<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;

$lock = new SchedulerExecutionLock();
$runner = new SchedulerTaskRunner();

try {
    $lock->acquire('scheduler');
    $result = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_SCHEDULED_CAMPAIGNS);

    if (($result['success'] ?? false) === false) {
        fwrite(STDERR, '[dispatch_scheduled_campaigns] ' . ($result['error'] ?? 'Falha desconhecida') . PHP_EOL);
        exit(1);
    }

    $stats = $result['result'];

    echo '[dispatch_scheduled_campaigns] '
        . "processadas={$stats['processed']} "
        . "enviadas={$stats['sent']} "
        . "falhas={$stats['failed']}" . PHP_EOL;
} finally {
    $lock->release();
}
