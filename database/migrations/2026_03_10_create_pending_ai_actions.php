<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('pending_ai_actions')) {
            echo "• Tabela pending_ai_actions já existe — ignorando\n";
            return;
        }

        Capsule::schema()->create('pending_ai_actions', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->enum('action_type', [
                'create_lancamento',
                'create_meta',
                'create_orcamento',
                'create_categoria',
                'create_subcategoria',
            ]);
            $table->json('payload');
            $table->enum('status', [
                'awaiting_confirm',
                'confirmed',
                'rejected',
                'expired',
            ])->default('awaiting_confirm');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');

            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');
        });

        echo "✅ Tabela pending_ai_actions criada\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('pending_ai_actions');
        echo "✅ Tabela pending_ai_actions removida\n";
    }
};
