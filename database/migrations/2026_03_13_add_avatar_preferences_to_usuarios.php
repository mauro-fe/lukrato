<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('usuarios')) {
            echo "Tabela usuarios nao encontrada\n";
            return;
        }

        $hasFocusX = $schema->hasColumn('usuarios', 'avatar_focus_x');
        $hasFocusY = $schema->hasColumn('usuarios', 'avatar_focus_y');
        $hasZoom = $schema->hasColumn('usuarios', 'avatar_zoom');

        if ($hasFocusX && $hasFocusY && $hasZoom) {
            echo "Colunas de preferencias do avatar ja existem em usuarios\n";
            return;
        }

        $schema->table('usuarios', function ($table) use ($hasFocusX, $hasFocusY, $hasZoom, $schema) {
            $hasAvatar = $schema->hasColumn('usuarios', 'avatar');

            if (!$hasFocusX) {
                $col = $table->unsignedTinyInteger('avatar_focus_x')->default(50);
                if ($hasAvatar) $col->after('avatar');
            }

            if (!$hasFocusY) {
                $col = $table->unsignedTinyInteger('avatar_focus_y')->default(50);
                if ($hasFocusX || $hasAvatar) $col->after($hasFocusX ? 'avatar_focus_x' : 'avatar');
            }

            if (!$hasZoom) {
                $col = $table->decimal('avatar_zoom', 4, 2)->default(1.00);
                if ($hasFocusY || $hasAvatar) $col->after($hasFocusY ? 'avatar_focus_y' : 'avatar');
            }
        });

        echo "Preferencias do avatar adicionadas em usuarios\n";
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('usuarios')) {
            return;
        }

        $columns = [];

        if ($schema->hasColumn('usuarios', 'avatar_focus_x')) {
            $columns[] = 'avatar_focus_x';
        }

        if ($schema->hasColumn('usuarios', 'avatar_focus_y')) {
            $columns[] = 'avatar_focus_y';
        }

        if ($schema->hasColumn('usuarios', 'avatar_zoom')) {
            $columns[] = 'avatar_zoom';
        }

        if ($columns !== []) {
            $schema->table('usuarios', function ($table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
