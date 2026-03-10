<?php

declare(strict_types=1);

/**
 * Migração: Cria tabela user_category_rules para categorização adaptativa.
 * Armazena padrões aprendidos por usuário para que o sistema de IA melhore
 * a categorização com base nas correções e hábitos de cada usuário.
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

$tableName = 'user_category_rules';

echo "=== Migração: Criar tabela {$tableName} ===" . PHP_EOL;

$schema = DB::schema();

if ($schema->hasTable($tableName)) {
    echo "⚠️  Tabela {$tableName} já existe. Pulando." . PHP_EOL;
    exit(0);
}

try {
    $schema->create($tableName, function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('pattern', 200)->comment('Palavra-chave ou regex do padrão detectado');
        $table->string('normalized_pattern', 200)->comment('Padrão normalizado (lowercase, sem acentos) para busca rápida');
        $table->unsignedBigInteger('categoria_id');
        $table->unsignedBigInteger('subcategoria_id')->nullable();
        $table->unsignedInteger('usage_count')->default(1)->comment('Quantas vezes este padrão foi usado/confirmado');
        $table->enum('source', ['correction', 'confirmed', 'manual'])->default('correction')
            ->comment('correction=usuário corrigiu sugestão, confirmed=usuário confirmou, manual=criação manual');
        $table->timestamps();

        // Índices
        $table->index(['user_id', 'normalized_pattern'], 'idx_user_pattern');
        $table->index(['user_id', 'categoria_id'], 'idx_user_categoria');
        $table->unique(['user_id', 'normalized_pattern'], 'uq_user_pattern');

        // Foreign keys
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');
        $table->foreign('subcategoria_id')->references('id')->on('categorias')->onDelete('set null');
    });

    echo "✅ Tabela {$tableName} criada com sucesso!" . PHP_EOL;
} catch (\Throwable $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
