<?php

/**
 * Migration: Blindagem anti-duplicidade para recorrencias.
 *
 * Adiciona constraints unicas para impedir criacao de itens duplicados por ciclo:
 * - lancamentos recorrentes: 1 item por data dentro da mesma serie
 * - recorrencias de cartao: 1 item por mes/ano dentro da mesma serie
 *
 * Importante:
 * - Se existirem duplicados historicos, a migration interrompe com erro explicito.
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Aplicando blindagem anti-duplicidade de recorrencias ===\n\n";

        $duplicadosLancamentos = DB::select(
            "SELECT user_id, recorrencia_pai_id, data, COUNT(*) AS qtd
             FROM lancamentos
             WHERE recorrencia_pai_id IS NOT NULL
             GROUP BY user_id, recorrencia_pai_id, data
             HAVING COUNT(*) > 1
             LIMIT 20"
        );

        if (!empty($duplicadosLancamentos)) {
            echo "❌ Foram encontrados duplicados em lancamentos recorrentes.\n";
            foreach ($duplicadosLancamentos as $dup) {
                echo sprintf(
                    "   - user_id=%d, pai=%d, data=%s, qtd=%d\n",
                    (int) $dup->user_id,
                    (int) $dup->recorrencia_pai_id,
                    (string) $dup->data,
                    (int) $dup->qtd
                );
            }

            throw new RuntimeException(
                'Remova os duplicados de lancamentos antes de aplicar a unique key uk_lancamentos_recorrencia_data.'
            );
        }

        $duplicadosCartao = DB::select(
            "SELECT user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia, COUNT(*) AS qtd
             FROM faturas_cartao_itens
             WHERE recorrencia_pai_id IS NOT NULL
             GROUP BY user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia
             HAVING COUNT(*) > 1
             LIMIT 20"
        );

        if (!empty($duplicadosCartao)) {
            echo "❌ Foram encontrados duplicados em recorrencias de cartao.\n";
            foreach ($duplicadosCartao as $dup) {
                echo sprintf(
                    "   - user_id=%d, cartao=%d, pai=%d, mes=%d, ano=%d, qtd=%d\n",
                    (int) $dup->user_id,
                    (int) $dup->cartao_credito_id,
                    (int) $dup->recorrencia_pai_id,
                    (int) $dup->mes_referencia,
                    (int) $dup->ano_referencia,
                    (int) $dup->qtd
                );
            }

            throw new RuntimeException(
                'Remova os duplicados de cartao antes de aplicar a unique key uk_fci_recorrencia_mes_ano.'
            );
        }

        if (!$this->indexExists('lancamentos', 'uk_lancamentos_recorrencia_data')) {
            DB::statement(
                'CREATE UNIQUE INDEX uk_lancamentos_recorrencia_data ON lancamentos (user_id, recorrencia_pai_id, data)'
            );
            echo "✅ Unique index uk_lancamentos_recorrencia_data criado.\n";
        } else {
            echo "ℹ️ Unique index uk_lancamentos_recorrencia_data ja existe.\n";
        }

        if (!$this->indexExists('faturas_cartao_itens', 'uk_fci_recorrencia_mes_ano')) {
            DB::statement(
                'CREATE UNIQUE INDEX uk_fci_recorrencia_mes_ano ON faturas_cartao_itens (user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia)'
            );
            echo "✅ Unique index uk_fci_recorrencia_mes_ano criado.\n";
        } else {
            echo "ℹ️ Unique index uk_fci_recorrencia_mes_ano ja existe.\n";
        }

        echo "\n✅ Migration concluida.\n";
    }

    public function down(): void
    {
        echo "=== Removendo blindagem anti-duplicidade de recorrencias ===\n\n";

        if ($this->indexExists('lancamentos', 'uk_lancamentos_recorrencia_data')) {
            DB::statement('DROP INDEX uk_lancamentos_recorrencia_data ON lancamentos');
            echo "✅ Unique index uk_lancamentos_recorrencia_data removido.\n";
        } else {
            echo "ℹ️ Unique index uk_lancamentos_recorrencia_data nao existe.\n";
        }

        if ($this->indexExists('faturas_cartao_itens', 'uk_fci_recorrencia_mes_ano')) {
            DB::statement('DROP INDEX uk_fci_recorrencia_mes_ano ON faturas_cartao_itens');
            echo "✅ Unique index uk_fci_recorrencia_mes_ano removido.\n";
        } else {
            echo "ℹ️ Unique index uk_fci_recorrencia_mes_ano nao existe.\n";
        }

        echo "\n✅ Rollback concluido.\n";
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }
};
