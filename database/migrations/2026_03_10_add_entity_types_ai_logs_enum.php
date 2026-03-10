<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('ai_logs')) {
            echo "• Tabela ai_logs não existe — ignorando\n";
            return;
        }

        Capsule::connection()->statement(
            "ALTER TABLE ai_logs MODIFY COLUMN `type` ENUM('chat', 'suggest_category', 'analyze_spending', 'extract_transaction', 'quick_query', 'create_entity', 'confirm_action') NOT NULL"
        );

        echo "✅ Enum ai_logs.type atualizado com create_entity e confirm_action\n";
    }

    public function down(): void
    {
        if (!Capsule::schema()->hasTable('ai_logs')) {
            return;
        }

        Capsule::connection()->statement(
            "ALTER TABLE ai_logs MODIFY COLUMN `type` ENUM('chat', 'suggest_category', 'analyze_spending', 'extract_transaction', 'quick_query') NOT NULL"
        );
    }
};
