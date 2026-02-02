<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "=== Análise detalhada dos lançamentos ===\n\n";

// Por tipo
echo "--- Por tipo ---\n";
$receitas = Lancamento::where('tipo', 'receita')->count();
$despesas = Lancamento::where('tipo', 'despesa')->count();
echo "Receitas: $receitas\n";
echo "Despesas: $despesas\n\n";

// Por cartão
echo "--- Por cartão ---\n";
$semCartao = Lancamento::whereNull('cartao_credito_id')->count();
$comCartao = Lancamento::whereNotNull('cartao_credito_id')->count();
echo "SEM cartao_credito_id: $semCartao\n";
echo "COM cartao_credito_id: $comCartao\n\n";

// Amostra de lançamentos COM cartão
echo "--- Amostra de lançamentos COM cartao_credito_id ---\n";
$amostra = Lancamento::whereNotNull('cartao_credito_id')
    ->select('id', 'descricao', 'tipo', 'valor', 'cartao_credito_id', 'conta_id', 'pago', 'afeta_caixa')
    ->limit(15)
    ->get();

foreach ($amostra as $l) {
    echo sprintf(
        "ID: %d | %s | %s | R$ %.2f | Cartão: %s | Conta: %s | Pago: %s | AfetaCaixa: %s\n",
        $l->id,
        substr($l->descricao, 0, 30),
        $l->tipo,
        $l->valor,
        $l->cartao_credito_id ?? 'NULL',
        $l->conta_id ?? 'NULL',
        $l->pago ? 'Sim' : 'Não',
        $l->afeta_caixa ? 'Sim' : 'Não'
    );
}

echo "\n--- Amostra de lançamentos SEM cartao_credito_id ---\n";
$amostra2 = Lancamento::whereNull('cartao_credito_id')
    ->select('id', 'descricao', 'tipo', 'valor', 'cartao_credito_id', 'conta_id', 'pago', 'afeta_caixa')
    ->limit(10)
    ->get();

foreach ($amostra2 as $l) {
    echo sprintf(
        "ID: %d | %s | %s | R$ %.2f | Conta: %s | Pago: %s | AfetaCaixa: %s\n",
        $l->id,
        substr($l->descricao, 0, 30),
        $l->tipo,
        $l->valor,
        $l->conta_id ?? 'NULL',
        $l->pago ? 'Sim' : 'Não',
        $l->afeta_caixa ? 'Sim' : 'Não'
    );
}

// Verificar cartões existentes
echo "\n--- Cartões de crédito existentes ---\n";
$cartoes = \Application\Models\CartaoCredito::select('id', 'nome_cartao', 'bandeira')->get();
foreach ($cartoes as $c) {
    $qtd = Lancamento::where('cartao_credito_id', $c->id)->count();
    echo "Cartão #{$c->id} ({$c->nome_cartao} - {$c->bandeira}): $qtd lançamentos\n";
}
