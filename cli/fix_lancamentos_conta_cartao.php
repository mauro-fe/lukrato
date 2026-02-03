<?php

/**
 * FIX: Corrigir conta_id dos lançamentos de cartão de crédito
 * 
 * Os lançamentos com cartao_credito_id devem ter conta_id igual ao conta_id do cartão
 * (que é a conta de pagamento, ex: "Pagar contas")
 * 
 * O problema atual: lançamentos estão com a conta de origem do dinheiro, não a conta do cartão
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$dryRun = !in_array('--execute', $argv);

if ($dryRun) {
    echo "=== MODO SIMULAÇÃO (dry run) ===" . PHP_EOL;
    echo "Use --execute para aplicar as correções" . PHP_EOL . PHP_EOL;
} else {
    echo "=== MODO EXECUÇÃO ===" . PHP_EOL . PHP_EOL;
}

// Buscar todos os cartões
$cartoes = DB::table('cartoes_credito')->get();

$totalCorrigidos = 0;
$totalErrados = 0;

foreach ($cartoes as $cartao) {
    $contaCorreta = $cartao->conta_id;

    // Buscar lançamentos com conta errada
    $errados = DB::table('lancamentos')
        ->where('cartao_credito_id', $cartao->id)
        ->where('conta_id', '!=', $contaCorreta)
        ->get();

    if ($errados->count() > 0) {
        echo sprintf(
            "Cartão #%d (%s) - User #%d",
            $cartao->id,
            $cartao->nome_cartao,
            $cartao->user_id
        ) . PHP_EOL;
        echo "  Conta correta: #" . $contaCorreta . PHP_EOL;
        echo "  Lançamentos com conta ERRADA: " . $errados->count() . PHP_EOL;

        // Agrupar por conta_id atual
        $porConta = $errados->groupBy('conta_id');
        foreach ($porConta as $contaId => $grupo) {
            $conta = DB::table('contas')->where('id', $contaId)->first();
            $nomeConta = $conta ? $conta->nome : ($contaId == 0 ? 'ZERADA' : 'INEXISTENTE');
            echo "    conta_id=$contaId ($nomeConta): " . count($grupo) . " lançamentos" . PHP_EOL;
        }

        $totalErrados += $errados->count();

        if (!$dryRun) {
            // Corrigir
            $updated = DB::table('lancamentos')
                ->where('cartao_credito_id', $cartao->id)
                ->where('conta_id', '!=', $contaCorreta)
                ->update(['conta_id' => $contaCorreta]);

            echo "  ✅ Corrigidos: " . $updated . " lançamentos" . PHP_EOL;
            $totalCorrigidos += $updated;
        }

        echo PHP_EOL;
    }
}

echo str_repeat('=', 50) . PHP_EOL;
echo "Total de lançamentos com conta errada: " . $totalErrados . PHP_EOL;

if (!$dryRun) {
    echo "Total de lançamentos corrigidos: " . $totalCorrigidos . PHP_EOL;
} else {
    echo PHP_EOL . "Execute com --execute para corrigir" . PHP_EOL;
}