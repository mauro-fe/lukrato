<?php

/**
 * CLI: Expira PendingAiActions que passaram do prazo.
 * Ideal para rodar via cron a cada 5-10 minutos.
 *
 * Uso: php cli/expire_pending_ai_actions.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\PendingAiAction;

echo "\n╔════════════════════════════════════════════╗\n";
echo "║  Expire Pending AI Actions                ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

try {
    $expired = PendingAiAction::where('status', 'awaiting_confirm')
        ->where('expires_at', '<', now())
        ->update(['status' => 'expired']);

    echo "✅ {$expired} ação(ões) pendente(s) expirada(s).\n";
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
    exit(1);
}
