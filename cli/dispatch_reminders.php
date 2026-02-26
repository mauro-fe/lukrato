<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\Notificacao;
use Application\Services\MailService;
use Application\Services\LogService;

LogService::info('=== [dispatch_reminders] Inicio do lembrete de lançamentos ===');

try {
    $now = new \DateTimeImmutable('now');
    $windowLimit = $now->modify('+10 minutes');

    $baseUrl = defined('BASE_URL')
        ? rtrim(BASE_URL, '/')
        : rtrim($_ENV['APP_URL'] ?? '', '/');
    $linkLancamentos = $baseUrl ? $baseUrl . '/lancamentos' : null;

    $mailService = new MailService();

    // Buscar lançamentos futuros não pagos com lembrete configurado
    $lancamentos = Lancamento::with(['usuario:id,nome,email'])
        ->where('pago', 0)
        ->whereNull('cancelado_em')
        ->whereNotNull('lembrar_antes_segundos')
        ->where('lembrar_antes_segundos', '>', 0)
        ->where(function ($query) {
            $query->whereNull('lembrete_antecedencia_em')
                ->orWhereNull('notificado_em');
        })
        ->get();

    $count = count($lancamentos);
    LogService::info("[dispatch_reminders] Lançamentos para processar encontrados: {$count}");

    foreach ($lancamentos as $lancamento) {
        $dataLanc = new \DateTimeImmutable((string)$lancamento->data);
        // Use meio-dia como referência (lançamentos têm só data, sem hora)
        $pagamentoTs = $dataLanc->setTime(12, 0)->getTimestamp();
        $leadSeconds = (int)($lancamento->lembrar_antes_segundos ?? 0);
        $reminderTimestamp = $pagamentoTs - $leadSeconds;
        $nowTs = $now->getTimestamp();
        $windowEnd = $windowLimit->getTimestamp();

        // Não enviar se a data já passou há mais de 24h
        $limiteAtraso = $nowTs - (24 * 3600);
        if ($pagamentoTs < $limiteAtraso) {
            LogService::info(sprintf(
                "[dispatch_reminders] Ignorado lançamento #%d (%s): data muito antiga (%s)",
                $lancamento->id,
                $lancamento->descricao,
                $dataLanc->format('d/m/Y')
            ));
            continue;
        }

        $enviouAlgo = false;

        // ===== LEMBRETE DE ANTECEDÊNCIA =====
        $temAntecedencia = $leadSeconds > 0;
        $antecedenciaNaoEnviada = empty($lancamento->lembrete_antecedencia_em);
        $momentoAntecedenciaChegou = ($reminderTimestamp <= $windowEnd);

        if ($temAntecedencia && $antecedenciaNaoEnviada && $momentoAntecedenciaChegou) {
            $segundosRestantes = $pagamentoTs - $nowTs;
            $tempoRestante = '';
            if ($segundosRestantes > 86400) {
                $dias = floor($segundosRestantes / 86400);
                $tempoRestante = $dias . ' dia' . ($dias > 1 ? 's' : '');
            } elseif ($segundosRestantes > 3600) {
                $horas = floor($segundosRestantes / 3600);
                $tempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
            } elseif ($segundosRestantes > 60) {
                $minutos = floor($segundosRestantes / 60);
                $tempoRestante = $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
            } else {
                $tempoRestante = 'alguns instantes';
            }

            LogService::info(sprintf(
                "[dispatch_reminders] Enviando lembrete de ANTECEDÊNCIA para lançamento #%d (%s)...",
                $lancamento->id,
                $lancamento->descricao
            ));

            if ($lancamento->canal_inapp) {
                Notificacao::create([
                    'user_id' => $lancamento->user_id,
                    'tipo' => 'lancamento',
                    'titulo' => 'Lembrete de lançamento',
                    'mensagem' => sprintf(
                        'Lembrete: %s (%s) vence em %s (%s).',
                        $lancamento->descricao,
                        $lancamento->tipo,
                        $tempoRestante,
                        $dataLanc->format('d/m/Y')
                    ),
                    'link' => $linkLancamentos,
                    'lida' => 0,
                ]);
                LogService::info("[dispatch_reminders] Notificacao in-app de antecedência criada");
            }

            if ($lancamento->canal_email && $mailService->isConfigured()) {
                $usuario = $lancamento->usuario;
                if ($usuario && !empty($usuario->email)) {
                    try {
                        $mailService->sendLancamentoReminder($lancamento, $usuario, 'antecedencia');
                        LogService::info("[dispatch_reminders] Email de antecedência enviado para {$usuario->email}");
                    } catch (\Throwable $exception) {
                        LogService::error('[dispatch_reminders] Falha ao enviar email de antecedência', [
                            'erro' => $exception->getMessage(),
                            'lancamento_id' => $lancamento->id,
                        ]);
                    }
                }
            }

            $lancamento->lembrete_antecedencia_em = $now->format('Y-m-d H:i:s');
            $enviouAlgo = true;
        }

        // ===== LEMBRETE NO DIA =====
        $horarioNaoEnviado = empty($lancamento->notificado_em);
        $momentoHorarioChegou = ($pagamentoTs <= $windowEnd);

        if ($horarioNaoEnviado && $momentoHorarioChegou) {
            LogService::info(sprintf(
                "[dispatch_reminders] Enviando lembrete NO DIA para lançamento #%d (%s)...",
                $lancamento->id,
                $lancamento->descricao
            ));

            if ($lancamento->canal_inapp) {
                Notificacao::create([
                    'user_id' => $lancamento->user_id,
                    'tipo' => 'lancamento',
                    'titulo' => 'Lançamento vence hoje!',
                    'mensagem' => sprintf(
                        'Atenção: %s (%s) vence hoje! (%s)',
                        $lancamento->descricao,
                        $lancamento->tipo,
                        $dataLanc->format('d/m/Y')
                    ),
                    'link' => $linkLancamentos,
                    'lida' => 0,
                ]);
                LogService::info("[dispatch_reminders] Notificacao in-app no dia criada");
            }

            if ($lancamento->canal_email && $mailService->isConfigured()) {
                $usuario = $lancamento->usuario;
                if ($usuario && !empty($usuario->email)) {
                    try {
                        $mailService->sendLancamentoReminder($lancamento, $usuario, 'horario');
                        LogService::info("[dispatch_reminders] Email no dia enviado para {$usuario->email}");
                    } catch (\Throwable $exception) {
                        LogService::error('[dispatch_reminders] Falha ao enviar email no dia', [
                            'erro' => $exception->getMessage(),
                            'lancamento_id' => $lancamento->id,
                        ]);
                    }
                }
            }

            $lancamento->notificado_em = $now->format('Y-m-d H:i:s');
            $enviouAlgo = true;
        }

        // Salvar alterações se enviou algo
        if ($enviouAlgo) {
            $lancamento->save();
            LogService::info("[dispatch_reminders] Lançamento #{$lancamento->id} atualizado.");
        } else {
            LogService::info(sprintf(
                "[dispatch_reminders] Nenhum lembrete enviado para #%d: aguardando momento correto (antec: %s, data: %s)",
                $lancamento->id,
                date('d/m/Y H:i', $reminderTimestamp),
                $dataLanc->format('d/m/Y')
            ));
        }
    }

    LogService::info('=== [dispatch_reminders] Execucao finalizada com sucesso ===');
} catch (\Throwable $e) {
    LogService::critical('[dispatch_reminders] Erro fatal no script', [
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
}
