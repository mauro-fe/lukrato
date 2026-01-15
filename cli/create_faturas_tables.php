<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Criando tabela faturas...\n\n";

try {
    // 1. Criar tabela faturas
    if (!Capsule::schema()->hasTable('faturas')) {
        Capsule::schema()->create('faturas', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cartao_credito_id');
            $table->string('descricao', 255);
            $table->decimal('valor_total', 10, 2);
            $table->integer('numero_parcelas');
            $table->date('data_compra');
            $table->string('status', 20)->default('pendente');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('cartao_credito_id')->references('id')->on('cartoes_credito')->onDelete('cascade');

            $table->index(['user_id', 'cartao_credito_id']);
            $table->index('data_compra');
        });
        echo "✅ Tabela 'faturas' criada com sucesso!\n";
    } else {
        echo "⚠️  Tabela 'faturas' já existe!\n";
    }

    // 2. Criar tabela faturas_cartao_itens
    if (!Capsule::schema()->hasTable('faturas_cartao_itens')) {
        Capsule::schema()->create('faturas_cartao_itens', function ($table) {
            $table->id();
            $table->unsignedBigInteger('fatura_id');
            $table->unsignedBigInteger('lancamento_id')->nullable();
            $table->integer('numero_parcela');
            $table->decimal('valor_parcela', 10, 2);
            $table->integer('mes_referencia');
            $table->integer('ano_referencia');
            $table->boolean('pago')->default(false);
            $table->date('data_pagamento')->nullable();
            $table->timestamps();

            $table->foreign('fatura_id')->references('id')->on('faturas')->onDelete('cascade');
            // Foreign key para lancamentos será adicionada depois se a tabela existir
            // $table->foreign('lancamento_id')->references('id')->on('lancamentos')->onDelete('set null');

            $table->index(['fatura_id', 'numero_parcela']);
            $table->index(['mes_referencia', 'ano_referencia']);
        });
        echo "✅ Tabela 'faturas_cartao_itens' criada com sucesso!\n";
    } else {
        echo "⚠️  Tabela 'faturas_cartao_itens' já existe!\n";
    }

    echo "\n✅ Todas as tabelas de faturas foram criadas/verificadas!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
