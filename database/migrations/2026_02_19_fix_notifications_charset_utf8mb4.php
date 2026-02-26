<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Corrige charset da tabela notifications para utf8mb4
 * 
 * A tabela foi criada com latin1 ao invés de utf8mb4,
 * causando emojis serem armazenados como "?" nos campos title/message.
 */

return new class
{
    public function up(): void
    {
        $pdo = Capsule::connection()->getPdo();

        // Verificar charset atual
        $stmt = $pdo->prepare(
            "SELECT TABLE_COLLATION FROM information_schema.TABLES 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications'"
        );
        $stmt->execute();
        $current = $stmt->fetchColumn();

        if ($current && stripos($current, 'utf8mb4') === false) {
            $pdo->exec(
                "ALTER TABLE notifications 
                 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            echo "✅ Tabela 'notifications' convertida para utf8mb4_unicode_ci\n";
        } else {
            echo "⚠️ Tabela 'notifications' já está em utf8mb4\n";
        }
    }

    public function down(): void
    {
        // Não reverter — latin1 nunca deveria ter sido usado
        echo "⚠️ Down não reverte charset (utf8mb4 é o correto)\n";
    }
};
