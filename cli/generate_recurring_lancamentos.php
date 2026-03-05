<?php

/**
 * Cron: Gerar lançamentos recorrentes vencidos.
 *
 * Deve ser executado diariamente (ex: 02:00).
 * Gera apenas o próximo lançamento de cada recorrência quando a data
 * do ciclo chega, sempre como pendente.
 *
 * Uso:
 *   php cli/generate_recurring_lancamentos.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\Lancamento\LancamentoCreationService;

echo "[" . date('Y-m-d H:i:s') . "] Gerando lançamentos recorrentes...\n";

try {
    $service = new LancamentoCreationService();
    $criados = $service->estenderRecorrenciasInfinitas();

    echo "[" . date('Y-m-d H:i:s') . "] Concluído: {$criados} lançamentos criados.\n";
} catch (\Throwable $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    error_log("[generate_recurring_lancamentos] " . $e->getMessage());
    exit(1);
}
