<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Executando migrations pendentes de forma automática...\n\n";

// Buscar migrations já executadas
// @phpstan-ignore-next-line
$executadas = Capsule::table('migrations')->pluck('migration')->toArray();

// Determinar próximo batch
// @phpstan-ignore-next-line
$nextBatch = (int) Capsule::table('migrations')->max('batch') + 1;

// Lista de migrations para executar na ordem
$migrationsToRun = [
    '2026_01_04_add_plan_type_to_achievements.php',
    '2026_01_04_add_progress_percentage.php',
    '2026_01_04_add_streak_protection_fields.php',
    '2026_01_04_create_points_logs.php',
    '2026_01_06_000001_create_faturas_table.php',
    '2026_01_06_000002_alter_faturas_cartao_itens_rename_parcelamento_id.php',
    '2026_01_06_000003_add_mes_ano_referencia.php',
    '2026_01_06_migrate_existing_items_to_faturas.php',
    '2026_01_06_reorganizar_faturas_mensais.php',
    '2026_01_11_000001_add_status_to_faturas.php',
    '2026_01_13_add_arquivado_to_cartoes_credito.php',
    '2026_01_13_add_billing_security_tables.php',
    'seed_achievements.php',
];

$migrationsPath = BASE_PATH . '/database/migrations';
$executadasAgora = 0;

foreach ($migrationsToRun as $basename) {
    $migrationName = str_replace('.php', '', $basename);
    $file = $migrationsPath . '/' . $basename;

    // Verificar se arquivo existe
    if (!file_exists($file)) {
        echo "⚠️  Arquivo não encontrado: $basename\n";
        continue;
    }

    // Pular se já foi executada
    if (in_array($migrationName, $executadas)) {
        echo "⏭️  Pulando (já executada): $basename\n";
        continue;
    }

    echo "Executando: $basename\n";

    // Incluir arquivo
    $migration = require $file;

    if (!is_object($migration) || !method_exists($migration, 'up')) {
        echo "⚠️  Migration inválida: não retorna objeto com método up()\n";

        // Mesmo assim marcar como executada para não tentar novamente
        // @phpstan-ignore-next-line
        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $nextBatch
        ]);
        echo "✓ Marcada como executada (pular nas próximas vezes)\n\n";
        continue;
    }

    try {
        $migration->up();

        // Registrar migration executada
        // @phpstan-ignore-next-line
        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $nextBatch
        ]);

        echo "✓ $basename executada com sucesso!\n\n";
        $executadasAgora++;
    } catch (Exception $e) {
        echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
        echo "Tentando marcar como executada mesmo assim...\n";

        try {
            // @phpstan-ignore-next-line
            Capsule::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $nextBatch
            ]);
            echo "✓ Marcada como executada\n\n";
        } catch (Exception $e2) {
            echo "❌ Erro ao marcar: " . $e2->getMessage() . "\n\n";
        }
    }
}

echo "\n✅ Processo concluído! $executadasAgora migration(s) executada(s) com sucesso!\n";
