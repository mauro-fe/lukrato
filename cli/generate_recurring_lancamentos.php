<?php

/**
 * Cron: Estender horizonte de lançamentos recorrentes infinitos.
 *
 * Deve ser executado diariamente (ex: 02:00).
 * Gera lançamentos futuros (com pago=false) para manter um horizonte
 * de 3 meses de recorrências ativas sem data de fim.
 *
 * Uso:
 *   php cli/generate_recurring_lancamentos.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\LancamentoCreationService;

echo "[" . date('Y-m-d H:i:s') . "] Gerando lançamentos recorrentes...\n";

try {
    $service = new LancamentoCreationService();
    $criados = $service->estenderRecorrenciasInfinitas(3);

    echo "[" . date('Y-m-d H:i:s') . "] Concluído: {$criados} lançamentos criados.\n";
} catch (\Throwable $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    error_log("[generate_recurring_lancamentos] " . $e->getMessage());
    exit(1);
}
