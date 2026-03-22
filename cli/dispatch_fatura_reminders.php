<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;

echo '[' . date('Y-m-d H:i:s') . "] Disparando lembretes de fatura..." . PHP_EOL;

$lock = new SchedulerExecutionLock();
$runner = new SchedulerTaskRunner();

try {
    $lock->acquire('scheduler');

    $result = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_FATURA_REMINDERS);

    if (($result['success'] ?? false) !== true) {
        throw new RuntimeException((string) ($result['error'] ?? 'Falha ao disparar lembretes de fatura.'));
    }

    $stats = $result['result'];

    echo '[' . date('Y-m-d H:i:s') . '] Concluido: '
        . "processados={$stats['processed']} "
        . "enviados={$stats['sent']} "
        . "ignorados={$stats['ignored']} "
        . "erros={$stats['errors']}" . PHP_EOL;
} catch (\Throwable $e) {
    LogService::error('[dispatch_fatura_reminders] Erro fatal', [
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);
    echo '[ERRO] ' . $e->getMessage() . PHP_EOL;
    exit(1);
} finally {
    $lock->release();
}
