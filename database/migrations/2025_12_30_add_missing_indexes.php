<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Adiciona índices faltantes para otimização de performance
 * 
 * Adiciona índices que não foram criados nas migrations anteriores
 * devido às tabelas já existirem no momento da atualização
 */
return new class
{
    public function up(): void
    {
        echo "Adicionando índices de performance...\n";

        // Índices na tabela lancamentos
        Capsule::schema()->table('lancamentos', function ($table) {
            // Verificar se índice já existe antes de criar
            try {
                if (!$this->indexExists('lancamentos', 'idx_lancamentos_data_pagamento')) {
                    $table->index('data_pagamento', 'idx_lancamentos_data_pagamento');
                    echo "  ✅ Índice idx_lancamentos_data_pagamento criado\n";
                }

                if (!$this->indexExists('lancamentos', 'idx_lancamentos_cartao')) {
                    $table->index('cartao_credito_id', 'idx_lancamentos_cartao');
                    echo "  ✅ Índice idx_lancamentos_cartao criado\n";
                }

                if (!$this->indexExists('lancamentos', 'idx_lancamentos_parcelamento')) {
                    $table->index('eh_parcelado', 'idx_lancamentos_parcelamento');
                    echo "  ✅ Índice idx_lancamentos_parcelamento criado\n";
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao criar índices em lancamentos: " . $e->getMessage() . "\n";
            }
        });

        // Índices na tabela contas
        Capsule::schema()->table('contas', function ($table) {
            try {
                if (!$this->indexExists('contas', 'idx_contas_saldo_inicial')) {
                    $table->index('saldo_inicial', 'idx_contas_saldo_inicial');
                    echo "  ✅ Índice idx_contas_saldo_inicial criado\n";
                }

                if (!$this->indexExists('contas', 'idx_contas_tipo_conta')) {
                    $table->index('tipo_conta', 'idx_contas_tipo_conta');
                    echo "  ✅ Índice idx_contas_tipo_conta criado\n";
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao criar índices em contas: " . $e->getMessage() . "\n";
            }
        });

        // Índices na tabela instituicoes_financeiras
        Capsule::schema()->table('instituicoes_financeiras', function ($table) {
            try {
                if (!$this->indexExists('instituicoes_financeiras', 'idx_instituicoes_tipo')) {
                    $table->index('tipo', 'idx_instituicoes_tipo');
                    echo "  ✅ Índice idx_instituicoes_tipo criado\n";
                }

                if (!$this->indexExists('instituicoes_financeiras', 'idx_instituicoes_ativo')) {
                    $table->index('ativo', 'idx_instituicoes_ativo');
                    echo "  ✅ Índice idx_instituicoes_ativo criado\n";
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao criar índices em instituicoes_financeiras: " . $e->getMessage() . "\n";
            }
        });

        echo "✅ Índices de performance adicionados com sucesso!\n";
    }

    public function down(): void
    {
        echo "Removendo índices de performance...\n";

        // Remover índices da tabela lancamentos
        Capsule::schema()->table('lancamentos', function ($table) {
            try {
                if ($this->indexExists('lancamentos', 'idx_lancamentos_data_pagamento')) {
                    $table->dropIndex('idx_lancamentos_data_pagamento');
                }
                if ($this->indexExists('lancamentos', 'idx_lancamentos_cartao')) {
                    $table->dropIndex('idx_lancamentos_cartao');
                }
                if ($this->indexExists('lancamentos', 'idx_lancamentos_parcelamento')) {
                    $table->dropIndex('idx_lancamentos_parcelamento');
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao remover índices de lancamentos: " . $e->getMessage() . "\n";
            }
        });

        // Remover índices da tabela contas
        Capsule::schema()->table('contas', function ($table) {
            try {
                if ($this->indexExists('contas', 'idx_contas_saldo_inicial')) {
                    $table->dropIndex('idx_contas_saldo_inicial');
                }
                if ($this->indexExists('contas', 'idx_contas_tipo_conta')) {
                    $table->dropIndex('idx_contas_tipo_conta');
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao remover índices de contas: " . $e->getMessage() . "\n";
            }
        });

        // Remover índices da tabela instituicoes_financeiras
        Capsule::schema()->table('instituicoes_financeiras', function ($table) {
            try {
                if ($this->indexExists('instituicoes_financeiras', 'idx_instituicoes_tipo')) {
                    $table->dropIndex('idx_instituicoes_tipo');
                }
                if ($this->indexExists('instituicoes_financeiras', 'idx_instituicoes_ativo')) {
                    $table->dropIndex('idx_instituicoes_ativo');
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Erro ao remover índices de instituicoes_financeiras: " . $e->getMessage() . "\n";
            }
        });

        echo "✅ Índices removidos com sucesso!\n";
    }

    /**
     * Verifica se um índice existe em uma tabela
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Capsule::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
