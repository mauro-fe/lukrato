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

    $agendamentos = Agendamento::with(['usuario:id,nome,email'])
        ->where('status', 'pendente')
        ->get();

    $count = count($agendamentos);
    LogService::info("[dispatch_reminders] Agendamentos pendentes encontrados: {$count}");

    foreach ($agendamentos as $agendamento) {
        $pagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($agendamento->data_pagamento)
            : new \DateTimeImmutable((string) $agendamento->data_pagamento);

        $leadSeconds = (int) ($agendamento->lembrar_antes_segundos ?? 0);
        $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;

        if ($reminderTimestamp > $windowLimit->getTimestamp() || $reminderTimestamp < $now->getTimestamp()) {
            LogService::info(sprintf(
                "[dispatch_reminders] Ignorado agendamento #%d (%s): fora da janela (%s)",
                $agendamento->id,
                $agendamento->titulo,
                $pagamento->format('d/m/Y H:i')
            ));
            continue;
        }

        LogService::info(sprintf(
            "[dispatch_reminders] Enviando lembrete para agendamento #%d (%s)...",
            $agendamento->id,
            $agendamento->titulo
        ));

        if ($agendamento->canal_inapp) {
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

            LogService::info("[dispatch_reminders] Notificacao in-app criada para user_id={$agendamento->user_id}");
        }

        if ($agendamento->canal_email && $mailService->isConfigured()) {
            $usuario = $agendamento->usuario;
            if ($usuario && !empty($usuario->email)) {
                try {
                    $mailService->sendAgendamentoReminder($agendamento, $usuario);
                    LogService::info(sprintf(
                        "[dispatch_reminders] Email enviado para %s (%s)",
                        $usuario->nome ?? 'usuario sem nome',
                        $usuario->email
                    ));
                } catch (\Throwable $exception) {
                    LogService::error('[dispatch_reminders] Falha ao enviar email', [
                        'erro' => $exception->getMessage(),
                        'agendamento_id' => $agendamento->id,
                    ]);
                }
            } else {
                LogService::warning('[dispatch_reminders] Usuario sem email', [
                    'agendamento_id' => $agendamento->id,
                ]);
            }
        } elseif ($agendamento->canal_email && !$mailService->isConfigured()) {
            LogService::warning('[dispatch_reminders] Canal email habilitado, mas SMTP nao configurado');
        }

        $agendamento->status = 'enviado';
        $agendamento->notificado_em = $now->format('Y-m-d H:i:s');
        $agendamento->save();

        LogService::info("[dispatch_reminders] Agendamento #{$agendamento->id} marcado como 'enviado'.");
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
