<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('whatsapp_messages')) {
            echo "• Tabela whatsapp_messages já existe\n";
            return;
        }

        Capsule::schema()->create('whatsapp_messages', function ($table) {
            $table->id();

            $table->string('wa_message_id', 100)->unique()->comment('Idempotency: Meta message ID');
            $table->string('from_phone', 20)->index();
            $table->unsignedInteger('user_id')->nullable()->index();

            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->enum('type', ['text', 'interactive', 'status', 'unknown'])->default('text');

            $table->text('body')->nullable();
            $table->json('metadata')->nullable()->comment('Payload original ou dados extra');

            $table->enum('processing_status', ['received', 'processed', 'failed', 'ignored'])
                ->default('received')
                ->index();

            $table->string('intent', 50)->nullable()->comment('IntentType detectado');
            $table->string('error_message', 500)->nullable();

            $table->timestamps();

            // Composto para consultas
            $table->index(['from_phone', 'created_at']);
            $table->index(['user_id', 'created_at']);

            // FK
            $table->foreign('user_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        echo "✔ Tabela whatsapp_messages criada\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('whatsapp_messages');
    }
};
