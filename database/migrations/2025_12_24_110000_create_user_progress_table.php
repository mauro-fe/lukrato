<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Tabela de Progresso do Usuário (Gamificação)
 * 
 * Armazena pontos, nível e streak de cada usuário
 */
return new class
{
    public function up(): void
    {
        Capsule::schema()->create('user_progress', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->integer('total_points')->default(0)->comment('Total de pontos acumulados');
            $table->integer('current_level')->default(1)->comment('Nível atual (1-5)');
            $table->integer('points_to_next_level')->default(100)->comment('Pontos necessários para próximo nível');
            $table->integer('current_streak')->default(0)->comment('Dias consecutivos com lançamentos');
            $table->integer('best_streak')->default(0)->comment('Maior streak alcançada');
            $table->date('last_activity_date')->nullable()->comment('Última data com lançamento');
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            // Índices para performance
            $table->index('total_points');
            $table->index('current_level');
            $table->index('current_streak');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('user_progress');
    }
};
