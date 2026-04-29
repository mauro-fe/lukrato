<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('faturas_cartao_itens')) {
            return;
        }

        $schema->table('faturas_cartao_itens', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('faturas_cartao_itens', 'subcategoria_id')) {
                $table->unsignedInteger('subcategoria_id')->nullable()->after('categoria_id');
                $table->index('subcategoria_id', 'idx_fci_subcategoria_id');
            }
        });
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('faturas_cartao_itens')) {
            return;
        }

        $schema->table('faturas_cartao_itens', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('faturas_cartao_itens', 'subcategoria_id')) {
                $table->dropIndex('idx_fci_subcategoria_id');
                $table->dropColumn('subcategoria_id');
            }
        });
    }
};
