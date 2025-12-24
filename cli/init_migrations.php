<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

require BASE_PATH . '/config/config.php';

echo "=== INICIALIZANDO SISTEMA DE MIGRATIONS ===\n\n";

// Criar tabela de migrations se não existir
if (!DB::schema()->hasTable('migrations')) {
    echo "Criando tabela de migrations...\n";
    DB::schema()->create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });
    echo "✓ Tabela migrations criada!\n\n";
} else {
    echo "✓ Tabela migrations já existe!\n\n";
}

// Registrar migrations já executadas (baseado na existência das tabelas)
$migrationsToRegister = [];

if (DB::schema()->hasTable('enderecos')) {
    $migrationsToRegister[] = '2025_11_17_100000_create_enderecos_table';
}

if (DB::schema()->hasColumn('contas', 'deleted_at')) {
    $migrationsToRegister[] = '2025_12_19_add_deleted_at_to_contas';
}

if (DB::schema()->hasTable('instituicoes_financeiras')) {
    $migrationsToRegister[] = '2025_12_23_000001_create_instituicoes_financeiras_table';
}

if (DB::schema()->hasColumn('contas', 'instituicao_financeira_id')) {
    $migrationsToRegister[] = '2025_12_23_000002_alter_contas_add_instituicao_id';
}

if (DB::schema()->hasTable('cartoes_credito')) {
    $migrationsToRegister[] = '2025_12_23_000003_create_cartoes_credito_table';
}

// Verificar se há dados em instituicoes_financeiras (seed)
if (DB::schema()->hasTable('instituicoes_financeiras')) {
    $count = DB::table('instituicoes_financeiras')->count();
    if ($count > 0) {
        $migrationsToRegister[] = '2025_12_23_000004_seed_instituicoes_financeiras';
    }
}

if (DB::schema()->hasColumn('lancamentos', 'cartao_credito_id')) {
    $migrationsToRegister[] = '2025_12_23_000005_alter_lancamentos_add_cartao_credito';
}

echo "Registrando migrations já executadas...\n";
foreach ($migrationsToRegister as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1
        ]);
        echo "  ✓ Registrada: $migration\n";
    } else {
        echo "  - Já registrada: $migration\n";
    }
}

echo "\n✓ Sistema de migrations inicializado!\n";
echo "Execute: php cli/check_migrations.php para ver o status\n";
