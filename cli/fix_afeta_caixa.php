<?php

/**
 * One-time fix: sync afeta_caixa with pago status for existing lancamentos.
 *
 * Lançamentos pendentes (pago=0) que têm afeta_caixa=1 devem ser corrigidos
 * para afeta_caixa=0, desde que não sejam de origem cartão de crédito ou
 * pagamento de fatura (esses têm lógica própria).
 *
 * Usage: php cli/fix_afeta_caixa.php [--dry-run]
 */

require_once __DIR__ . '/../bootstrap.php';

$dryRun = in_array('--dry-run', $argv ?? []);

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Fix afeta_caixa sync with pago status ===\n";
echo $dryRun ? "[DRY RUN - no changes will be made]\n\n" : "\n";

// 1. Find lancamentos where pago=0 but afeta_caixa=1 (or NULL treated as true)
$pendentes = DB::table('lancamentos')
    ->where('pago', 0)
    ->where(function ($q) {
        $q->where('afeta_caixa', 1)
            ->orWhereNull('afeta_caixa');
    })
    ->whereNotIn('origem_tipo', ['cartao_credito', 'pagamento_fatura'])
    ->count();

echo "Lançamentos pendentes com afeta_caixa incorreto: {$pendentes}\n";

if ($pendentes > 0 && !$dryRun) {
    $updated = DB::table('lancamentos')
        ->where('pago', 0)
        ->where(function ($q) {
            $q->where('afeta_caixa', 1)
                ->orWhereNull('afeta_caixa');
        })
        ->whereNotIn('origem_tipo', ['cartao_credito', 'pagamento_fatura'])
        ->update(['afeta_caixa' => 0]);

    echo "✅ Corrigidos: {$updated} lançamentos\n";
}

// 2. Verify: check lancamentos where pago=1 but afeta_caixa=0 (should be true)
$pagosErrados = DB::table('lancamentos')
    ->where('pago', 1)
    ->where('afeta_caixa', 0)
    ->whereNotIn('origem_tipo', ['cartao_credito', 'pagamento_fatura'])
    ->count();

echo "\nLançamentos pagos com afeta_caixa=0 (exceto cartão): {$pagosErrados}\n";

if ($pagosErrados > 0 && !$dryRun) {
    $updated2 = DB::table('lancamentos')
        ->where('pago', 1)
        ->where('afeta_caixa', 0)
        ->whereNotIn('origem_tipo', ['cartao_credito', 'pagamento_fatura'])
        ->update(['afeta_caixa' => 1]);

    echo "✅ Corrigidos: {$updated2} lançamentos pagos\n";
}

echo "\n=== Done ===\n";
