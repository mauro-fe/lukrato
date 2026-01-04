<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;

$userId = getenv('USER_ID') ?: 1;

echo "Usuario: {$userId}\n\n";

// 1) Agregado: soma de lançamentos não pagos por cartao_credito_id
$agregado = Lancamento::selectRaw('cartao_credito_id, SUM(valor) as total_nao_pago, COUNT(*) as qtd')
    ->where('user_id', $userId)
    ->where('pago', false)
    ->whereNotNull('cartao_credito_id')
    ->groupBy('cartao_credito_id')
    ->orderByRaw('SUM(valor) DESC')
    ->get();

if ($agregado->isEmpty()) {
    echo "Nenhum lançamento não pago associado a cartões encontrado.\n";
    exit;
}

echo "Totais não pagos por cartão:\n";
foreach ($agregado as $row) {
    $cartao = CartaoCredito::find($row->cartao_credito_id);
    $nome = $cartao ? ($cartao->nome_cartao . ' (id=' . $cartao->id . ')') : 'Cartao ID ' . $row->cartao_credito_id;
    printf("- %s: %s (qtd %d)\n", $nome, number_format($row->total_nao_pago, 2, ',', '.'), $row->qtd);
}

// Prompt for specific cartao id if provided via argv
$cartaoId = $argv[1] ?? null;
if (!$cartaoId) {
    echo "\nPara ver detalhes de um cartão, rode: php cli/debug_cartao_limites.php <cartao_credito_id>\n";
    exit;
}

$cartaoId = (int)$cartaoId;
$cartao = CartaoCredito::find($cartaoId);
if (!$cartao) {
    echo "Cartão id={$cartaoId} não encontrado.\n";
    exit;
}

echo "\nDetalhes do cartão: {$cartao->nome_cartao} (id={$cartao->id})\n";

$lancamentos = Lancamento::where('user_id', $userId)
    ->where('cartao_credito_id', $cartaoId)
    ->where('pago', false)
    ->orderBy('data')
    ->get();

if ($lancamentos->isEmpty()) {
    echo "Nenhum lançamento não pago para este cartão.\n";
    exit;
}

echo "\nLançamentos não pagos:\n";
foreach ($lancamentos as $l) {
    printf("- id:%d | %s | %s | %s | parcelamento_id:%s\n", $l->id, $l->data->format('Y-m-d'), number_format($l->valor, 2, ',', '.'), $l->descricao, $l->parcelamento_id ?? 'NULL');
}

echo "\nSoma: " . number_format($lancamentos->sum('valor'), 2, ',', '.') . "\n";
