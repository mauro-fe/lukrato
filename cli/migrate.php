<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

// Carrega configuração do banco de dados
require BASE_PATH . '/config/config.php';

echo "Iniciando migrations...\n\n";

// Criar tabela de migrations se não existir
if (!DB::schema()->hasTable('migrations')) {
    echo "Criando tabela de migrations...\n";
    DB::schema()->create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });
    echo "✓ Tabela migrations criada!\n\n";
}

// Buscar migrations já executadas
$executadas = DB::table('migrations')->pluck('migration')->toArray();

// Busca todos os arquivos de migration
$migrationsPath = BASE_PATH . '/database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

if (empty($migrationFiles)) {
    echo "Nenhuma migration encontrada.\n";
    exit(0);
}

// Determinar próximo batch
$nextBatch = DB::table('migrations')->max('batch') + 1;

$executadasAgora = 0;

foreach ($migrationFiles as $file) {
    $basename = basename($file);
    $migrationName = str_replace('.php', '', $basename);

    // Pular se já foi executada
    if (in_array($migrationName, $executadas)) {
        echo "⏭️  Pulando (já executada): $basename\n";
        continue;
    }

    echo "Executando: $basename\n";

    // Incluir arquivo e obter instância da classe anônima
    $migration = require $file;

    if (!is_object($migration) || !method_exists($migration, 'up')) {
        echo "⚠️  Migration inválida: não retorna objeto com método up()\n\n";
        continue;
    }

    try {
        $migration->up();

        // Registrar migration executada
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $nextBatch
        ]);

        echo "✓ $basename executada com sucesso!\n\n";
        $executadasAgora++;
    } catch (Exception $e) {
        echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n\n";
    }
}

if ($executadasAgora === 0) {
    echo "✓ Nenhuma migration pendente. Tudo atualizado!\n";
} else {
    echo "✓ $executadasAgora migration(s) executada(s) com sucesso!\n";
}

echo "\nMigrations concluídas!\n";
