<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Adiciona status 'scheduled' e 'cancelled' à tabela message_campaigns.
 * Migra campanhas existentes com scheduled_at de 'draft' para 'scheduled'.
 */
return new class {
    public function up(): void
    {
        // Alterar ENUM para incluir novos valores
        DB::statement("ALTER TABLE message_campaigns MODIFY COLUMN status ENUM('draft','sending','sent','failed','partial','scheduled','cancelled') NOT NULL DEFAULT 'draft'");

        // Migrar campanhas agendadas existentes de draft para scheduled
        DB::table('message_campaigns')
            ->where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->update(['status' => 'scheduled']);

        // Campanhas presas em 'sending' por mais de 30 minutos → failed
        DB::table('message_campaigns')
            ->where('status', 'sending')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'failed']);
    }

    public function down(): void
    {
        // Reverter scheduled → draft, cancelled → failed
        DB::table('message_campaigns')
            ->where('status', 'scheduled')
            ->update(['status' => 'draft']);

        DB::table('message_campaigns')
            ->where('status', 'cancelled')
            ->update(['status' => 'failed']);

        DB::statement("ALTER TABLE message_campaigns MODIFY COLUMN status ENUM('draft','sending','sent','failed','partial') NOT NULL DEFAULT 'draft'");
    }
};
