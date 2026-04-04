<?php

/**
 * Script CLI para processar fila de importacoes em background.
 * Uso:
 *   php cli/process_importacoes_queue.php          # loop continuo
 *   php cli/process_importacoes_queue.php --once   # processa no maximo um job
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\Importacao\ImportQueueService;

$runOnce = in_array('--once', $argv ?? [], true);
$sleepSeconds = max(1, (int) ($_ENV['IMPORTACOES_QUEUE_SLEEP'] ?? 2));
$queue = new ImportQueueService();

echo "Iniciando worker da fila de importacoes...\n";
if ($runOnce) {
    echo "Modo: --once\n";
}
echo "\n";

do {
    try {
        $result = $queue->processNext();

        if ($result === null) {
            if ($runOnce) {
                echo "Nenhum job pendente na fila.\n";
                break;
            }

            echo sprintf("[%s] Fila ociosa. Aguardando %ds...\n", date('H:i:s'), $sleepSeconds);
            sleep($sleepSeconds);
            continue;
        }

        $job = is_array($result['job'] ?? null) ? $result['job'] : [];
        $jobId = (int) ($job['id'] ?? 0);
        $status = (string) ($result['status'] ?? 'unknown');
        $message = trim((string) ($result['message'] ?? ''));
        $attempts = (int) ($job['attempts'] ?? 0);
        $maxAttempts = (int) ($job['max_attempts'] ?? 0);

        $statusLabel = $status === 'queued'
            ? 'reagendado para nova tentativa'
            : 'finalizado';

        echo sprintf(
            "[%s] Job #%d %s com status %s%s%s\n",
            date('H:i:s'),
            $jobId,
            $statusLabel,
            $status,
            ($attempts > 0 && $maxAttempts > 0) ? sprintf(' (tentativa %d/%d)', $attempts, $maxAttempts) : '',
            $message !== '' ? (': ' . $message) : ''
        );

        if ($runOnce) {
            break;
        }
    } catch (\Throwable $e) {
        echo sprintf("[%s] ERRO: %s\n", date('H:i:s'), $e->getMessage());

        if ($runOnce) {
            break;
        }

        sleep($sleepSeconds);
    }
} while (true);
