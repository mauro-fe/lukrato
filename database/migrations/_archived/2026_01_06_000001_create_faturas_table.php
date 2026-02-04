<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('faturas')) {
            Capsule::schema()->create('faturas', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('cartao_credito_id');
                $table->string('descricao', 255);
                $table->decimal('valor_total', 10, 2);
                $table->integer('numero_parcelas');
                $table->date('data_compra');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('cartao_credito_id')->references('id')->on('cartoes_credito')->onDelete('cascade');

                $table->index(['user_id', 'cartao_credito_id']);
                $table->index('data_compra');
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('faturas');
    }
};
