<?php

/**
 * CLI Script: Processar assinaturas expiradas
 * 
 * Este script deve ser executado periodicamente via cron job.
 * Exemplo de cron (rodar a cada hora):
 * 0 * * * * php /path/to/lukrato/cli/process_expired_subscriptions.php
 * 
 * Ou rodar diariamente às 8h:
 * 0 8 * * * php /path/to/lukrato/cli/process_expired_subscriptions.php
 */

require __DIR__ . '/../bootstrap.php';

use Application\Services\SubscriptionExpirationService;
use Application\Services\LogService;

echo "=== Processando Assinaturas Expiradas ===" . PHP_EOL;
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

try {
    $service = new SubscriptionExpirationService();
    $stats = $service->processExpiredSubscriptions();

    echo PHP_EOL . "Resultado:" . PHP_EOL;
    echo "  - Assinaturas verificadas: {$stats['checked']}" . PHP_EOL;
    echo "  - Usuários notificados: {$stats['notified']}" . PHP_EOL;
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
    echo "Processamento concluído com sucesso!" . PHP_EOL;

    // Exit code baseado em erros
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
}
