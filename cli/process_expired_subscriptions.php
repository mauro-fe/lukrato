<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;

echo "=== Processando Assinaturas Expiradas ===" . PHP_EOL;
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

$lock = new SchedulerExecutionLock();
$runner = new SchedulerTaskRunner();

try {
    $lock->acquire('scheduler');

    $result = $runner->runTask(SchedulerTaskRunner::TASK_PROCESS_EXPIRED_SUBSCRIPTIONS);

    if (($result['success'] ?? false) !== true) {
        throw new RuntimeException((string) ($result['error'] ?? 'Falha ao processar assinaturas expiradas.'));
    }

    $stats = $result['result'];

    echo PHP_EOL . "Resultado:" . PHP_EOL;
    echo "  - Assinaturas verificadas: {$stats['checked']}" . PHP_EOL;
    echo "  - Usuarios notificados: {$stats['notified']}" . PHP_EOL;
    echo "  - Assinaturas bloqueadas: {$stats['blocked']}" . PHP_EOL;
    echo "  - Emails enviados: {$stats['emails_sent']}" . PHP_EOL;

    if (!empty($stats['errors'])) {
        echo PHP_EOL . "Erros encontrados: " . count($stats['errors']) . PHP_EOL;
        foreach ($stats['errors'] as $error) {
            if (isset($error['general'])) {
                echo "  - Erro geral: {$error['general']}" . PHP_EOL;
            } else {
                echo "  - Assinatura #{$error['subscription_id']} (User #{$error['user_id']}): {$error['error']}" . PHP_EOL;
            }
        }
    }

    echo PHP_EOL . str_repeat('-', 50) . PHP_EOL;
    echo "Processamento concluido com sucesso!" . PHP_EOL;

    exit(empty($stats['errors']) ? 0 : 1);
} catch (Throwable $e) {
    echo PHP_EOL . "ERRO FATAL: " . $e->getMessage() . PHP_EOL;
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;

    LogService::error('[CLI] Erro fatal ao processar assinaturas expiradas', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    exit(2);
} finally {
    $lock->release();
}
