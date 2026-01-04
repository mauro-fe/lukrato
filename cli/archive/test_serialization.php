<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\InstituicaoFinanceira;
use Application\Models\Conta;

echo "=== TESTE DE SERIALIZAÇÃO ===\n\n";

// Testar instituição
$inst = InstituicaoFinanceira::find(1);
echo "Instituição Nubank:\n";
echo json_encode($inst->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Testar conta com instituição
$conta = Conta::with('instituicaoFinanceira')->where('nome', 'LIKE', '%NUBANK%')->first();
if ($conta) {
    echo "Conta NUBANK PF:\n";
    echo json_encode($conta->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
