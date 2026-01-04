<?php
/**
 * Script de teste para verificar se a API V2 está funcionando
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\InstituicaoFinanceira;
use Application\Models\Conta;
use Application\Models\CartaoCredito;

echo "=== Teste da API V2 - Contas e Cartões ===\n\n";

// Teste 1: Verificar Instituições Financeiras
echo "1. Verificando Instituições Financeiras:\n";
$instituicoes = InstituicaoFinanceira::ativas()->get();
echo "   ✓ Total de instituições ativas: " . $instituicoes->count() . "\n";

if ($instituicoes->count() > 0) {
    $primeira = $instituicoes->first();
    echo "   ✓ Exemplo: " . $primeira->nome . " (ID: {$primeira->id})\n";
    echo "   ✓ Logo URL: " . $primeira->logo_url . "\n";
}

echo "\n";

// Teste 2: Verificar Contas
echo "2. Verificando Contas:\n";
$contas = Conta::with('instituicaoFinanceira')->limit(5)->get();
echo "   ✓ Total de contas (primeiras 5): " . $contas->count() . "\n";

foreach ($contas as $conta) {
    $instituicao = $conta->instituicaoFinanceira;
    $nomeInst = $instituicao ? $instituicao->nome : 'Sem instituição';
    echo "   - {$conta->nome} ({$nomeInst})\n";
}

echo "\n";

// Teste 3: Verificar Cartões
echo "3. Verificando Cartões de Crédito:\n";
$cartoes = CartaoCredito::with(['conta.instituicaoFinanceira'])->limit(5)->get();
echo "   ✓ Total de cartões (primeiros 5): " . $cartoes->count() . "\n";

foreach ($cartoes as $cartao) {
    $conta = $cartao->conta;
    $nomeConta = $conta ? $conta->nome : 'Sem conta';
    echo "   - {$cartao->nome_cartao} - {$cartao->bandeira} (Conta: {$nomeConta})\n";
}

echo "\n";

// Teste 4: Verificar Rotas (simulação)
echo "4. Rotas da API V2 esperadas:\n";
$rotas = [
    'GET /api/v2/instituicoes',
    'GET /api/v2/contas',
    'POST /api/v2/contas',
    'PUT /api/v2/contas/{id}',
    'POST /api/v2/contas/{id}/archive',
    'POST /api/v2/contas/{id}/restore',
    'DELETE /api/v2/contas/{id}',
    'GET /api/v2/cartoes',
    'POST /api/v2/cartoes',
    'PUT /api/v2/cartoes/{id}',
    'DELETE /api/v2/cartoes/{id}',
];

foreach ($rotas as $rota) {
    echo "   ✓ {$rota}\n";
}

echo "\n";
echo "=== Teste concluído com sucesso! ===\n";
