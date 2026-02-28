<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Application\Services\MailService;
use Application\Services\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;

/**
 * Controller para tarefas agendadas (cron jobs via HTTP)
 * 
 * Permite executar tarefas de manutenção via requisição HTTP,
 * ideal para ambientes hospedados que não suportam cron nativo.
 */
class SchedulerController extends BaseController
{
    /**
     * Token secreto para autenticar requisições do scheduler
     */
    private function validateSchedulerToken(): bool
    {
        $token = $_SERVER['HTTP_X_SCHEDULER_TOKEN']
            ?? $_GET['token']
            ?? null;

        // Tenta $_ENV primeiro, depois getenv() como fallback
        $expectedToken = $_ENV['SCHEDULER_TOKEN'] ?? getenv('SCHEDULER_TOKEN') ?: null;

        // Loga os valores para debug (mascara para não expor tudo)
        LogService::warning('[Scheduler][DEBUG] Token recebido e esperado', [
            'token_recebido' => $token ? substr($token, 0, 6) . '...' . substr($token, -6) : null,
            'token_esperado' => $expectedToken ? substr($expectedToken, 0, 6) . '...' . substr($expectedToken, -6) : null,
            'token_igual' => $expectedToken && $token ? hash_equals($expectedToken, (string) $token) : false,
            'env_keys' => array_keys($_ENV),
            'getenv_result' => getenv('SCHEDULER_TOKEN') !== false,
        ]);

        if (empty($expectedToken)) {
            LogService::warning('[Scheduler] SCHEDULER_TOKEN não configurado no .env');
            return false;
        }

        return hash_equals($expectedToken, (string) $token);
    }

