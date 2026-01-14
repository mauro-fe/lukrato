<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasColumn('cartoes_credito', 'arquivado')) {
            Capsule::schema()->table('cartoes_credito', function ($table) {
                $table->boolean('arquivado')->default(false)->after('ativo');
            });

            echo "✅ Coluna 'arquivado' adicionada na tabela 'cartoes_credito'\n";
        } else {
            echo "⚠️  Coluna 'arquivado' já existe na tabela 'cartoes_credito'\n";
        }
    }

    public function down(): void
    {
        if (Capsule::schema()->hasColumn('cartoes_credito', 'arquivado')) {
            Capsule::schema()->table('cartoes_credito', function ($table) {
                $table->dropColumn('arquivado');
            });

            echo "✅ Coluna 'arquivado' removida da tabela 'cartoes_credito'\n";
        }
    }
};
