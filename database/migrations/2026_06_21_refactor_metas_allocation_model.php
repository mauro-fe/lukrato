<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('metas')) {
            if (!$schema->hasColumn('metas', 'valor_alocado') || !$schema->hasColumn('metas', 'valor_aporte_manual')) {
                $schema->table('metas', function (Blueprint $table) use ($schema) {
                    if (!$schema->hasColumn('metas', 'valor_alocado')) {
                        $table->decimal('valor_alocado', 12, 2)->default(0)->after('valor_alvo');
                    }

                    if (!$schema->hasColumn('metas', 'valor_aporte_manual')) {
                        $table->decimal('valor_aporte_manual', 12, 2)->default(0)->after('valor_alocado');
                    }
                });
            }

            if ($schema->hasColumn('metas', 'valor_atual')) {
                Capsule::statement('UPDATE metas SET valor_alocado = COALESCE(valor_atual, 0)');
                Capsule::statement('UPDATE metas SET valor_aporte_manual = COALESCE(valor_atual, 0)');
                Capsule::statement('UPDATE metas SET valor_atual = valor_alocado');
            }

            if ($schema->hasColumn('metas', 'conta_id')) {
                Capsule::table('metas')
                    ->whereNotNull('conta_id')
                    ->update(['conta_id' => null]);
            }

            Capsule::statement(
                "UPDATE metas
                    SET status = CASE
                        WHEN status = 'cancelada' THEN status
                        WHEN valor_alocado >= valor_alvo THEN 'concluida'
                        WHEN status = 'pausada' THEN 'pausada'
                        ELSE 'ativa'
                    END"
            );
        }

        if ($schema->hasTable('lancamentos')) {
            if (!$schema->hasColumn('lancamentos', 'meta_id')) {
                $schema->table('lancamentos', function (Blueprint $table) {
                    $table->unsignedInteger('meta_id')->nullable()->after('subcategoria_id');
                });
            }

            if (!$this->hasIndex('lancamentos', 'idx_lancamentos_meta_id')) {
                Capsule::statement('CREATE INDEX idx_lancamentos_meta_id ON lancamentos(meta_id)');
            }

            if ($schema->hasTable('metas') && !$this->hasForeignKey('lancamentos', 'fk_lancamentos_meta')) {
                Capsule::statement(
                    'ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_meta FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE SET NULL ON UPDATE CASCADE'
                );
            }
        }
    }

    public function down(): void
    {
        if ($this->hasForeignKey('lancamentos', 'fk_lancamentos_meta')) {
            Capsule::statement('ALTER TABLE lancamentos DROP FOREIGN KEY fk_lancamentos_meta');
        }

        if ($this->hasIndex('lancamentos', 'idx_lancamentos_meta_id')) {
            Capsule::statement('DROP INDEX idx_lancamentos_meta_id ON lancamentos');
        }

        $schema = Capsule::schema();

        if ($schema->hasTable('lancamentos') && $schema->hasColumn('lancamentos', 'meta_id')) {
            $schema->table('lancamentos', function (Blueprint $table) {
                $table->dropColumn('meta_id');
            });
        }

        if ($schema->hasTable('metas')) {
            if ($schema->hasColumn('metas', 'valor_aporte_manual')) {
                $schema->table('metas', function (Blueprint $table) {
                    $table->dropColumn('valor_aporte_manual');
                });
            }

            if ($schema->hasColumn('metas', 'valor_alocado')) {
                $schema->table('metas', function (Blueprint $table) {
                    $table->dropColumn('valor_alocado');
                });
            }
        }
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
};
