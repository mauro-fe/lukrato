<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Migration: Adicionar campos de competência à tabela lancamentos
 * 
 * OBJETIVO:
 * Separar corretamente COMPETÊNCIA (mês da despesa) de CAIXA (mês do pagamento)
 * para lançamentos de cartão de crédito.
 * 
 * NOVOS CAMPOS:
 * - data_competencia: Data real da despesa (mês da compra)
 * - afeta_competencia: Se deve contar nas despesas do mês de competência
 * - afeta_caixa: Se afeta saldo disponível (fluxo de caixa)
 * - origem_tipo: Tipo de origem do lançamento
 * 
 * SEGURANÇA:
 * - Campos são NULLABLE/têm DEFAULT
 * - Dados antigos continuam funcionando (backward compatible)
 * - Pode ser revertida sem perda de dados
 * 
 * @see docs/AUDITORIA_CARTAO_CREDITO.md
 * @see docs/PROPOSTA_MIGRACAO.md
 */
return new class
{
    public function up(): void
    {
        echo "🔄 Adicionando campos de competência à tabela lancamentos...\n";

        // Verificar se campos já existem (para idempotência)
        if (DB::schema()->hasColumn('lancamentos', 'data_competencia')) {
            echo "⏭️  Coluna data_competencia já existe. Pulando migration.\n";
            return;
        }

        DB::schema()->table('lancamentos', function ($table) {
            // Campo de competência (mês/ano da despesa real)
            // Para cartão: data da COMPRA, não do pagamento
            $table->date('data_competencia')
                ->nullable()
                ->after('data')
                ->comment('Data de competência (mês da despesa real). NULL = usar campo data');

            // Flag: Se deve contar nas despesas do mês de competência
            // TRUE = aparece nos relatórios/dashboard do mês da compra
            $table->boolean('afeta_competencia')
                ->default(true)
                ->after('data_competencia')
                ->comment('Se deve contar nas despesas do mês de competência');

            // Flag: Se afeta saldo disponível (fluxo de caixa)
            // TRUE = reduz saldo da conta quando executado
            $table->boolean('afeta_caixa')
                ->default(true)
                ->after('afeta_competencia')
                ->comment('Se afeta saldo disponível (fluxo de caixa)');

            // Origem do lançamento para facilitar queries e manutenção
            $table->enum('origem_tipo', [
                'normal',           // Lançamento comum (receita/despesa)
                'cartao_credito',   // Pagamento de fatura de cartão
                'parcelamento',     // Parcela de compra parcelada
                'agendamento',      // Lançamento de agendamento executado
                'transferencia'     // Transferência entre contas
            ])
                ->default('normal')
                ->after('afeta_caixa')
                ->comment('Tipo de origem do lançamento');

            // Índices para performance
            $table->index('data_competencia', 'idx_lancamentos_data_competencia');
            $table->index(['origem_tipo', 'afeta_competencia'], 'idx_lancamentos_origem_competencia');
            $table->index(['user_id', 'data_competencia'], 'idx_lancamentos_user_competencia');
        });

        echo "✅ Campos de competência adicionados com sucesso!\n";
        echo "\n📋 Novos campos:\n";
        echo "   • data_competencia (DATE, NULL) - Data real da despesa\n";
        echo "   • afeta_competencia (BOOL, TRUE) - Conta no mês de competência\n";
        echo "   • afeta_caixa (BOOL, TRUE) - Afeta saldo disponível\n";
        echo "   • origem_tipo (ENUM) - Tipo de origem\n";
        echo "\n💡 Execute o script de normalização para atualizar dados antigos:\n";
        echo "   php cli/normalizar_competencia_cartao.php\n";
    }

    public function down(): void
    {
        echo "🔄 Removendo campos de competência da tabela lancamentos...\n";

        if (!DB::schema()->hasColumn('lancamentos', 'data_competencia')) {
            echo "⏭️  Coluna data_competencia não existe. Pulando rollback.\n";
            return;
        }

        DB::schema()->table('lancamentos', function ($table) {
            // Remover índices primeiro
            try {
                $table->dropIndex('idx_lancamentos_data_competencia');
            } catch (\Exception $e) {
                // Índice pode não existir
            }

            try {
                $table->dropIndex('idx_lancamentos_origem_competencia');
            } catch (\Exception $e) {
                // Índice pode não existir
            }

            try {
                $table->dropIndex('idx_lancamentos_user_competencia');
            } catch (\Exception $e) {
                // Índice pode não existir
            }

            // Remover colunas
            $table->dropColumn([
                'data_competencia',
                'afeta_competencia',
                'afeta_caixa',
                'origem_tipo'
            ]);
        });

        echo "✅ Campos de competência removidos com sucesso!\n";
    }
};
