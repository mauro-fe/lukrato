<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('cartoes_credito')) {
            $this->modifyColumnIfNeeded('cartoes_credito', 'user_id', 'INT UNSIGNED NOT NULL');
            $this->modifyColumnIfNeeded('cartoes_credito', 'conta_id', 'INT UNSIGNED NOT NULL');

            $this->createIndexIfMissing('cartoes_credito', 'idx_cartoes_credito_user', 'user_id');
            $this->createIndexIfMissing('cartoes_credito', 'idx_cartoes_credito_conta', 'conta_id');

            if ($schema->hasTable('usuarios') && !$this->hasForeignKey('cartoes_credito', 'fk_cartoes_credito_user')) {
                Capsule::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT fk_cartoes_credito_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('contas') && !$this->hasForeignKey('cartoes_credito', 'fk_cartoes_credito_conta')) {
                Capsule::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT fk_cartoes_credito_conta FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }
        }

        if ($schema->hasTable('faturas')) {
            $this->modifyColumnIfNeeded('faturas', 'user_id', 'INT UNSIGNED NOT NULL');

            $this->createIndexIfMissing('faturas', 'idx_faturas_user', 'user_id');
            $this->createIndexIfMissing('faturas', 'idx_faturas_cartao', 'cartao_credito_id');

            if ($schema->hasTable('usuarios') && !$this->hasForeignKey('faturas', 'fk_faturas_user')) {
                Capsule::statement('ALTER TABLE faturas ADD CONSTRAINT fk_faturas_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('cartoes_credito') && !$this->hasForeignKey('faturas', 'fk_faturas_cartao')) {
                Capsule::statement('ALTER TABLE faturas ADD CONSTRAINT fk_faturas_cartao FOREIGN KEY (cartao_credito_id) REFERENCES cartoes_credito(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }
        }

        if ($schema->hasTable('faturas_cartao_itens')) {
            $this->createIndexIfMissing('faturas_cartao_itens', 'idx_fci_cartao_credito_id', 'cartao_credito_id');

            if ($schema->hasTable('usuarios') && !$this->hasForeignKey('faturas_cartao_itens', 'fk_fci_user')) {
                Capsule::statement('ALTER TABLE faturas_cartao_itens ADD CONSTRAINT fk_fci_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('cartoes_credito') && !$this->hasForeignKey('faturas_cartao_itens', 'fk_fci_cartao')) {
                Capsule::statement('ALTER TABLE faturas_cartao_itens ADD CONSTRAINT fk_fci_cartao FOREIGN KEY (cartao_credito_id) REFERENCES cartoes_credito(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('faturas') && !$this->hasForeignKey('faturas_cartao_itens', 'fk_fci_fatura')) {
                Capsule::statement('ALTER TABLE faturas_cartao_itens ADD CONSTRAINT fk_fci_fatura FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }

        if ($schema->hasTable('notifications')) {
            $this->modifyColumnIfNeeded('notifications', 'user_id', 'INT UNSIGNED NOT NULL');

            if ($schema->hasTable('usuarios') && !$this->hasForeignKey('notifications', 'fk_notifications_user')) {
                Capsule::statement('ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('message_campaigns') && !$this->hasForeignKey('notifications', 'fk_notifications_campaign')) {
                Capsule::statement('ALTER TABLE notifications ADD CONSTRAINT fk_notifications_campaign FOREIGN KEY (campaign_id) REFERENCES message_campaigns(id) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }

        if ($schema->hasTable('agendamentos')) {
            $this->createIndexIfMissing('agendamentos', 'idx_agendamentos_conta', 'conta_id');

            if ($schema->hasTable('usuarios') && !$this->hasForeignKey('agendamentos', 'fk_agendamentos_user')) {
                Capsule::statement('ALTER TABLE agendamentos ADD CONSTRAINT fk_agendamentos_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
            }

            if ($schema->hasTable('contas') && !$this->hasForeignKey('agendamentos', 'fk_agendamentos_conta')) {
                Capsule::statement('ALTER TABLE agendamentos ADD CONSTRAINT fk_agendamentos_conta FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }
    }

    public function down(): void
    {
        if ($this->hasForeignKey('agendamentos', 'fk_agendamentos_conta')) {
            Capsule::statement('ALTER TABLE agendamentos DROP FOREIGN KEY fk_agendamentos_conta');
        }

        if ($this->hasForeignKey('agendamentos', 'fk_agendamentos_user')) {
            Capsule::statement('ALTER TABLE agendamentos DROP FOREIGN KEY fk_agendamentos_user');
        }

        if ($this->hasForeignKey('notifications', 'fk_notifications_campaign')) {
            Capsule::statement('ALTER TABLE notifications DROP FOREIGN KEY fk_notifications_campaign');
        }

        if ($this->hasForeignKey('notifications', 'fk_notifications_user')) {
            Capsule::statement('ALTER TABLE notifications DROP FOREIGN KEY fk_notifications_user');
        }

        if ($this->hasForeignKey('faturas_cartao_itens', 'fk_fci_fatura')) {
            Capsule::statement('ALTER TABLE faturas_cartao_itens DROP FOREIGN KEY fk_fci_fatura');
        }

        if ($this->hasForeignKey('faturas_cartao_itens', 'fk_fci_cartao')) {
            Capsule::statement('ALTER TABLE faturas_cartao_itens DROP FOREIGN KEY fk_fci_cartao');
        }

        if ($this->hasForeignKey('faturas_cartao_itens', 'fk_fci_user')) {
            Capsule::statement('ALTER TABLE faturas_cartao_itens DROP FOREIGN KEY fk_fci_user');
        }

        if ($this->hasForeignKey('faturas', 'fk_faturas_cartao')) {
            Capsule::statement('ALTER TABLE faturas DROP FOREIGN KEY fk_faturas_cartao');
        }

        if ($this->hasForeignKey('faturas', 'fk_faturas_user')) {
            Capsule::statement('ALTER TABLE faturas DROP FOREIGN KEY fk_faturas_user');
        }

        if ($this->hasForeignKey('cartoes_credito', 'fk_cartoes_credito_conta')) {
            Capsule::statement('ALTER TABLE cartoes_credito DROP FOREIGN KEY fk_cartoes_credito_conta');
        }

        if ($this->hasForeignKey('cartoes_credito', 'fk_cartoes_credito_user')) {
            Capsule::statement('ALTER TABLE cartoes_credito DROP FOREIGN KEY fk_cartoes_credito_user');
        }

        $this->dropIndexIfExists('agendamentos', 'idx_agendamentos_conta');
        $this->dropIndexIfExists('faturas_cartao_itens', 'idx_fci_cartao_credito_id');
        $this->dropIndexIfExists('faturas', 'idx_faturas_cartao');
        $this->dropIndexIfExists('faturas', 'idx_faturas_user');
        $this->dropIndexIfExists('cartoes_credito', 'idx_cartoes_credito_conta');
        $this->dropIndexIfExists('cartoes_credito', 'idx_cartoes_credito_user');
    }

    private function modifyColumnIfNeeded(string $table, string $column, string $definition): void
    {
        $currentDefinition = $this->getCurrentColumnDefinition($table, $column);

        if ($currentDefinition === null) {
            return;
        }

        if (strcasecmp($currentDefinition, $definition) === 0) {
            return;
        }

        Capsule::statement(sprintf(
            'ALTER TABLE %s MODIFY %s %s',
            $table,
            $column,
            $definition
        ));
    }

    private function createIndexIfMissing(string $table, string $indexName, string $columns): void
    {
        if ($this->hasIndex($table, $indexName)) {
            return;
        }

        Capsule::statement(sprintf(
            'CREATE INDEX %s ON %s(%s)',
            $indexName,
            $table,
            $columns
        ));
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->hasIndex($table, $indexName)) {
            return;
        }

        Capsule::statement(sprintf('DROP INDEX %s ON %s', $indexName, $table));
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $result = Capsule::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $indexName]
        );

        return $result !== [];
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $result = Capsule::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$table, $constraintName, 'FOREIGN KEY']
        );

        return $result !== [];
    }

    private function getCurrentColumnDefinition(string $table, string $column): ?string
    {
        $result = Capsule::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        );

        if ($result === null) {
            return null;
        }

        $base = strtoupper((string) $result->COLUMN_TYPE);
        $nullable = strtoupper((string) $result->IS_NULLABLE) === 'YES' ? 'NULL' : 'NOT NULL';

        return trim($base . ' ' . $nullable);
    }
};
