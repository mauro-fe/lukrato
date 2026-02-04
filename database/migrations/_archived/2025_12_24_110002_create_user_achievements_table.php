<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Tabela de Conquistas do Usuário
 * 
 * Relaciona usuários com suas conquistas desbloqueadas
 */
return new class
{
    public function up(): void
    {
        Capsule::schema()->create('user_achievements', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('achievement_id');
            $table->timestamp('unlocked_at')->useCurrent()->comment('Quando foi desbloqueada');
            $table->boolean('notification_seen')->default(false)->comment('Usuário viu a notificação');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            $table->foreign('achievement_id')
                ->references('id')
                ->on('achievements')
                ->onDelete('cascade');

            // Garantir que usuário não desbloqueie mesma conquista 2x
            $table->unique(['user_id', 'achievement_id']);

            // Índices
            $table->index('user_id');
            $table->index('achievement_id');
            $table->index('unlocked_at');
            $table->index('notification_seen');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('user_achievements');
    }
};
