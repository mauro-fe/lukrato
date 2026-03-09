<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('whatsapp_pending')) {
            echo "• Tabela whatsapp_pending já existe\n";
            return;
        }

        Capsule::schema()->create('whatsapp_pending', function ($table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();
            $table->string('wa_message_id', 100)->unique()->comment('ID da mensagem original no Meta');

            // Dados da transação extraída
            $table->string('descricao', 200);
            $table->decimal('valor', 12, 2);
            $table->enum('tipo', ['despesa', 'receita'])->default('despesa');
            $table->date('data');

            // Categoria sugerida (pode ser null se rule engine não encontrou)
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('subcategoria_id')->nullable();
            $table->string('categoria_nome', 100)->nullable();
            $table->string('subcategoria_nome', 100)->nullable();

            // Status do fluxo
            $table->enum('status', ['awaiting_confirm', 'confirmed', 'rejected', 'expired'])
                ->default('awaiting_confirm')
                ->index();

            // Controle
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // FK
            $table->foreign('user_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });

        echo "✔ Tabela whatsapp_pending criada\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('whatsapp_pending');
    }
};
