<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Cria tabela de indicações para rastrear referrals
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('indicacoes')) {
            $schema->create('indicacoes', function (Blueprint $table) {
                $table->id();

                // Quem indicou
                $table->unsignedBigInteger('referrer_id');
                $table->foreign('referrer_id')->references('id')->on('usuarios')->cascadeOnDelete();

                // Quem foi indicado
                $table->unsignedBigInteger('referred_id');
                $table->foreign('referred_id')->references('id')->on('usuarios')->cascadeOnDelete();

                // Status da indicação
                $table->enum('status', ['pending', 'completed', 'expired', 'cancelled'])->default('pending');

                // Recompensas
                $table->integer('referrer_reward_days')->default(15); // Dias PRO para quem indicou
                $table->integer('referred_reward_days')->default(7);  // Dias PRO para indicado

                // Controle de recompensas aplicadas
                $table->boolean('referrer_rewarded')->default(false);
                $table->boolean('referred_rewarded')->default(false);
                $table->timestamp('referrer_rewarded_at')->nullable();
                $table->timestamp('referred_rewarded_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                // Timestamps
                $table->timestamps();

                // Índices
                $table->unique('referred_id'); // Cada usuário só pode ser indicado uma vez
                $table->index('referrer_id');
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('indicacoes');
    }
};
