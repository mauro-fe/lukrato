<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Criar tabela error_logs
 *
 * Sistema de logging estruturado no banco de dados.
 * Registra erros, warnings e eventos críticos com contexto completo.
 */
return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('error_logs')) {
            echo "• Tabela error_logs já existe\n";
            return;
        }

        Capsule::schema()->create('error_logs', function ($table) {
            $table->id();

            // Severidade e categoria
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('error')->index();
            $table->string('category', 50)->default('general')->index();

            // Mensagem principal
            $table->string('message', 500);

            // Contexto JSON (payload, IDs, valores, etc.)
            $table->json('context')->nullable();

            // Dados da exception (se houver)
            $table->string('exception_class', 255)->nullable();
            $table->text('exception_message')->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('stack_trace')->nullable();

            // Contexto da request
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Resolução
            $table->timestamp('resolved_at')->nullable()->index();
            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->timestamps();

            // Índices compostos para queries frequentes
            $table->index(['level', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['level', 'category', 'resolved_at']);
        });

        echo "✓ Tabela error_logs criada\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('error_logs');
        echo "✓ Tabela error_logs removida\n";
    }
};
