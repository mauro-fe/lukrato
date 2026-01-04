<?php

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICANDO ÍNDICES DAS MIGRATIONS ATUALIZADAS ===\n\n";

// Tabelas para verificar
$tables = [
    'lancamentos' => ['idx_lancamentos_data_pagamento', 'idx_lancamentos_cartao', 'idx_lancamentos_parcelamento'],
    'contas' => ['idx_contas_saldo_inicial', 'idx_contas_tipo_conta'],
    'instituicoes_financeiras' => ['idx_instituicoes_tipo', 'idx_instituicoes_ativo'],
    'parcelamentos' => ['idx_parcelamentos_cartao_credito'],
];

foreach ($tables as $table => $expectedIndexes) {
    echo "📊 Tabela: {$table}\n";

    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name LIKE 'idx_%'");

    $foundIndexes = [];
    foreach ($indexes as $idx) {
        if (!in_array($idx->Key_name, $foundIndexes)) {
            $foundIndexes[] = $idx->Key_name;
        }
    }

    foreach ($expectedIndexes as $expectedIndex) {
        if (in_array($expectedIndex, $foundIndexes)) {
            echo "  ✅ {$expectedIndex}\n";
        } else {
            echo "  ❌ {$expectedIndex} (NÃO ENCONTRADO)\n";
        }
    }

    echo "\n";
}

echo "✅ Verificação concluída!\n";
