<?php

/**
 * CLI Script: Limpar conversas de IA antigas
 *
 * Remove conversas (e suas mensagens via CASCADE) que não são
 * atualizadas há mais de N dias (padrão: 7).
 *
 * Recomendação de cron (rodar 1x por dia, de madrugada):
 * 0 4 * * * php /path/to/lukrato/cli/cleanup_ai_conversations.php
 *
 * Opções:
 *   --dry-run    Simula sem excluir
 *   --days=N     Conversas mais antigas que N dias (padrão: 7)
 *
 * Exemplos:
 *   php cli/cleanup_ai_conversations.php --dry-run
 *   php cli/cleanup_ai_conversations.php --days=30
 *   php cli/cleanup_ai_conversations.php --days=14 --dry-run
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\AiConversation;
use Application\Services\Infrastructure\LogService;

// ── Parse args ──────────────────────────────────────────────
$isDryRun = in_array('--dry-run', $argv ?? [], true);
$days = 7;

foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--days=(\d+)$/', $arg, $m)) {
        $days = max(1, (int) $m[1]);
    }
}

$cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

echo "=== Limpeza de Conversas de IA ===" . PHP_EOL;
if ($isDryRun) {
    echo "*** MODO SIMULAÇÃO (--dry-run) — nenhuma conversa será removida ***" . PHP_EOL;
}
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Conversas sem atividade há mais de {$days} dias (antes de {$cutoff})" . PHP_EOL;
echo str_repeat('-', 55) . PHP_EOL;

LogService::info('=== [cleanup_ai_conversations] Início ===', [
    'dry_run' => $isDryRun,
    'days'    => $days,
    'cutoff'  => $cutoff,
]);

try {
    $conversations = AiConversation::where('updated_at', '<', $cutoff)
        ->orderBy('updated_at')
        ->get();

    $total   = $conversations->count();
    $removed = 0;
    $failed  = 0;

    echo "Conversas encontradas: {$total}" . PHP_EOL;

    if ($total === 0) {
        echo "Nenhuma conversa para remover." . PHP_EOL;
        LogService::info('[cleanup_ai_conversations] Nenhuma conversa expirada encontrada');
        exit(0);
    }

    foreach ($conversations as $conv) {
        $msgCount = $conv->messages()->count();
        $daysAgo  = $conv->updated_at ? $conv->updated_at->diffInDays(now()) : '?';
        $titulo   = $conv->titulo ? mb_substr($conv->titulo, 0, 40) : '(sem título)';

        echo "  [#{$conv->id}] user={$conv->user_id} msgs={$msgCount} \"{$titulo}\" ({$daysAgo}d atrás)";

        if ($isDryRun) {
            echo " — SIMULAÇÃO (não removida)" . PHP_EOL;
            $removed++;
            continue;
        }

        try {
            $conv->delete(); // Cascade via FK remove ai_chat_messages
            $removed++;
            echo " — REMOVIDA ✓" . PHP_EOL;
        } catch (\Throwable $e) {
            $failed++;
            echo " — ERRO: {$e->getMessage()}" . PHP_EOL;
            LogService::error('[cleanup_ai_conversations] Falha ao remover', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    echo str_repeat('-', 55) . PHP_EOL;
    echo "Resumo: {$total} encontradas, {$removed} removidas, {$failed} erros" . PHP_EOL;

    LogService::info('[cleanup_ai_conversations] Concluído', [
        'total'   => $total,
        'removed' => $removed,
        'failed'  => $failed,
        'dry_run' => $isDryRun,
    ]);
} catch (\Throwable $e) {
    echo "ERRO FATAL: {$e->getMessage()}" . PHP_EOL;
    LogService::error('[cleanup_ai_conversations] Erro fatal', [
        'error' => $e->getMessage(),
    ]);
    exit(1);
}
