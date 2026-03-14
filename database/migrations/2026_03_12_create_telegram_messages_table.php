<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('telegram_messages')) {
            echo "• Tabela telegram_messages já existe\n";
            return;
        }

        Capsule::schema()->create('telegram_messages', function ($table) {
            $table->id();

            $table->string('tg_update_id', 50)->unique()->comment('Idempotency: Telegram update_id');
            $table->string('tg_message_id', 50)->index()->comment('Telegram message_id dentro do chat');
            $table->string('chat_id', 50)->index();
            $table->unsignedInteger('user_id')->nullable()->index();

            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->enum('type', ['text', 'callback_query', 'command', 'unknown'])->default('text');

            $table->text('body')->nullable();
            $table->json('metadata')->nullable()->comment('Payload original ou dados extra');

            $table->enum('processing_status', ['received', 'processed', 'failed', 'ignored'])
                ->default('received')
                ->index();

            $table->string('intent', 50)->nullable()->comment('IntentType detectado');
            $table->string('error_message', 500)->nullable();

            $table->timestamps();

            // Composto para consultas
            $table->index(['chat_id', 'created_at']);
            $table->index(['user_id', 'created_at']);

            // FK
            $table->foreign('user_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        echo "✔ Tabela telegram_messages criada\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('telegram_messages');
    }
};
