<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('cartoes_credito', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id'); // Relacionamento com a conta
            $table->string('nome_cartao', 100); // Ex: "Cartão Nubank Platinum"
            $table->string('bandeira', 30); // visa, mastercard, elo, amex, etc
            $table->string('ultimos_digitos', 4); // últimos 4 dígitos
            $table->decimal('limite_total', 15, 2)->default(0);
            $table->decimal('limite_disponivel', 15, 2)->default(0);
            $table->tinyInteger('dia_vencimento')->nullable(); // 1-31
            $table->tinyInteger('dia_fechamento')->nullable(); // 1-31
            $table->string('cor_cartao', 7)->nullable(); // cor visual do cartão
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // Sem foreign key para users (pode não existir a tabela)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('conta_id')->references('id')->on('contas')->onDelete('cascade');
            
            $table->index(['user_id', 'ativo']);
            $table->index('conta_id');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('cartoes_credito');
    }
};
