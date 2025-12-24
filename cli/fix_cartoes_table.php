<?php

require dirname(__DIR__) . '/bootstrap.php';
require BASE_PATH . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DIAGNÓSTICO E CORREÇÃO TABELA CARTÕES ===\n\n";

// Verificar se a tabela cartoes_credito já existe
if (DB::schema()->hasTable('cartoes_credito')) {
    echo "✓ Tabela cartoes_credito já existe!\n";

    // Verificar se há dados
    $count = DB::table('cartoes_credito')->count();
    echo "  Registros: $count\n\n";

    echo "Tabela já está criada. Nenhuma ação necessária.\n";
    exit(0);
}

echo "Tabela cartoes_credito não existe. Criando...\n\n";

try {
    DB::schema()->create('cartoes_credito', function ($table) {
        $table->bigIncrements('id');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('conta_id')->unsigned();
        $table->string('nome_cartao', 100);
        $table->string('bandeira', 30);
        $table->string('ultimos_digitos', 4);
        $table->decimal('limite_total', 15, 2)->default(0);
        $table->decimal('limite_disponivel', 15, 2)->default(0);
        $table->tinyInteger('dia_vencimento')->nullable();
        $table->tinyInteger('dia_fechamento')->nullable();
        $table->string('cor_cartao', 7)->nullable();
        $table->boolean('ativo')->default(true);
        $table->timestamps();

        $table->index(['user_id', 'ativo']);
        $table->index('conta_id');
    });

    echo "✓ Tabela cartoes_credito criada com sucesso!\n";
    echo "  (Foreign keys omitidas para evitar problemas de compatibilidade)\n\n";

    // Registrar migration como executada
    $migrationName = '2025_12_23_000003_create_cartoes_credito_table';
    $exists = DB::table('migrations')->where('migration', $migrationName)->exists();

    if (!$exists) {
        $nextBatch = DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $nextBatch
        ]);
        echo "✓ Migration registrada no banco de dados\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Processo concluído com sucesso!\n";
