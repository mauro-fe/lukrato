<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Primeiro: remover a constraint antiga se existir
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'faturas_cartao_itens' 
                AND COLUMN_NAME = 'parcelamento_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($foreignKeys)) {
                $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE faturas_cartao_itens DROP FOREIGN KEY `{$constraintName}`");
            }
        } catch (\Exception $e) {
            // Ignora se não existir
        }

        // Renomear a coluna
        if (Capsule::schema()->hasColumn('faturas_cartao_itens', 'parcelamento_id')) {
            DB::statement('ALTER TABLE faturas_cartao_itens CHANGE COLUMN parcelamento_id fatura_id BIGINT UNSIGNED NULL');
        }

        // Adicionar foreign key para faturas (só se a tabela faturas existir)
        if (Capsule::schema()->hasTable('faturas')) {
            try {
                Capsule::schema()->table('faturas_cartao_itens', function ($table) {
                    $table->foreign('fatura_id')->references('id')->on('faturas')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Ignora se já existir
            }
        }
    }

    public function down(): void
    {
        // Reverter: fatura_id para parcelamento_id
        if (Capsule::schema()->hasColumn('faturas_cartao_itens', 'fatura_id')) {
            Capsule::schema()->table('faturas_cartao_itens', function ($table) {
                $table->dropForeign(['fatura_id']);
                $table->renameColumn('fatura_id', 'parcelamento_id');
            });
        }
    }
};
