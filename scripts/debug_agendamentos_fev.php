<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\FaturaCartaoItem;

$userId = 1; // Ajuste se necessário

$start = '2026-02-01';
$end = '2026-02-28';

echo "=== AGENDAMENTOS FEVEREIRO 2026 (user $userId) ===\n\n";

// Query igual à do dashboard
$agendamentos = Agendamento::where('user_id', $userId)
    ->whereIn('status', ['pendente', 'notificado'])
    ->whereNull('concluido_em')
    ->whereBetween('data_pagamento', [$start . ' 00:00:00', $end . ' 23:59:59'])
    ->get();

$totalPagar = 0;
$totalReceber = 0;
$countPagar = 0;
$countReceber = 0;

foreach ($agendamentos as $ag) {
    $valor = ($ag->valor_centavos ?? 0) / 100;
    $tipo = $ag->tipo;
    
    echo "- {$ag->titulo}: R$ " . number_format($valor, 2, ',', '.') . " | Data: {$ag->data_pagamento} | Tipo: {$tipo}\n";
    
    if (strtolower($tipo) === 'receita') {
        $totalReceber += $valor;
        $countReceber++;
    } else {
        $totalPagar += $valor;
        $countPagar++;
    }
}

echo "\n--- Totais Agendamentos ---\n";
echo "Despesas: {$countPagar} agendamentos = R$ " . number_format($totalPagar, 2, ',', '.') . "\n";
echo "Receitas: {$countReceber} agendamentos = R$ " . number_format($totalReceber, 2, ',', '.') . "\n";

echo "\n=== FATURAS CARTÃO FEVEREIRO 2026 ===\n\n";

$faturas = FaturaCartaoItem::where('user_id', $userId)
    ->where('pago', false)
    ->whereYear('data_vencimento', 2026)
    ->whereMonth('data_vencimento', 2)
    ->get();

$totalFaturas = 0;
foreach ($faturas as $f) {
    echo "- {$f->descricao}: R$ " . number_format($f->valor, 2, ',', '.') . " | Vencimento: {$f->data_vencimento}\n";
    $totalFaturas += $f->valor;
}

echo "\n--- Total Faturas ---\n";
echo count($faturas) . " itens = R$ " . number_format($totalFaturas, 2, ',', '.') . "\n";

echo "\n=== TOTAL A PAGAR PREVISTO ===\n";
$total = $totalPagar + $totalFaturas;
echo "R$ " . number_format($total, 2, ',', '.') . " ({$countPagar} agendamentos + " . count($faturas) . " itens fatura)\n";
