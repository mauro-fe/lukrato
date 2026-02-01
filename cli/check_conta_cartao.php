<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "=== LANCAMENTOS DE CARTAO PENDENTES COM CONTA_ID ===\n\n";

$pendentes = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNotNull('conta_id')
    ->where('pago', 0)
    ->selectRaw('conta_id, COUNT(*) as total, SUM(valor) as soma')
    ->groupBy('conta_id')
    ->get();

foreach ($pendentes as $p) {
    echo "Conta {$p->conta_id}: {$p->total} lancamentos, R$ " . number_format($p->soma, 2, ',', '.') . "\n";
}

echo "\nTotal: " . $pendentes->sum('total') . " lancamentos\n";
echo "Valor total: R$ " . number_format($pendentes->sum('soma'), 2, ',', '.') . "\n";

echo "\n=== OPCAO DE CORRECAO ===\n";
echo "Esses lancamentos pendentes NAO deveriam ter conta_id.\n";
echo "A conta_id so deve ser definida quando a fatura e paga.\n";
echo "\nPara corrigir, podemos:\n";
echo "1. Remover conta_id dos lancamentos pendentes de cartao\n";
echo "2. Isso fara o saldo voltar ao normal\n";

// Contar afetados
$total = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNotNull('conta_id')
    ->where('pago', 0)
    ->count();

echo "\nLancamentos a corrigir: {$total}\n";
