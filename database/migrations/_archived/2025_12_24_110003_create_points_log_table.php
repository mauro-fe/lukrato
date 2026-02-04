<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Log de Pontos
 * 
 * Histórico detalhado de todas as ações que geraram pontos
 */
return new class
{
    public function up(): void
    {
        Capsule::schema()->create('points_log', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('action', 50)->comment('Ação que gerou pontos');
            $table->integer('points')->comment('Pontos ganhos (pode ser negativo)');
            $table->text('description')->nullable()->comment('Descrição detalhada');
            $table->json('metadata')->nullable()->comment('Dados adicionais da ação');
            $table->unsignedBigInteger('related_id')->nullable()->comment('ID do registro relacionado');
            $table->string('related_type', 50)->nullable()->comment('Tipo do registro (lancamento, categoria, etc)');
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index('action');
            $table->index(['related_type', 'related_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('points_log');
    }
};
