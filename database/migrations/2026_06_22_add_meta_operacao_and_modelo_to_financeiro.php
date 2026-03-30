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
            $schema->table('metas', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('metas', 'modelo')) {
                    $table->string('modelo', 20)->default('reserva')->after('tipo');
                }

                if (!$schema->hasColumn('metas', 'valor_realizado')) {
                    $table->decimal('valor_realizado', 12, 2)->default(0)->after('valor_alocado');
                }
            });

            Capsule::statement("UPDATE metas SET modelo = 'reserva' WHERE modelo IS NULL OR modelo = ''");
            Capsule::statement('UPDATE metas SET valor_realizado = 0 WHERE valor_realizado IS NULL');
        }

        if ($schema->hasTable('lancamentos')) {
            $schema->table('lancamentos', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('lancamentos', 'meta_operacao')) {
                    $table->string('meta_operacao', 20)->nullable()->after('meta_id');
                }

                if (!$schema->hasColumn('lancamentos', 'meta_valor')) {
                    $table->decimal('meta_valor', 12, 2)->nullable()->after('meta_operacao');
                }
            });

            Capsule::statement(
                "UPDATE lancamentos l
                 JOIN metas m ON m.id = l.meta_id
                 SET l.meta_operacao = CASE
                    WHEN l.meta_id IS NULL THEN NULL
                    WHEN l.meta_operacao IS NOT NULL AND l.meta_operacao <> '' THEN l.meta_operacao
                    WHEN l.eh_transferencia = 1 OR l.tipo = 'receita' THEN 'aporte'
                    WHEN l.tipo = 'despesa' AND m.modelo = 'realizacao' THEN 'realizacao'
                    WHEN l.tipo = 'despesa' THEN 'resgate'
                    ELSE NULL
                 END"
            );

            Capsule::statement(
                "UPDATE lancamentos
                 SET meta_valor = valor
                 WHERE meta_id IS NOT NULL
                   AND (meta_valor IS NULL OR meta_valor <= 0)"
            );
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('lancamentos')) {
            $schema->table('lancamentos', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('lancamentos', 'meta_valor')) {
                    $table->dropColumn('meta_valor');
                }
                if ($schema->hasColumn('lancamentos', 'meta_operacao')) {
                    $table->dropColumn('meta_operacao');
                }
            });
        }

        if ($schema->hasTable('metas')) {
            $schema->table('metas', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('metas', 'valor_realizado')) {
                    $table->dropColumn('valor_realizado');
                }
                if ($schema->hasColumn('metas', 'modelo')) {
                    $table->dropColumn('modelo');
                }
            });
        }
    }
};

