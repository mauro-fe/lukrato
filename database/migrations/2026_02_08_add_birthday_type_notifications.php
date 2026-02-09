<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Adiciona tipo 'birthday' ao enum de notificações
 * 
 * Permite notificações de aniversário no sistema.
 */

return new class
{
    public function up(): void
    {
        // Modificar o ENUM para incluir 'birthday'
        Capsule::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'promo', 'update', 'alert', 'success', 'reminder', 'birthday') DEFAULT 'info'");
        
        echo "✅ Tipo 'birthday' adicionado ao ENUM de notificações\n";
    }

    public function down(): void
    {
        // Remover 'birthday' do ENUM (reverter para original)
        Capsule::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'promo', 'update', 'alert', 'success', 'reminder') DEFAULT 'info'");
        
        echo "✅ Tipo 'birthday' removido do ENUM de notificações\n";
    }
};
