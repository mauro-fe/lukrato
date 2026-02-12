<?php

/**
 * CLI Script: Enviar lembretes de verificação de email
 * 
 * Envia lembrete para contas criadas há mais de 24h que ainda não verificaram o email.
 * 
 * Recomendação de cron (rodar a cada 6 horas):
 * 0 0,6,12,18 * * * php /path/to/lukrato/cli/send_verification_reminders.php
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Auth\EmailVerificationService;
use Application\Services\LogService;

echo "=== Lembretes de Verificação de Email ===" . PHP_EOL;
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

LogService::info('=== [send_verification_reminders] Início ===');

try {
    $service = new EmailVerificationService();
    $users = $service->getUnverifiedForReminder();

    $total = $users->count();
    $sent = 0;
    $failed = 0;

    echo "Contas não verificadas encontradas: {$total}" . PHP_EOL;

    if ($total === 0) {
        echo "Nenhum lembrete para enviar." . PHP_EOL;
        LogService::info('[send_verification_reminders] Nenhum lembrete necessário');
        exit(0);
    }

    foreach ($users as $user) {
        echo "  Enviando lembrete para {$user->email}... ";

        $result = $service->sendReminder($user);

        if ($result) {
            echo "OK" . PHP_EOL;
            $sent++;
        } else {
            echo "FALHOU" . PHP_EOL;
            $failed++;
        }

        // Pausa entre envios para não sobrecarregar SMTP
        usleep(500000); // 500ms
    }

    echo PHP_EOL . "Resultado:" . PHP_EOL;
    echo "  - Total encontradas: {$total}" . PHP_EOL;
    echo "  - Lembretes enviados: {$sent}" . PHP_EOL;
    echo "  - Falhas: {$failed}" . PHP_EOL;

    LogService::info('[send_verification_reminders] Finalizado', [
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
    ]);
} catch (Throwable $e) {
    $msg = "ERRO: " . $e->getMessage();
    echo $msg . PHP_EOL;
    LogService::error('[send_verification_reminders] Exceção', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit(1);
}

echo PHP_EOL . "Concluído!" . PHP_EOL;
