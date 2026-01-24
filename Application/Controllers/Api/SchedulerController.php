<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Application\Services\MailService;
use Application\Services\LogService;

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

        if (empty($expectedToken)) {
            LogService::warning('[Scheduler] SCHEDULER_TOKEN não configurado no .env', [
                'env_keys' => array_keys($_ENV),
                'getenv_result' => getenv('SCHEDULER_TOKEN') !== false,
            ]);
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
                ->where('status', 'pendente')
                ->get();

            LogService::info("[Scheduler] Agendamentos pendentes encontrados: " . count($agendamentos));

            foreach ($agendamentos as $agendamento) {
                $stats['processados']++;

                $pagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
                    ? \DateTimeImmutable::createFromInterface($agendamento->data_pagamento)
                    : new \DateTimeImmutable((string) $agendamento->data_pagamento);

                $leadSeconds = (int) ($agendamento->lembrar_antes_segundos ?? 0);
                $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;

                // Verifica se está dentro da janela de envio
                if ($reminderTimestamp > $windowLimit->getTimestamp() || $reminderTimestamp < $now->getTimestamp()) {
                    $stats['ignorados']++;
                    LogService::info(sprintf(
                        "[Scheduler] Ignorado agendamento #%d (%s): fora da janela",
                        $agendamento->id,
                        $agendamento->titulo
                    ));
                    continue;
                }

                LogService::info(sprintf(
                    "[Scheduler] Processando lembrete para agendamento #%d (%s)...",
                    $agendamento->id,
                    $agendamento->titulo
                ));

                // Notificação in-app
                if ($agendamento->canal_inapp) {
                    try {
                        Notificacao::create([
                            'user_id' => $agendamento->user_id,
                            'tipo' => 'agendamento',
                            'titulo' => 'Lembrete de pagamento',
                            'mensagem' => sprintf(
                                '%s agendado para %s.',
                                $agendamento->titulo,
                                $pagamento->format('d/m/Y H:i')
                            ),
                            'link' => $linkAgendamentos,
                            'lida' => 0,
                        ]);

                        $stats['enviados_inapp']++;
                        LogService::info("[Scheduler] Notificação in-app criada para user_id={$agendamento->user_id}");
                    } catch (\Throwable $e) {
                        $stats['erros'][] = [
                            'tipo' => 'inapp',
                            'agendamento_id' => $agendamento->id,
                            'erro' => $e->getMessage(),
                        ];
                        LogService::error('[Scheduler] Erro ao criar notificação in-app', [
                            'agendamento_id' => $agendamento->id,
                            'erro' => $e->getMessage(),
                        ]);
                    }
                }

                // Notificação por email
                if ($agendamento->canal_email) {
                    if ($mailService->isConfigured()) {
                        $usuario = $agendamento->usuario;
                        if ($usuario && !empty($usuario->email)) {
                            try {
                                $mailService->sendAgendamentoReminder($agendamento, $usuario);
                                $stats['enviados_email']++;
                                LogService::info(sprintf(
                                    "[Scheduler] Email enviado para %s (%s)",
                                    $usuario->nome ?? 'usuário sem nome',
                                    $usuario->email
                                ));
                            } catch (\Throwable $e) {
                                $stats['erros'][] = [
                                    'tipo' => 'email',
                                    'agendamento_id' => $agendamento->id,
                                    'erro' => $e->getMessage(),
                                ];
                                LogService::error('[Scheduler] Falha ao enviar email', [
                                    'erro' => $e->getMessage(),
                                    'agendamento_id' => $agendamento->id,
                                ]);
                            }
                        } else {
                            LogService::warning('[Scheduler] Usuário sem email', [
                                'agendamento_id' => $agendamento->id,
                            ]);
                        }
                    } else {
                        LogService::warning('[Scheduler] Canal email habilitado, mas SMTP não configurado');
                    }
                }

                // Marca como enviado
                $agendamento->status = 'enviado';
                $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
                $agendamento->save();

                LogService::info("[Scheduler] Agendamento #{$agendamento->id} marcado como 'enviado'.");
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
            LogService::error('[Scheduler] Erro em dispatch_reminders', [
                'erro' => $e->getMessage(),
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
            LogService::error('[Scheduler] Erro em process_expired_subscriptions', [
                'erro' => $e->getMessage(),
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
            ->where('status', 'pendente')
            ->get();

        foreach ($agendamentos as $agendamento) {
            $stats['processados']++;

            $pagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($agendamento->data_pagamento)
                : new \DateTimeImmutable((string) $agendamento->data_pagamento);

            $leadSeconds = (int) ($agendamento->lembrar_antes_segundos ?? 0);
            $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;

            if ($reminderTimestamp > $windowLimit->getTimestamp() || $reminderTimestamp < $now->getTimestamp()) {
                $stats['ignorados']++;
                continue;
            }

            // Notificação in-app
            if ($agendamento->canal_inapp) {
                try {
                    Notificacao::create([
                        'user_id' => $agendamento->user_id,
                        'tipo' => 'agendamento',
                        'titulo' => 'Lembrete de pagamento',
                        'mensagem' => sprintf(
                            '%s agendado para %s.',
                            $agendamento->titulo,
                            $pagamento->format('d/m/Y H:i')
                        ),
                        'link' => $linkAgendamentos,
                        'lida' => 0,
                    ]);
                    $stats['enviados_inapp']++;
                } catch (\Throwable $e) {
                    $stats['erros'][] = ['tipo' => 'inapp', 'agendamento_id' => $agendamento->id, 'erro' => $e->getMessage()];
                }
            }

            // Notificação por email
            if ($agendamento->canal_email && $mailService->isConfigured()) {
                $usuario = $agendamento->usuario;
                if ($usuario && !empty($usuario->email)) {
                    try {
                        $mailService->sendAgendamentoReminder($agendamento, $usuario);
                        $stats['enviados_email']++;
                    } catch (\Throwable $e) {
                        $stats['erros'][] = ['tipo' => 'email', 'agendamento_id' => $agendamento->id, 'erro' => $e->getMessage()];
                    }
                }
            }

            $agendamento->status = 'enviado';
            $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
            $agendamento->save();
        }

        return $stats;
    }
}
