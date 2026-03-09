<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        // ── ai_conversations ─────────────────────────────────
        if (Capsule::schema()->hasTable('ai_conversations')) {
            echo "• Tabela ai_conversations já existe\n";
        } else {
            Capsule::schema()->create('ai_conversations', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('titulo', 200)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'updated_at']);
            });
            echo "✅ Tabela ai_conversations criada\n";
        }

        // ── ai_chat_messages ─────────────────────────────────
        if (Capsule::schema()->hasTable('ai_chat_messages')) {
            echo "• Tabela ai_chat_messages já existe\n";
        } else {
            Capsule::schema()->create('ai_chat_messages', function ($table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->enum('role', ['user', 'assistant'])->index();
                $table->text('content');
                $table->unsignedInteger('tokens_used')->nullable();
                $table->string('intent', 30)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->foreign('conversation_id')
                    ->references('id')
                    ->on('ai_conversations')
                    ->onDelete('cascade');

                // Índice para contagem de quota mensal
                $table->index(['role', 'created_at']);
            });
            echo "✅ Tabela ai_chat_messages criada\n";
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('ai_chat_messages');
        Capsule::schema()->dropIfExists('ai_conversations');
        echo "✅ Tabelas ai_chat_messages e ai_conversations removidas\n";
    }
};
