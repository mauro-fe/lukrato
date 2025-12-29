<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

$userId = 22;

echo "=== Verificando lançamentos de cartão ===\n\n";

// Buscar lançamentos com cartão
$lancamentos = Lancamento::where('user_id', $userId)
    ->whereNotNull('cartao_credito_id')
    ->orderBy('data', 'desc')
    ->limit(10)
    ->get();

echo "Total de lançamentos com cartão: " . $lancamentos->count() . "\n\n";

foreach ($lancamentos as $lanc) {
    echo "ID: {$lanc->id}\n";
    echo "Descrição: {$lanc->descricao}\n";
    echo "Valor: R$ {$lanc->valor}\n";
    echo "Data: {$lanc->data}\n";
    echo "Tipo: {$lanc->tipo}\n";
    echo "Cartão ID: {$lanc->cartao_credito_id}\n";
    echo "---\n";
}

// Verificar dezembro de 2025
echo "\n=== Lançamentos de cartão em Dezembro/2025 ===\n\n";

$dez2025 = Lancamento::where('user_id', $userId)
    ->whereNotNull('cartao_credito_id')
    ->where('tipo', 'despesa')
    ->whereBetween('data', ['2025-12-01', '2025-12-31'])
    ->get();

echo "Total: " . $dez2025->count() . "\n";
echo "Soma: R$ " . $dez2025->sum('valor') . "\n";
