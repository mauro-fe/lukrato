<?php

/**
 * Migration para adicionar campo tipo aos itens de fatura
 * Permite diferenciar despesas de estornos/créditos
 * 
 * Executar: php cli/migrate.php
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

// Verificar se já existe a coluna
$hasColumn = Capsule::schema()->hasColumn('faturas_cartao_itens', 'tipo');

if (!$hasColumn) {
    Capsule::schema()->table('faturas_cartao_itens', function (Blueprint $table) {
        // tipo: 'despesa' (padrão) ou 'estorno'
        $table->string('tipo', 20)->default('despesa')->after('valor');
    });

    echo "✅ Coluna 'tipo' adicionada à tabela faturas_cartao_itens\n";
} else {
    echo "⚠️ Coluna 'tipo' já existe na tabela faturas_cartao_itens\n";
}
