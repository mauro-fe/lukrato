<?php

/**
 * Script CLI para processar fila de webhooks
 * Executar em background ou via supervisor
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\Billing\WebhookQueueService;
use Application\Controllers\Api\Billing\AsaasWebhookController;

$queue = new WebhookQueueService();

echo "🚀 Iniciando processador de webhooks...\n\n";

// Loop infinito
while (true) {
    try {
        $stats = $queue->getQueueStats();
        echo sprintf(
            "[%s] Fila: %d pendentes | %d processando | %d falhos\n",
            date('H:i:s'),
            $stats['pending'],
            $stats['processing'],
            $stats['failed']
        );

        // Processar próximo webhook
        $job = $queue->processNext();

        if ($job) {
            $payload = $job['payload'];
            $data = $job['data'];

            echo "   → Processando webhook: " . ($data['data']['event'] ?? 'unknown') . "\n";

            try {
                // Processar webhook
                $controller = new AsaasWebhookController();
                // Simular processamento
                // (normalmente você chamaria método específico)

                // Marcar como processado
                $queue->markAsProcessed($payload);
                echo "   ✅ Webhook processado com sucesso\n";
            } catch (\Throwable $e) {
                echo "   ❌ Erro ao processar: " . $e->getMessage() . "\n";
                $queue->markAsFailed($payload);
            }
        } else {
            // Nenhum webhook na fila, aguardar
            sleep(1);
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
