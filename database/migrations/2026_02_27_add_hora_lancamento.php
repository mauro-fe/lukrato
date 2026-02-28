<?php

/**
 * Migration: Adiciona coluna hora_lancamento à tabela lancamentos
 * Permite que lançamentos tenham horário específico (útil para lembretes)
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    $schema = DB::schema();

    if (!$schema->hasColumn('lancamentos', 'hora_lancamento')) {
        $schema->table('lancamentos', function ($table) {
            $table->string('hora_lancamento', 5)->nullable()->after('data')
                ->comment('Horário do lançamento (HH:MM)');
        });
        echo "✅ Coluna 'hora_lancamento' adicionada à tabela 'lancamentos'.\n";
    } else {
        echo "⏭️ Coluna 'hora_lancamento' já existe.\n";
    }

    echo "\n✅ Migration concluída com sucesso!\n";
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
