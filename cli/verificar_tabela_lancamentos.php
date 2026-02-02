<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ESTRUTURA DA TABELA LANCAMENTOS ===\n\n";

$cols = DB::select('DESCRIBE lancamentos');

echo str_pad('Campo', 25) . " | " . str_pad('Tipo', 25) . " | NULL? | Default\n";
echo str_repeat('-', 80) . "\n";

foreach ($cols as $c) {
    echo str_pad($c->Field, 25) . " | " . str_pad($c->Type, 25) . " | " . str_pad($c->Null, 5) . " | " . ($c->Default ?? 'NULL') . "\n";
}

echo "\n=== VERIFICAÇÃO DE CAMPOS IMPORTANTES ===\n\n";

$camposImportantes = [
    'afeta_caixa' => 'Controla se afeta saldo da conta',
    'afeta_competencia' => 'Controla se aparece nos relatórios de competência',
    'cartao_credito_id' => 'Vincula ao cartão de crédito',
    'pago' => 'Indica se foi pago',
    'data_pagamento' => 'Data do pagamento efetivo',
    'data_competencia' => 'Data para fins de competência',
    'conta_id' => 'Conta vinculada',
];

foreach ($camposImportantes as $campo => $descricao) {
    $existe = false;
    foreach ($cols as $c) {
        if ($c->Field === $campo) {
            $existe = true;
            echo "✅ {$campo}: {$descricao}\n";
            echo "   Tipo: {$c->Type} | Default: " . ($c->Default ?? 'NULL') . "\n\n";
            break;
        }
    }
    if (!$existe) {
        echo "❌ {$campo}: CAMPO NÃO EXISTE!\n\n";
    }
}

// Verificar alguns registros de exemplo
echo "=== AMOSTRA DE DADOS ===\n\n";

$amostra = DB::table('lancamentos')
    ->select('id', 'descricao', 'tipo', 'valor', 'pago', 'afeta_caixa', 'cartao_credito_id', 'conta_id')
    ->limit(10)
    ->orderBy('id', 'desc')
    ->get();

echo "Últimos 10 lançamentos:\n";
foreach ($amostra as $l) {
    $cartao = $l->cartao_credito_id ? "Cartão #{$l->cartao_credito_id}" : "Sem cartão";
    $pago = $l->pago ? "PAGO" : "PEND";
    $afeta = $l->afeta_caixa ? "Afeta" : "NãoAfeta";
    if ($l->afeta_caixa === null) $afeta = "NULL";

    echo "#{$l->id} | " . substr($l->descricao, 0, 20) . " | {$l->tipo} | R$ " . number_format($l->valor, 2) . " | {$pago} | {$afeta} | {$cartao}\n";
}
