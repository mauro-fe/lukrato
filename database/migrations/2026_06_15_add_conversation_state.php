<?php

declare(strict_types=1);

/**
 * Migração: Adiciona colunas state/state_data na tabela ai_conversations
 * para suportar fluxos multi-turno (coleta de dados em várias mensagens).
 *
 * state: 'idle' (padrão), 'collecting_entity', 'awaiting_selection'
 * state_data: JSON com dados parciais do fluxo ativo
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class
{
    public function up(): void
    {
        $tableName = 'ai_conversations';
        $schema = DB::schema();

        if (!$schema->hasTable($tableName)) {
            echo "❌ Tabela {$tableName} não existe.\n";
            return;
        }

        if ($schema->hasColumn($tableName, 'state')) {
            echo "⚠️  Coluna 'state' já existe em {$tableName}. Pulando.\n";
            return;
        }

        $schema->table($tableName, function (Blueprint $table) {
            $table->string('state', 30)->default('idle')->after('titulo');
            $table->json('state_data')->nullable()->after('state');
            $table->index('state');
        });

        echo "✅ Colunas 'state' e 'state_data' adicionadas.\n";
    }

    public function down(): void
    {
        DB::schema()->table('ai_conversations', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropColumn(['state', 'state_data']);
        });
    }
};
