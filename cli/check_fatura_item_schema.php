<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== COLUNAS DA TABELA faturas_cartao_itens ===\n";
$cols = DB::select('SHOW COLUMNS FROM faturas_cartao_itens');
foreach ($cols as $c) {
    echo "  - {$c->Field} ({$c->Type})" . ($c->Null === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
}

echo "\n=== VERIFICAR SE lancamento_id EXISTE ===\n";
$hasLancamentoId = false;
foreach ($cols as $c) {
    if ($c->Field === 'lancamento_id') {
        $hasLancamentoId = true;
        break;
    }
}
echo $hasLancamentoId ? "✅ lancamento_id existe\n" : "❌ lancamento_id NÃO existe - precisa criar migration\n";
