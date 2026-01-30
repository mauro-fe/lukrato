<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  EXECUTANDO MIGRATION: Campos de Competência               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Verificar se campos já existem
$cols = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'data_competencia'");
if (count($cols) > 0) {
    echo "⏭️  Coluna data_competencia já existe. Migration já foi executada.\n";
    exit(0);
}

echo "🔄 Adicionando campos de competência à tabela lancamentos...\n";

try {
    // Adicionar data_competencia
    DB::statement("ALTER TABLE lancamentos ADD COLUMN data_competencia DATE NULL AFTER data");
    echo "   ✓ Coluna data_competencia adicionada\n";

    // Adicionar afeta_competencia
    DB::statement("ALTER TABLE lancamentos ADD COLUMN afeta_competencia TINYINT(1) NOT NULL DEFAULT 1 AFTER data_competencia");
    echo "   ✓ Coluna afeta_competencia adicionada\n";

    // Adicionar afeta_caixa
    DB::statement("ALTER TABLE lancamentos ADD COLUMN afeta_caixa TINYINT(1) NOT NULL DEFAULT 1 AFTER afeta_competencia");
    echo "   ✓ Coluna afeta_caixa adicionada\n";

    // Adicionar origem_tipo
    DB::statement("ALTER TABLE lancamentos ADD COLUMN origem_tipo ENUM('normal','cartao_credito','parcelamento','agendamento','transferencia') NOT NULL DEFAULT 'normal' AFTER afeta_caixa");
    echo "   ✓ Coluna origem_tipo adicionada\n";

    // Adicionar índices
    DB::statement("ALTER TABLE lancamentos ADD INDEX idx_lancamentos_data_competencia (data_competencia)");
    echo "   ✓ Índice idx_lancamentos_data_competencia criado\n";

    DB::statement("ALTER TABLE lancamentos ADD INDEX idx_lancamentos_origem_competencia (origem_tipo, afeta_competencia)");
    echo "   ✓ Índice idx_lancamentos_origem_competencia criado\n";

    DB::statement("ALTER TABLE lancamentos ADD INDEX idx_lancamentos_user_competencia (user_id, data_competencia)");
    echo "   ✓ Índice idx_lancamentos_user_competencia criado\n";

    echo "\n✅ Migration executada com sucesso!\n";

    // Registrar na tabela de migrations
    DB::table('migrations')->insert([
        'migration' => '2026_01_29_000001_add_competencia_fields_to_lancamentos',
        'batch' => DB::table('migrations')->max('batch') + 1,
    ]);
    echo "📝 Migration registrada na tabela de controle.\n";
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
