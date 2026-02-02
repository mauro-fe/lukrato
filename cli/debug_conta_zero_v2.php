<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$lanc = DB::table('lancamentos')
    ->where('user_id', 1)
    ->where('conta_id', 0)
    ->get();

echo "=== LANÇAMENTOS COM CONTA_ID = 0 ===" . PHP_EOL;
echo "Total geral: " . count($lanc) . PHP_EOL;

$comCartao = $lanc->filter(fn($l) => $l->cartao_credito_id)->count();
$semCartao = $lanc->filter(fn($l) => !$l->cartao_credito_id)->count();

echo "Com cartao_credito_id: " . $comCartao . PHP_EOL;
echo "Sem cartao_credito_id: " . $semCartao . PHP_EOL;

echo PHP_EOL . "Por cartao_credito_id:" . PHP_EOL;
$porCartao = $lanc->groupBy('cartao_credito_id');
foreach ($porCartao as $cartaoId => $grupo) {
    echo "  Cartão #" . ($cartaoId ?: 'NULL') . ": " . count($grupo) . " lançamentos" . PHP_EOL;
}

echo PHP_EOL . "=== O QUE DEVERIA SER FEITO ===" . PHP_EOL;
echo "O cartão #32 tem conta_id = 21 (Pagar contas)" . PHP_EOL;
echo "Então todos os lançamentos com cartao_credito_id=32 deveriam ter conta_id=21" . PHP_EOL;

// Verificar quantos precisam ser corrigidos
$paraCorrigir = DB::table('lancamentos')
    ->where('user_id', 1)
    ->where('conta_id', 0)
    ->where('cartao_credito_id', 32)
    ->count();

echo PHP_EOL . "Lançamentos para corrigir (conta_id=0, cartao=32): " . $paraCorrigir . PHP_EOL;

// Verificar se há outros cartões com o mesmo problema
echo PHP_EOL . "=== VERIFICAR TODOS OS CARTÕES ===" . PHP_EOL;
$cartoes = DB::table('cartoes_credito')->get();

foreach ($cartoes as $cartao) {
    $contaCorreta = $cartao->conta_id;

    $errados = DB::table('lancamentos')
        ->where('cartao_credito_id', $cartao->id)
        ->where('conta_id', '!=', $contaCorreta)
        ->count();

    if ($errados > 0) {
        echo sprintf(
            "Cartão #%d (%s) - conta correta: #%d - lançamentos com conta ERRADA: %d",
            $cartao->id,
            $cartao->nome_cartao,
            $contaCorreta,
            $errados
        ) . PHP_EOL;
    }
}
