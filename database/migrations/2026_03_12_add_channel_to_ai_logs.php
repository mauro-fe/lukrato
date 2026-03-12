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

        if (Capsule::schema()->hasColumn('ai_logs', 'channel')) {
            echo "• Coluna ai_logs.channel já existe — ignorando\n";
            return;
        }

        Capsule::schema()->table('ai_logs', function ($table) {
            $table->string('channel', 20)->default('web')->after('type');
            $table->index('channel', 'idx_ai_logs_channel');
        });

        echo "✅ Coluna ai_logs.channel adicionada com índice\n";
    }

    public function down(): void
    {
        if (!Capsule::schema()->hasTable('ai_logs')) {
            return;
        }

        Capsule::schema()->table('ai_logs', function ($table) {
            $table->dropIndex('idx_ai_logs_channel');
            $table->dropColumn('channel');
        });
    }
};
