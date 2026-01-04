<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Conta;

echo "=== Teste de Relação Conta x Instituição ===\n\n";

$conta = Conta::with('instituicaoFinanceira')->first();

if ($conta) {
    echo "Conta: " . $conta->nome . "\n";
    echo "Instituição ID: " . $conta->instituicao_financeira_id . "\n";
    
    $inst = $conta->instituicaoFinanceira;
    if ($inst) {
        echo "Instituição: " . $inst->nome . "\n";
        echo "Logo URL: " . $inst->logo_url . "\n";
        echo "Cor Primária: " . $inst->cor_primaria . "\n";
        echo "\nJSON da conta:\n";
        echo json_encode($conta->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Sem instituição vinculada\n";
    }
} else {
    echo "Nenhuma conta encontrada\n";
}
