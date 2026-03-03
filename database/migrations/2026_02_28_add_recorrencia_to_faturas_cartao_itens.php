<?php

/**
 * Migration: Adicionar campos de recorrência/assinatura na tabela faturas_cartao_itens
 * 
 * Permite registrar cobranças recorrentes em cartão de crédito (ex: Spotify, ChatGPT)
 * 
 * Novos campos:
 * - recorrente: BOOLEAN - se é cobrança recorrente/assinatura
 * - recorrencia_freq: VARCHAR(20) - frequência: mensal, anual, etc
 * - recorrencia_fim: DATE - data de fim da recorrência (null = infinito)
 * - recorrencia_pai_id: BIGINT FK - referência ao item original da recorrência
 * - cancelado_em: DATETIME - quando a recorrência foi cancelada
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campos de recorrência/assinatura aos itens de fatura do cartão ===\n\n";

        $columns = DB::select("SHOW COLUMNS FROM faturas_cartao_itens");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        $newColumns = [
            'recorrente' => "ALTER TABLE faturas_cartao_itens ADD COLUMN recorrente TINYINT(1) NOT NULL DEFAULT 0 AFTER pago",
            'recorrencia_freq' => "ALTER TABLE faturas_cartao_itens ADD COLUMN recorrencia_freq VARCHAR(20) NULL DEFAULT NULL AFTER recorrente",
            'recorrencia_fim' => "ALTER TABLE faturas_cartao_itens ADD COLUMN recorrencia_fim DATE NULL DEFAULT NULL AFTER recorrencia_freq",
            'recorrencia_pai_id' => "ALTER TABLE faturas_cartao_itens ADD COLUMN recorrencia_pai_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER recorrencia_fim",
            'cancelado_em' => "ALTER TABLE faturas_cartao_itens ADD COLUMN cancelado_em DATETIME NULL DEFAULT NULL AFTER recorrencia_pai_id",
        ];

        foreach ($newColumns as $name => $sql) {
            if (!in_array($name, $columnNames)) {
                DB::statement($sql);
                echo "✅ Coluna '{$name}' adicionada.\n";
            } else {
                echo "ℹ️ Coluna '{$name}' já existe.\n";
            }
        }

        // Índice para buscar itens recorrentes ativos (para cron de geração)
        try {
            DB::statement("CREATE INDEX idx_fci_recorrencia ON faturas_cartao_itens (recorrente, recorrencia_pai_id, cancelado_em)");
            echo "✅ Índice 'idx_fci_recorrencia' criado.\n";
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                echo "ℹ️ Índice 'idx_fci_recorrencia' já existe.\n";
            } else {
                echo "⚠️ Erro ao criar índice: {$e->getMessage()}\n";
            }
        }

        // Índice para buscar filhos de uma recorrência
        try {
            DB::statement("CREATE INDEX idx_fci_recorrencia_pai ON faturas_cartao_itens (recorrencia_pai_id)");
            echo "✅ Índice 'idx_fci_recorrencia_pai' criado.\n";
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                echo "ℹ️ Índice 'idx_fci_recorrencia_pai' já existe.\n";
            } else {
                echo "⚠️ Erro ao criar índice: {$e->getMessage()}\n";
            }
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campos de recorrência dos itens de fatura do cartão ===\n\n";

        $columns = ['recorrente', 'recorrencia_freq', 'recorrencia_fim', 'recorrencia_pai_id', 'cancelado_em'];

        foreach ($columns as $col) {
            try {
                DB::statement("ALTER TABLE faturas_cartao_itens DROP COLUMN IF EXISTS {$col}");
                echo "✅ Coluna '{$col}' removida.\n";
            } catch (\Throwable $e) {
                echo "⚠️ Erro ao remover '{$col}': {$e->getMessage()}\n";
            }
        }

        try {
            DB::statement("DROP INDEX idx_fci_recorrencia ON faturas_cartao_itens");
        } catch (\Throwable) {
        }
        try {
            DB::statement("DROP INDEX idx_fci_recorrencia_pai ON faturas_cartao_itens");
        } catch (\Throwable) {
        }

        echo "\n✅ Rollback concluído!\n";
    }
};