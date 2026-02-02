<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "=== Análise do campo afeta_caixa ===\n\n";

$total = Lancamento::count();
$comNull = Lancamento::whereNull('afeta_caixa')->count();
$comTrue = Lancamento::where('afeta_caixa', 1)->count();
$comFalse = Lancamento::where('afeta_caixa', 0)->count();

echo "Total de lançamentos: $total\n";
echo "Com afeta_caixa = NULL: $comNull\n";
echo "Com afeta_caixa = true (1): $comTrue\n";
echo "Com afeta_caixa = false (0): $comFalse\n";

echo "\n=== Lançamentos com afeta_caixa = false (0) ===\n";
$lancFalse = Lancamento::where('afeta_caixa', 0)->limit(10)->get();
foreach ($lancFalse as $l) {
    echo "ID: {$l->id} | {$l->descricao} | Tipo: {$l->tipo} | Valor: {$l->valor} | Cartão ID: " . ($l->cartao_credito_id ?? 'null') . "\n";
}
