<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;

echo "[" . date('Y-m-d H:i:s') . "] Gerando lancamentos recorrentes..." . PHP_EOL;

$lock = new SchedulerExecutionLock();
$runner = new SchedulerTaskRunner();

try {
    $lock->acquire('scheduler');

    $result = $runner->runTask(SchedulerTaskRunner::TASK_GENERATE_RECURRING_LANCAMENTOS);

    if (($result['success'] ?? false) !== true) {
        throw new RuntimeException((string) ($result['error'] ?? 'Falha ao gerar lancamentos recorrentes.'));
    }

    $criados = (int) ($result['result']['created'] ?? 0);

    echo "[" . date('Y-m-d H:i:s') . "] Concluido: {$criados} lancamentos criados." . PHP_EOL;
} catch (\Throwable $e) {
    echo "[ERRO] " . $e->getMessage() . PHP_EOL;
    LogService::error('[generate_recurring_lancamentos] Erro fatal', [
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);
    exit(1);
} finally {
    $lock->release();
}