    /**
     * Dispara lembretes de agendamentos pendentes
     * 
     * GET/POST /api/scheduler/dispatch-reminders
     */
    public function dispatchReminders(): void
    {
        if (!$this->validateSchedulerToken()) {
            LogService::warning('[Scheduler] Tentativa de acesso não autorizada', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        LogService::info('=== [Scheduler] Início do dispatch de lembretes ===');

        try {
            $now = new \DateTimeImmutable('now');
            $windowLimit = $now->modify('+10 minutes');
            $stats = [
                'processados' => 0,
                'enviados_inapp' => 0,
                'enviados_email' => 0,
                'ignorados' => 0,
                'erros' => [],
            ];

            $baseUrl = defined('BASE_URL')
                ? rtrim(BASE_URL, '/')
                : rtrim($_ENV['APP_URL'] ?? '', '/');
            $linkAgendamentos = $baseUrl ? $baseUrl . '/agendamentos' : null;

            $mailService = new MailService();

            $agendamentos = Agendamento::with(['usuario:id,nome,email'])
                ->whereIn('status', ['pendente', 'notificado'])
                ->where(function ($query) {
                    $query->whereNull('lembrete_antecedencia_em')
                        ->orWhereNull('notificado_em');
                })
                ->get();

            LogService::info("[Scheduler] Agendamentos para processar encontrados: " . count($agendamentos));

            foreach ($agendamentos as $agendamento) {
                $stats['processados']++;

                $pagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
                    ? \DateTimeImmutable::createFromInterface($agendamento->data_pagamento)
                    : new \DateTimeImmutable((string) $agendamento->data_pagamento);

                $leadSeconds = (int) ($agendamento->lembrar_antes_segundos ?? 0);
                $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;
                $windowEnd = $windowLimit->getTimestamp();
                $nowTs = $now->getTimestamp();
                $pagamentoTs = $pagamento->getTimestamp();

                // Limite: não enviar se o pagamento já passou há mais de 24 horas
                $maxAtrasoHoras = 24;
                $limiteAtraso = $nowTs - ($maxAtrasoHoras * 3600);

                if ($pagamentoTs < $limiteAtraso) {
                    $stats['ignorados']++;
                    continue;
                }

                $enviouAlgo = false;

                // ===== LEMBRETE DE ANTECEDÊNCIA =====
                $temAntecedencia = $leadSeconds > 0;
                $antecedenciaNaoEnviada = empty($agendamento->lembrete_antecedencia_em);
                $momentoAntecedenciaChegou = ($reminderTimestamp <= $windowEnd);

                if ($temAntecedencia && $antecedenciaNaoEnviada && $momentoAntecedenciaChegou) {
                    $segundosRestantes = $pagamentoTs - $nowTs;
                    $tempoRestante = $segundosRestantes > 3600
                        ? floor($segundosRestantes / 3600) . ' hora(s)'
                        : floor($segundosRestantes / 60) . ' minuto(s)';

                    LogService::info("[Scheduler] Enviando lembrete de ANTECEDÊNCIA para #{$agendamento->id}");

                    if ($agendamento->canal_inapp) {
                        try {
                            Notificacao::create([
                                'user_id' => $agendamento->user_id,
                                'tipo' => 'agendamento',
                                'titulo' => 'Lembrete de pagamento',
                                'mensagem' => sprintf(
                                    'Lembrete: %s vence em %s (%s).',
                                    $agendamento->titulo,
                                    $tempoRestante,
                                    $pagamento->format('d/m/Y H:i')
                                ),
                                'link' => $linkAgendamentos,
                                'lida' => 0,
                            ]);
                            $stats['enviados_inapp']++;
                        } catch (\Throwable $e) {
                            $stats['erros'][] = ['tipo' => 'inapp_antecedencia', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                        }
                    }

                    if ($agendamento->canal_email && $mailService->isConfigured()) {
                        $usuario = $agendamento->usuario;
                        if ($usuario && !empty($usuario->email)) {
                            try {
                                $mailService->sendAgendamentoReminder($agendamento, $usuario, 'antecedencia');
                                $stats['enviados_email']++;
                            } catch (\Throwable $e) {
                                $stats['erros'][] = ['tipo' => 'email_antecedencia', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                            }
                        }
                    }

                    $agendamento->lembrete_antecedencia_em = $now->format('Y-m-d H:i:s');
                    $enviouAlgo = true;
                }

                // ===== LEMBRETE NO HORÁRIO =====
                $horarioNaoEnviado = empty($agendamento->notificado_em);
                $momentoHorarioChegou = ($pagamentoTs <= $windowEnd);

                if ($horarioNaoEnviado && $momentoHorarioChegou) {
                    LogService::info("[Scheduler] Enviando lembrete NO HORÁRIO para #{$agendamento->id}");

                    if ($agendamento->canal_inapp) {
                        try {
                            Notificacao::create([
                                'user_id' => $agendamento->user_id,
                                'tipo' => 'agendamento',
                                'titulo' => 'Pagamento agora!',
                                'mensagem' => sprintf(
                                    'Atenção: %s vence agora! (%s)',
                                    $agendamento->titulo,
                                    $pagamento->format('d/m/Y H:i')
                                ),
                                'link' => $linkAgendamentos,
                                'lida' => 0,
                            ]);
                            $stats['enviados_inapp']++;
                        } catch (\Throwable $e) {
                            $stats['erros'][] = ['tipo' => 'inapp_horario', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                        }
                    }

                    if ($agendamento->canal_email && $mailService->isConfigured()) {
                        $usuario = $agendamento->usuario;
                        if ($usuario && !empty($usuario->email)) {
                            try {
                                $mailService->sendAgendamentoReminder($agendamento, $usuario, 'horario');
                                $stats['enviados_email']++;
                            } catch (\Throwable $e) {
                                $stats['erros'][] = ['tipo' => 'email_horario', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                            }
                        }
                    }

                    $agendamento->status = 'notificado';
                    $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
                    $enviouAlgo = true;
                }

                if ($enviouAlgo) {
                    $agendamento->save();
                } else {
                    $stats['ignorados']++;
                }
            }

            LogService::info('=== [Scheduler] Dispatch de lembretes finalizado ===', $stats);

            Response::json([
                'success' => true,
                'message' => 'Lembretes processados com sucesso',
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            LogService::critical('[Scheduler] Erro fatal no dispatch de lembretes', [
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
            ]);

            Response::json([
                'success' => false,
                'error' => 'Erro interno ao processar lembretes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint de health check para verificar se o scheduler está funcionando
     * 
     * GET /api/scheduler/health
     */
    public function health(): void
    {
        Response::json([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
        ]);
    }

    /**
     * Lista tarefas disponíveis no scheduler (requer autenticação)
     * 
     * GET /api/scheduler/tasks
     */
    public function tasks(): void
    {
        if (!$this->validateSchedulerToken()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        Response::json([
            'tasks' => [
                [
                    'name' => 'dispatch-reminders',
                    'endpoint' => '/api/scheduler/dispatch-reminders',
                    'description' => 'Dispara lembretes de agendamentos pendentes',
                    'recommended_interval' => '5 minutos',
                ],
                [
                    'name' => 'process-expired-subscriptions',
                    'endpoint' => '/api/scheduler/process-expired-subscriptions',
                    'description' => 'Processa assinaturas expiradas',
                    'recommended_interval' => '1 hora',
                ],
            ],
        ]);
    }

    /**
     * Processa assinaturas expiradas
     * 
     * GET/POST /api/scheduler/process-expired-subscriptions
     */
    public function processExpiredSubscriptions(): void
    {
        if (!$this->validateSchedulerToken()) {
            LogService::warning('[Scheduler] Tentativa de acesso não autorizada', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        LogService::info('=== [Scheduler] Processando assinaturas expiradas ===');

        try {
            $service = new \Application\Services\SubscriptionExpirationService();
            $result = $service->processExpiredSubscriptions();

            LogService::info('[Scheduler] Assinaturas expiradas processadas', $result);

            Response::json([
                'success' => true,
                'message' => 'Assinaturas processadas com sucesso',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            LogService::critical('[Scheduler] Erro ao processar assinaturas', [
                'mensagem' => $e->getMessage(),
            ]);

            Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifica status da configuração de email e scheduler
     * 
     * GET /api/scheduler/debug
     */
    public function debug(): void
    {
        if (!$this->validateSchedulerToken()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $mailService = new MailService();

        // Verifica agendamentos pendentes
        $agendamentosPendentes = Agendamento::where('status', 'pendente')->count();
        $agendamentosComEmail = Agendamento::where('status', 'pendente')
            ->where('canal_email', true)
            ->count();
        $agendamentosComInapp = Agendamento::where('status', 'pendente')
            ->where('canal_inapp', true)
            ->count();

        // Próximos lembretes
        $now = new \DateTimeImmutable('now');
        $proximosLembretes = [];

        $agendamentos = Agendamento::with(['usuario:id,nome,email'])
            ->where('status', 'pendente')
            ->orderBy('data_pagamento', 'asc')
            ->limit(10)
            ->get();

        foreach ($agendamentos as $ag) {
            $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
                : new \DateTimeImmutable((string) $ag->data_pagamento);

            $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
            $reminderTime = $pagamento->getTimestamp() - $leadSeconds;
            $reminderDate = (new \DateTimeImmutable())->setTimestamp($reminderTime);

            $proximosLembretes[] = [
                'id' => $ag->id,
                'titulo' => $ag->titulo,
                'data_pagamento' => $pagamento->format('Y-m-d H:i:s'),
                'lembrar_antes_segundos' => $leadSeconds,
                'data_lembrete' => $reminderDate->format('Y-m-d H:i:s'),
                'canal_email' => (bool) $ag->canal_email,
                'canal_inapp' => (bool) $ag->canal_inapp,
                'usuario_email' => $ag->usuario?->email ?? null,
                'status' => $this->getStatusLembrete($reminderTime, $now),
            ];
        }

        Response::json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'config' => [
                'mail_configured' => $mailService->isConfigured(),
                'mail_host' => !empty($_ENV['MAIL_HOST']) ? '***configurado***' : 'NÃO CONFIGURADO',
                'mail_from' => !empty($_ENV['MAIL_FROM']) ? '***configurado***' : 'NÃO CONFIGURADO',
                'app_url' => $_ENV['APP_URL'] ?? 'NÃO CONFIGURADO',
                'scheduler_token' => !empty($_ENV['SCHEDULER_TOKEN']) ? '***configurado***' : 'NÃO CONFIGURADO',
            ],
            'agendamentos' => [
                'pendentes' => $agendamentosPendentes,
                'com_email' => $agendamentosComEmail,
                'com_inapp' => $agendamentosComInapp,
            ],
            'proximos_lembretes' => $proximosLembretes,
        ]);
    }

    /**
     * Determina o status do lembrete
     */
    private function getStatusLembrete(int $reminderTimestamp, \DateTimeImmutable $now): string
    {
        $nowTs = $now->getTimestamp();
        $windowLimit = $now->modify('+10 minutes')->getTimestamp();

        if ($reminderTimestamp < $nowTs) {
            return 'atrasado';
        }

        if ($reminderTimestamp <= $windowLimit) {
            return 'pronto_para_enviar';
        }

        $diff = $reminderTimestamp - $nowTs;
        if ($diff < 3600) {
            return 'em_' . round($diff / 60) . '_minutos';
        }
        if ($diff < 86400) {
            return 'em_' . round($diff / 3600) . '_horas';
        }
        return 'em_' . round($diff / 86400) . '_dias';
    }

    /**
     * Executa todas as tarefas do cron em uma única chamada
     * 
     * GET /api/rota-do-cron
     * 
     * Ideal para serviços de cron externos que fazem uma única requisição.
     */
    public function runAll(): void
    {
        if (!$this->validateSchedulerToken()) {
            LogService::warning('[Scheduler] Tentativa de acesso não autorizada em runAll', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        LogService::info('=== [Scheduler] Início da execução de todas as tarefas ===');

        $results = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'tasks' => [],
        ];

        // 1. Processar lembretes de agendamentos
        try {
            ob_start();
            $this->dispatchRemindersInternal();
            $output = ob_get_clean();
            $results['tasks']['dispatch_reminders'] = [
                'status' => 'success',
                'message' => 'Lembretes processados',
            ];
        } catch (\Throwable $e) {
            ob_end_clean();
            $results['tasks']['dispatch_reminders'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            LogService::captureException($e, LogCategory::AGENDAMENTO, [
                'action' => 'dispatch_reminders',
            ]);
        }

        // 2. Processar assinaturas expiradas
        try {
            $service = new \Application\Services\SubscriptionExpirationService();
            $expResult = $service->processExpiredSubscriptions();
            $results['tasks']['process_expired_subscriptions'] = [
                'status' => 'success',
                'result' => $expResult,
            ];
        } catch (\Throwable $e) {
            $results['tasks']['process_expired_subscriptions'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                'action' => 'process_expired_subscriptions',
            ]);
        }

        // 3. Gerar itens recorrentes de cartão de crédito (assinaturas)
        try {
            $recorrenciaService = new \Application\Services\RecorrenciaCartaoService();
            $recResult = $recorrenciaService->processRecurringCardItems();
            $results['tasks']['process_card_recurrences'] = [
                'status' => 'success',
                'result' => $recResult,
            ];
        } catch (\Throwable $e) {
            $results['tasks']['process_card_recurrences'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'process_card_recurrences',
            ]);
        }

        // 4. Lembretes de vencimento de faturas de cartão
        try {
            $faturaResult = $this->dispatchFaturaRemindersInternal();
            $results['tasks']['dispatch_fatura_reminders'] = [
                'status' => 'success',
                'result' => $faturaResult,
            ];
        } catch (\Throwable $e) {
            $results['tasks']['dispatch_fatura_reminders'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'dispatch_fatura_reminders',
            ]);
        }

        // Verifica se houve algum erro
        foreach ($results['tasks'] as $task) {
            if ($task['status'] === 'error') {
                $results['success'] = false;
                break;
            }
        }

        LogService::info('=== [Scheduler] Execução de todas as tarefas finalizada ===', $results);

        Response::json($results);
    }

    /**
     * Versão interna do dispatchReminders para uso em runAll
     * (não envia Response, apenas processa)
     */
    private function dispatchRemindersInternal(): array
    {
        $now = new \DateTimeImmutable('now');
        $windowLimit = $now->modify('+10 minutes');
        $stats = [
            'processados' => 0,
            'enviados_inapp' => 0,
            'enviados_email' => 0,
            'ignorados' => 0,
            'erros' => [],
        ];

        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : rtrim($_ENV['APP_URL'] ?? '', '/');
        $linkAgendamentos = $baseUrl ? $baseUrl . '/agendamentos' : null;

        $mailService = new MailService();

        $agendamentos = Agendamento::with(['usuario:id,nome,email'])
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($query) {
                $query->whereNull('lembrete_antecedencia_em')
                    ->orWhereNull('notificado_em');
            })
            ->get();

        foreach ($agendamentos as $agendamento) {
            $stats['processados']++;

            $pagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($agendamento->data_pagamento)
                : new \DateTimeImmutable((string) $agendamento->data_pagamento);

            $leadSeconds = (int) ($agendamento->lembrar_antes_segundos ?? 0);
            $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;
            $nowTs = $now->getTimestamp();
            $pagamentoTs = $pagamento->getTimestamp();
            $windowEnd = $windowLimit->getTimestamp();

            // Limite: não enviar se o pagamento já passou há mais de 24 horas
            $maxAtrasoHoras = 24;
            $limiteAtraso = $nowTs - ($maxAtrasoHoras * 3600);

            if ($pagamentoTs < $limiteAtraso) {
                $stats['ignorados']++;
                continue;
            }

            $enviouAlgo = false;

            // ===== LEMBRETE DE ANTECEDÊNCIA =====
            $temAntecedencia = $leadSeconds > 0;
            $antecedenciaNaoEnviada = empty($agendamento->lembrete_antecedencia_em);
            $momentoAntecedenciaChegou = ($reminderTimestamp <= $windowEnd);

            if ($temAntecedencia && $antecedenciaNaoEnviada && $momentoAntecedenciaChegou) {
                $segundosRestantes = $pagamentoTs - $nowTs;
                $tempoRestante = $segundosRestantes > 3600
                    ? floor($segundosRestantes / 3600) . ' hora(s)'
                    : floor($segundosRestantes / 60) . ' minuto(s)';

                if ($agendamento->canal_inapp) {
                    try {
                        Notificacao::create([
                            'user_id' => $agendamento->user_id,
                            'tipo' => 'agendamento',
                            'titulo' => 'Lembrete de pagamento',
                            'mensagem' => sprintf(
                                'Lembrete: %s vence em %s (%s).',
                                $agendamento->titulo,
                                $tempoRestante,
                                $pagamento->format('d/m/Y H:i')
                            ),
                            'link' => $linkAgendamentos,
                            'lida' => 0,
                        ]);
                        $stats['enviados_inapp']++;
                    } catch (\Throwable $e) {
                        $stats['erros'][] = ['tipo' => 'inapp_antecedencia', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                    }
                }

                if ($agendamento->canal_email && $mailService->isConfigured()) {
                    $usuario = $agendamento->usuario;
                    if ($usuario && !empty($usuario->email)) {
                        try {
                            $mailService->sendAgendamentoReminder($agendamento, $usuario, 'antecedencia');
                            $stats['enviados_email']++;
                        } catch (\Throwable $e) {
                            $stats['erros'][] = ['tipo' => 'email_antecedencia', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                        }
                    }
                }

                $agendamento->lembrete_antecedencia_em = $now->format('Y-m-d H:i:s');
                $enviouAlgo = true;
            }

            // ===== LEMBRETE NO HORÁRIO =====
            $horarioNaoEnviado = empty($agendamento->notificado_em);
            $momentoHorarioChegou = ($pagamentoTs <= $windowEnd);

            if ($horarioNaoEnviado && $momentoHorarioChegou) {
                if ($agendamento->canal_inapp) {
                    try {
                        Notificacao::create([
                            'user_id' => $agendamento->user_id,
                            'tipo' => 'agendamento',
                            'titulo' => 'Pagamento agora!',
                            'mensagem' => sprintf(
                                'Atenção: %s vence agora! (%s)',
                                $agendamento->titulo,
                                $pagamento->format('d/m/Y H:i')
                            ),
                            'link' => $linkAgendamentos,
                            'lida' => 0,
                        ]);
                        $stats['enviados_inapp']++;
                    } catch (\Throwable $e) {
                        $stats['erros'][] = ['tipo' => 'inapp_horario', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                    }
                }

                if ($agendamento->canal_email && $mailService->isConfigured()) {
                    $usuario = $agendamento->usuario;
                    if ($usuario && !empty($usuario->email)) {
                        try {
                            $mailService->sendAgendamentoReminder($agendamento, $usuario, 'horario');
                            $stats['enviados_email']++;
                        } catch (\Throwable $e) {
                            $stats['erros'][] = ['tipo' => 'email_horario', 'id' => $agendamento->id, 'erro' => $e->getMessage()];
                        }
                    }
                }

                $agendamento->status = 'notificado';
                $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
                $enviouAlgo = true;
            }

            if ($enviouAlgo) {
                $agendamento->save();
            } else {
                $stats['ignorados']++;
            }
        }

        return $stats;
    }

    /**
     * Processa notificações de aniversário do dia
     * 
     * GET/POST /api/scheduler/dispatch-birthdays
     */
    public function dispatchBirthdays(): void
    {
        if (!$this->validateSchedulerToken()) {
            LogService::warning('[Scheduler] Tentativa de acesso não autorizada - birthdays', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        LogService::info('=== [Scheduler] Início do dispatch de aniversários ===');

        try {
            $notificationService = new \Application\Services\NotificationService();

            // Envia notificações internas e emails
            $result = $notificationService->processBirthdayNotifications(true);

            LogService::info('[Scheduler] Aniversários processados', $result);

            Response::json([
                'success' => true,
                'message' => 'Notificações de aniversário processadas',
                'stats' => $result,
            ]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'dispatch_birthdays',
            ]);

            Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processa lembretes de vencimento de faturas de cartão de crédito
     *
     * GET/POST /api/scheduler/dispatch-fatura-reminders
     */
    public function dispatchFaturaReminders(): void
    {
        if (!$this->validateSchedulerToken()) {
            LogService::warning('[Scheduler] Tentativa de acesso não autorizada - fatura-reminders', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        LogService::info('=== [Scheduler] Início do dispatch de lembretes de fatura ===');

        try {
            $now = new \DateTimeImmutable('now');
            $mesAtual = $now->format('Y-m');

            $baseUrl = defined('BASE_URL')
                ? rtrim(BASE_URL, '/')
                : rtrim($_ENV['APP_URL'] ?? '', '/');
            $linkFaturas = $baseUrl ? $baseUrl . '/faturas' : null;

            $mailService = new MailService();

            $cartoes = \Application\Models\CartaoCredito::with(['usuario:id,nome,email'])
                ->where('ativo', true)
                ->where('arquivado', false)
                ->whereNotNull('dia_vencimento')
                ->whereNotNull('lembrar_fatura_antes_segundos')
                ->where('lembrar_fatura_antes_segundos', '>', 0)
                ->where(function ($q) use ($mesAtual) {
                    $q->whereNull('fatura_notificado_mes')
                        ->orWhere('fatura_notificado_mes', '<', $mesAtual);
                })
                ->get();

            $stats = [
                'processados' => count($cartoes),
                'enviados' => 0,
                'ignorados' => 0,
            ];

            foreach ($cartoes as $cartao) {
                $diaVencimento = (int) $cartao->dia_vencimento;
                $leadSeconds = (int) $cartao->lembrar_fatura_antes_segundos;
                $diaAtual = (int) $now->format('j');
                $mesRef = (int) $now->format('n');
                $anoRef = (int) $now->format('Y');

                if ($diaAtual > $diaVencimento) {
                    $mesRef++;
                    if ($mesRef > 12) {
                        $mesRef = 1;
                        $anoRef++;
                    }
                }

                $diaReal = min($diaVencimento, (int) date('t', mktime(0, 0, 0, $mesRef, 1, $anoRef)));
                $dataVencimento = new \DateTimeImmutable(
                    sprintf('%04d-%02d-%02d 12:00:00', $anoRef, $mesRef, $diaReal)
                );

                $mesNotificacao = $dataVencimento->format('Y-m');
                if ($cartao->fatura_notificado_mes === $mesNotificacao) {
                    $stats['ignorados']++;
                    continue;
                }

                $reminderTimestamp = $dataVencimento->getTimestamp() - $leadSeconds;
                $nowTs = $now->getTimestamp();

                if ($dataVencimento->getTimestamp() < ($nowTs - 86400)) {
                    $stats['ignorados']++;
                    continue;
                }
                if ($reminderTimestamp > $nowTs) {
                    $stats['ignorados']++;
                    continue;
                }

                // Calcular tempo restante
                $segundosRestantes = $dataVencimento->getTimestamp() - $nowTs;
                $tempoRestante = 'hoje';
                if ($segundosRestantes > 86400) {
                    $dias = floor($segundosRestantes / 86400);
                    $tempoRestante = $dias . ' dia' . ($dias > 1 ? 's' : '');
                } elseif ($segundosRestantes > 3600) {
                    $horas = floor($segundosRestantes / 3600);
                    $tempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
                }

                $usuario = $cartao->usuario;
                if (!$usuario) {
                    $stats['ignorados']++;
                    continue;
                }

                $mensagem = $segundosRestantes > 0
                    ? sprintf('A fatura do cartão %s vence em %s (%s).', $cartao->nome_cartao, $tempoRestante, $dataVencimento->format('d/m/Y'))
                    : sprintf('A fatura do cartão %s vence hoje (%s)!', $cartao->nome_cartao, $dataVencimento->format('d/m/Y'));

                if ($cartao->fatura_canal_inapp) {
                    \Application\Models\Notificacao::create([
                        'user_id' => $cartao->user_id,
                        'tipo' => 'fatura',
                        'titulo' => 'Lembrete de fatura',
                        'mensagem' => $mensagem,
                        'link' => $linkFaturas,
                        'lida' => 0,
                    ]);
                }

                if ($cartao->fatura_canal_email && $mailService->isConfigured() && !empty($usuario->email)) {
                    try {
                        $assunto = $segundosRestantes > 0
                            ? "Lembrete: Fatura do {$cartao->nome_cartao} vence em {$tempoRestante}"
                            : "Lembrete: Fatura do {$cartao->nome_cartao} vence HOJE!";

                        $corpo = "<p>Olá, {$usuario->nome}!</p>"
                            . "<p>{$mensagem}</p>"
                            . "<p>Não esqueça de efetuar o pagamento para evitar juros e multas.</p>"
                            . ($linkFaturas ? "<p><a href=\"{$linkFaturas}\">Ver minhas faturas</a></p>" : '');

                        $mailService->send($usuario->email, $assunto, $corpo);
                    } catch (\Throwable $e) {
                        LogService::error("[Scheduler] Erro ao enviar email fatura: " . $e->getMessage());
                    }
                }

                $cartao->fatura_notificado_mes = $mesNotificacao;
                $cartao->save();
                $stats['enviados']++;
            }

            LogService::info('[Scheduler] Lembretes de fatura processados', $stats);

            Response::json([
                'success' => true,
                'message' => 'Lembretes de fatura processados',
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'dispatch_fatura_reminders',
            ]);

            Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lógica interna de lembretes de fatura (usada pelo runAll)
     */
    private function dispatchFaturaRemindersInternal(): array
    {
        $now = new \DateTimeImmutable('now');
        $mesAtual = $now->format('Y-m');

        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : rtrim($_ENV['APP_URL'] ?? '', '/');
        $linkFaturas = $baseUrl ? $baseUrl . '/faturas' : null;

        $mailService = new MailService();

        $cartoes = \Application\Models\CartaoCredito::with(['usuario:id,nome,email'])
            ->where('ativo', true)
            ->where('arquivado', false)
            ->whereNotNull('dia_vencimento')
            ->whereNotNull('lembrar_fatura_antes_segundos')
            ->where('lembrar_fatura_antes_segundos', '>', 0)
            ->where(function ($q) use ($mesAtual) {
                $q->whereNull('fatura_notificado_mes')
                    ->orWhere('fatura_notificado_mes', '<', $mesAtual);
            })
            ->get();

        $stats = [
            'processados' => count($cartoes),
            'enviados' => 0,
            'ignorados' => 0,
        ];

        foreach ($cartoes as $cartao) {
            $diaVencimento = (int) $cartao->dia_vencimento;
            $leadSeconds = (int) $cartao->lembrar_fatura_antes_segundos;
            $diaAtual = (int) $now->format('j');
            $mesRef = (int) $now->format('n');
            $anoRef = (int) $now->format('Y');

            if ($diaAtual > $diaVencimento) {
                $mesRef++;
                if ($mesRef > 12) {
                    $mesRef = 1;
                    $anoRef++;
                }
            }

            $diaReal = min($diaVencimento, (int) date('t', mktime(0, 0, 0, $mesRef, 1, $anoRef)));
            $dataVencimento = new \DateTimeImmutable(
                sprintf('%04d-%02d-%02d 12:00:00', $anoRef, $mesRef, $diaReal)
            );

            $mesNotificacao = $dataVencimento->format('Y-m');
            if ($cartao->fatura_notificado_mes === $mesNotificacao) {
                $stats['ignorados']++;
                continue;
            }

            $reminderTimestamp = $dataVencimento->getTimestamp() - $leadSeconds;
            $nowTs = $now->getTimestamp();

            if ($dataVencimento->getTimestamp() < ($nowTs - 86400)) {
                $stats['ignorados']++;
                continue;
            }
            if ($reminderTimestamp > $nowTs) {
                $stats['ignorados']++;
                continue;
            }

            $segundosRestantes = $dataVencimento->getTimestamp() - $nowTs;
            $tempoRestante = 'hoje';
            if ($segundosRestantes > 86400) {
                $dias = floor($segundosRestantes / 86400);
                $tempoRestante = $dias . ' dia' . ($dias > 1 ? 's' : '');
            } elseif ($segundosRestantes > 3600) {
                $horas = floor($segundosRestantes / 3600);
                $tempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
            }

            $usuario = $cartao->usuario;
            if (!$usuario) {
                $stats['ignorados']++;
                continue;
            }

            $mensagem = $segundosRestantes > 0
                ? sprintf('A fatura do cartão %s vence em %s (%s).', $cartao->nome_cartao, $tempoRestante, $dataVencimento->format('d/m/Y'))
                : sprintf('A fatura do cartão %s vence hoje (%s)!', $cartao->nome_cartao, $dataVencimento->format('d/m/Y'));

            if ($cartao->fatura_canal_inapp) {
                \Application\Models\Notificacao::create([
                    'user_id' => $cartao->user_id,
                    'tipo' => 'fatura',
                    'titulo' => 'Lembrete de fatura',
                    'mensagem' => $mensagem,
                    'link' => $linkFaturas,
                    'lida' => 0,
                ]);
            }

            if ($cartao->fatura_canal_email && $mailService->isConfigured() && !empty($usuario->email)) {
                try {
                    $assunto = $segundosRestantes > 0
                        ? "Lembrete: Fatura do {$cartao->nome_cartao} vence em {$tempoRestante}"
                        : "Lembrete: Fatura do {$cartao->nome_cartao} vence HOJE!";

                    $corpo = "<p>Olá, {$usuario->nome}!</p>"
                        . "<p>{$mensagem}</p>"
                        . "<p>Não esqueça de efetuar o pagamento para evitar juros e multas.</p>"
                        . ($linkFaturas ? "<p><a href=\"{$linkFaturas}\">Ver minhas faturas</a></p>" : '');

                    $mailService->send($usuario->email, $assunto, $corpo);
                } catch (\Throwable $e) {
                    LogService::error("[Scheduler] Erro ao enviar email fatura: " . $e->getMessage());
                }
            }

            $cartao->fatura_notificado_mes = $mesNotificacao;
            $cartao->save();
            $stats['enviados']++;
        }

        LogService::info('[Scheduler] Lembretes de fatura processados (runAll)', $stats);

        return $stats;
    }
}
