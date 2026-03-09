<?php

/**
 * CLI Script: Limpar transações WhatsApp pendentes expiradas
 *
 * Marca como 'expired' todas as transações em whatsapp_pending
 * com status 'awaiting_confirm' e expires_at <= now().
 *
 * Recomendação de cron (rodar a cada 1h):
 * 0 * * * * php /path/to/lukrato/cli/cleanup_whatsapp_pending.php
 *
 * Use --dry-run para simular sem alterar:
 * php cli/cleanup_whatsapp_pending.php --dry-run
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\PendingWhatsAppTransaction;

$isDryRun = in_array('--dry-run', $argv ?? [], true);

echo "=== Limpeza de Transações WhatsApp Pendentes ===" . PHP_EOL;
echo "Início: " . date('Y-m-d H:i:s') . PHP_EOL;

if ($isDryRun) {
    echo "[DRY-RUN] Nenhuma alteração será feita." . PHP_EOL;
}

try {
    $expired = PendingWhatsAppTransaction::expired()->get();

    $count = $expired->count();
    echo "Encontradas: {$count} transação(ões) expirada(s)." . PHP_EOL;

    if ($count === 0) {
        echo "Nada a fazer." . PHP_EOL;
        exit(0);
    }

    if ($isDryRun) {
        foreach ($expired as $pending) {
            echo "  → [{$pending->id}] {$pending->descricao} — R$ " . number_format($pending->valor, 2, ',', '.') . " (user_id={$pending->user_id}, expirou em {$pending->expires_at})" . PHP_EOL;
        }
        echo "[DRY-RUN] {$count} registro(s) seriam marcados como expired." . PHP_EOL;
        exit(0);
    }

    $updated = 0;
    foreach ($expired as $pending) {
        $pending->markExpired();
        $updated++;
        echo "  ✔ [{$pending->id}] {$pending->descricao} → expired" . PHP_EOL;
    }

    echo "Concluído: {$updated} transação(ões) marcada(s) como expired." . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Fim: " . date('Y-m-d H:i:s') . PHP_EOL;
