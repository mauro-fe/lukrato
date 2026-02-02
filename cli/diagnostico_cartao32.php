<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DIAGNÓSTICO COMPLETO DO CARTÃO #32 ===" . PHP_EOL;

$cartao = DB::table('cartoes_credito')->where('id', 32)->first();
echo PHP_EOL . "Cartão: " . $cartao->nome_cartao . PHP_EOL;
echo "Conta correta (conta_id do cartão): #" . $cartao->conta_id . PHP_EOL;

$conta21 = DB::table('contas')->where('id', 21)->first();
echo "Conta #21: " . ($conta21->nome ?? 'NÃO EXISTE') . PHP_EOL;

echo PHP_EOL . "=== LANÇAMENTOS DO CARTÃO #32 POR CONTA_ID ===" . PHP_EOL;

$resumo = DB::table('lancamentos')
    ->select('conta_id', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(valor) as total'))
    ->where('cartao_credito_id', 32)
    ->groupBy('conta_id')
    ->get();

foreach ($resumo as $r) {
    $conta = DB::table('contas')->where('id', $r->conta_id)->first();
    $nomeConta = $conta ? $conta->nome : ($r->conta_id == 0 ? 'CONTA ZERADA/INEXISTENTE' : 'NÃO ENCONTRADA');
    $status = ($r->conta_id == $cartao->conta_id) ? '✅ CORRETO' : '❌ ERRADO';

    echo sprintf(
        "conta_id=%d (%s) | %d lançamentos | R$ %s | %s",
        $r->conta_id,
        $nomeConta,
        $r->qtd,
        number_format($r->total, 2, ',', '.'),
        $status
    ) . PHP_EOL;
}

echo PHP_EOL . "=== EXEMPLOS DE LANÇAMENTOS COM CONTA ERRADA ===" . PHP_EOL;

$exemplos = DB::table('lancamentos')
    ->where('cartao_credito_id', 32)
    ->where('conta_id', '!=', 21)
    ->orderBy('data', 'desc')
    ->limit(10)
    ->get();

foreach ($exemplos as $l) {
    echo sprintf(
        "#%-5d %s | conta_id=%d | R$ %8.2f | afeta=%s | %s",
        $l->id,
        $l->data,
        $l->conta_id,
        $l->valor,
        $l->afeta_caixa ? 'S' : 'N',
        substr($l->descricao, 0, 30)
    ) . PHP_EOL;
}

// Verificar quantos são de cada tipo
echo PHP_EOL . "=== RESUMO GERAL ===" . PHP_EOL;
$total = DB::table('lancamentos')->where('cartao_credito_id', 32)->count();
$corretos = DB::table('lancamentos')->where('cartao_credito_id', 32)->where('conta_id', 21)->count();
$errados = $total - $corretos;

echo "Total de lançamentos do cartão #32: " . $total . PHP_EOL;
echo "Com conta_id correto (21): " . $corretos . PHP_EOL;
echo "Com conta_id ERRADO: " . $errados . PHP_EOL;
