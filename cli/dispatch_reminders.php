<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Lancamento;
use Application\Models\Notificacao;
use Application\Services\MailService;
use Application\Services\LogService;

LogService::info('=== [dispatch_reminders] Inicio do lembrete de agendamentos ===');

try {
    $now = new \DateTimeImmutable('now');
    $windowLimit = $now->modify('+10 minutes');

    $baseUrl = defined('BASE_URL')
        ? rtrim(BASE_URL, '/')
        : rtrim($_ENV['APP_URL'] ?? '', '/');
    $linkAgendamentos = $baseUrl ? $baseUrl . '/agendamentos' : null;

    $mailService = new MailService();

    // Buscar agendamentos que precisam de lembrete (antecedência OU no horário)
    $agendamentos = Agendamento::with(['usuario:id,nome,email'])
        ->whereIn('status', ['pendente', 'notificado'])
        ->where(function ($query) {
            $query->whereNull('lembrete_antecedencia_em')
                ->orWhereNull('notificado_em');
        })
        ->get();

    $count = count($agendamentos);
    LogService::info("[dispatch_reminders] Agendamentos para processar encontrados: {$count}");

    foreach ($agendamentos as $agendamento) {
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

        // Pagamento não é muito antigo
        if ($pagamentoTs < $limiteAtraso) {
            LogService::info(sprintf(
                "[dispatch_reminders] Ignorado agendamento #%d (%s): pagamento muito antigo (%s)",
                $agendamento->id,
                $agendamento->titulo,
                $pagamento->format('d/m/Y H:i')
            ));
            continue;
        }

        $enviouAlgo = false;

        // ===== LEMBRETE DE ANTECEDÊNCIA =====
        // Enviar se: tem antecedência configurada, ainda não foi enviado, e o momento chegou
        $temAntecedencia = $leadSeconds > 0;
        $antecedenciaNaoEnviada = empty($agendamento->lembrete_antecedencia_em);
        $momentoAntecedenciaChegou = ($reminderTimestamp <= $windowEnd);

        if ($temAntecedencia && $antecedenciaNaoEnviada && $momentoAntecedenciaChegou) {
            // Calcular tempo restante para exibir na mensagem
            $segundosRestantes = $pagamentoTs - $nowTs;
            $tempoRestante = '';
            if ($segundosRestantes > 3600) {
                $horas = floor($segundosRestantes / 3600);
                $tempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
            } elseif ($segundosRestantes > 60) {
                $minutos = floor($segundosRestantes / 60);
                $tempoRestante = $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
            } else {
                $tempoRestante = 'alguns instantes';
            }

            LogService::info(sprintf(
                "[dispatch_reminders] Enviando lembrete de ANTECEDÊNCIA para agendamento #%d (%s)...",
                $agendamento->id,
                $agendamento->titulo
            ));

            if ($agendamento->canal_inapp) {
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
                LogService::info("[dispatch_reminders] Notificacao in-app de antecedência criada");
            }

            if ($agendamento->canal_email && $mailService->isConfigured()) {
                $usuario = $agendamento->usuario;
                if ($usuario && !empty($usuario->email)) {
                    try {
                        $mailService->sendAgendamentoReminder($agendamento, $usuario, 'antecedencia');
                        LogService::info("[dispatch_reminders] Email de antecedência enviado para {$usuario->email}");
                    } catch (\Throwable $exception) {
                        LogService::error('[dispatch_reminders] Falha ao enviar email de antecedência', [
                            'erro' => $exception->getMessage(),
                            'agendamento_id' => $agendamento->id,
                        ]);
                    }
                }
            }

            $agendamento->lembrete_antecedencia_em = $now->format('Y-m-d H:i:s');
            $enviouAlgo = true;
        }

        // ===== LEMBRETE NO HORÁRIO =====
        // Enviar se: ainda não foi enviado e o momento do pagamento chegou
        $horarioNaoEnviado = empty($agendamento->notificado_em);
        $momentoHorarioChegou = ($pagamentoTs <= $windowEnd);

        if ($horarioNaoEnviado && $momentoHorarioChegou) {
            LogService::info(sprintf(
                "[dispatch_reminders] Enviando lembrete NO HORÁRIO para agendamento #%d (%s)...",
                $agendamento->id,
                $agendamento->titulo
            ));

            if ($agendamento->canal_inapp) {
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
                LogService::info("[dispatch_reminders] Notificacao in-app no horário criada");
            }

            if ($agendamento->canal_email && $mailService->isConfigured()) {
                $usuario = $agendamento->usuario;
                if ($usuario && !empty($usuario->email)) {
                    try {
                        $mailService->sendAgendamentoReminder($agendamento, $usuario, 'horario');
                        LogService::info("[dispatch_reminders] Email no horário enviado para {$usuario->email}");
                    } catch (\Throwable $exception) {
                        LogService::error('[dispatch_reminders] Falha ao enviar email no horário', [
                            'erro' => $exception->getMessage(),
                            'agendamento_id' => $agendamento->id,
                        ]);
                    }
                }
            }

            $agendamento->status = 'notificado';
            $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
            $enviouAlgo = true;
        }

        // Salvar alterações se enviou algo
        if ($enviouAlgo) {
            $agendamento->save();
            LogService::info("[dispatch_reminders] Agendamento #{$agendamento->id} atualizado.");
        } else {
            LogService::info(sprintf(
                "[dispatch_reminders] Nenhum lembrete enviado para #%d: aguardando momento correto (antec: %s, horário: %s)",
                $agendamento->id,
                date('d/m/Y H:i', $reminderTimestamp),
                $pagamento->format('d/m/Y H:i')
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
