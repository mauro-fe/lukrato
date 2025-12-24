<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Conta;

echo "=== Teste NUBANK PF ===\n\n";

$conta = Conta::with('instituicaoFinanceira')
    ->where('nome', 'LIKE', '%NUBANK%')
    ->orWhere('nome', 'LIKE', '%Nubank%')
    ->first();

if ($conta) {
    echo "Conta: " . $conta->nome . "\n";
    echo "Instituição ID: " . ($conta->instituicao_financeira_id ?? 'NULL') . "\n";
    
    $inst = $conta->instituicaoFinanceira;
    if ($inst) {
        echo "Instituição: " . $inst->nome . "\n";
        echo "Logo URL: " . $inst->logo_url . "\n";
        echo "Logo Path: " . $inst->logo_path . "\n";
        echo "Cor Primária: " . $inst->cor_primaria . "\n";
        echo "\nJSON completo:\n";
        echo json_encode($conta->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Sem instituição vinculada\n";
        echo "\nDados da conta:\n";
        echo json_encode($conta->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    echo "Conta Nubank não encontrada\n";
    echo "\nBuscando todas as contas:\n";
    $contas = Conta::orderBy('id', 'desc')->limit(3)->get();
    foreach ($contas as $c) {
        echo "- " . $c->nome . " (ID: {$c->id}, Inst: " . ($c->instituicao_financeira_id ?? 'NULL') . ")\n";
    }
}
