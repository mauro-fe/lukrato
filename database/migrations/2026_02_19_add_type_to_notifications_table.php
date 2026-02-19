<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Adiciona coluna 'type' à tabela notifications (se não existir)
 * 
 * Corrige o erro: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'type'
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('notifications') && !$schema->hasColumn('notifications', 'type')) {
            Capsule::statement("ALTER TABLE notifications ADD COLUMN `type` ENUM('info', 'promo', 'update', 'alert', 'success', 'reminder', 'birthday') DEFAULT 'info' AFTER `link`");
            echo "✅ Coluna 'type' adicionada à tabela 'notifications'\n";
        } else {
            echo "⚠️ Coluna 'type' já existe na tabela 'notifications' ou tabela não encontrada\n";
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('notifications') && $schema->hasColumn('notifications', 'type')) {
            Capsule::statement("ALTER TABLE notifications DROP COLUMN `type`");
            echo "✅ Coluna 'type' removida da tabela 'notifications'\n";
        }
    }
};
