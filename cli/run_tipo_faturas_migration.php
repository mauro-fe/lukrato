<?php

/**
 * Script para rodar a migration de tipo em faturas_cartao_itens na produção
 * 
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para o servidor (pasta cli/)
 * 2. Execute via SSH: php cli/run_tipo_faturas_migration.php
 * 3. Remova o arquivo após execução
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "==============================================\n";
echo "MIGRATION: Adicionar campo 'tipo' aos itens de fatura\n";
echo "==============================================\n\n";

try {
    // Verificar se já existe a coluna
    $hasColumn = Capsule::schema()->hasColumn('faturas_cartao_itens', 'tipo');

    if ($hasColumn) {
        echo "⚠️ Coluna 'tipo' já existe na tabela faturas_cartao_itens\n";
        echo "Nenhuma alteração necessária.\n";
    } else {
        echo "Adicionando coluna 'tipo'...\n";

        Capsule::schema()->table('faturas_cartao_itens', function ($table) {
            // tipo: 'despesa' (padrão) ou 'estorno'
            $table->string('tipo', 20)->default('despesa')->after('valor');
        });

        echo "✅ Coluna 'tipo' adicionada com sucesso!\n";

        // Contar registros
        $total = Capsule::table('faturas_cartao_itens')->count();
        echo "📊 Total de itens de fatura existentes: {$total}\n";
        echo "   (Todos marcados como 'despesa' por padrão)\n";
    }

    echo "\n==============================================\n";
    echo "✅ MIGRATION CONCLUÍDA!\n";
    echo "==============================================\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
