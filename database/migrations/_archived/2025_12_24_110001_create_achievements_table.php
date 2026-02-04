<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Tabela de Conquistas (Achievements)
 * 
 * Define as conquistas disponíveis no sistema
 */
return new class
{
    public function up(): void
    {
        Capsule::schema()->create('achievements', function ($table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Código único da conquista');
            $table->string('name', 100)->comment('Nome exibido');
            $table->text('description')->comment('Descrição da conquista');
            $table->string('icon', 50)->default('trophy')->comment('Ícone FontAwesome');
            $table->integer('points_reward')->default(0)->comment('Pontos ganhos ao desbloquear');
            $table->enum('category', ['streak', 'financial', 'level', 'usage'])->default('usage');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Índices
            $table->index('code');
            $table->index('category');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('achievements');
    }
};
