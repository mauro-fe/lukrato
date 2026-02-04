<?php

/**
 * Migration: Create points_logs table
 * 
 * Tabela para registrar histórico de pontos ganhos
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

echo "🔄 Criando tabela points_logs...\n";

try {
    // Verificar se já existe
    if (DB::schema()->hasTable('points_logs')) {
        echo "⚠️  Tabela points_logs já existe! Pulando...\n";
        exit(0);
    }

    // Criar tabela
    DB::schema()->create('points_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id')->comment('ID do usuário');
        $table->string('action', 50)->comment('Ação que gerou os pontos');
        $table->integer('points')->default(0)->comment('Pontos ganhos');
        $table->text('description')->nullable()->comment('Descrição da ação');
        $table->json('metadata')->nullable()->comment('Metadados adicionais');
        $table->unsignedBigInteger('related_id')->nullable()->comment('ID do registro relacionado');
        $table->string('related_type', 50)->nullable()->comment('Tipo do registro relacionado');
        $table->timestamps();

        // Índices
        $table->index('user_id');
        $table->index('action');
        $table->index(['related_id', 'related_type']);
        $table->index('created_at');

        // Foreign key (sem ON DELETE CASCADE por compatibilidade)
        // $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
    });

    echo "✅ Tabela points_logs criada com sucesso!\n\n";

    // Mostrar estrutura
    echo "📋 Estrutura da tabela:\n";
    $columns = DB::select("DESCRIBE points_logs");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }

    echo "\n✅ Migração concluída!\n";
} catch (\Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
