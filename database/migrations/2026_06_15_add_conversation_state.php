<?php

declare(strict_types=1);

/**
 * Migração: Adiciona colunas state/state_data na tabela ai_conversations
 * para suportar fluxos multi-turno (coleta de dados em várias mensagens).
 *
 * state: 'idle' (padrão), 'collecting_entity', 'awaiting_selection'
 * state_data: JSON com dados parciais do fluxo ativo
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

$tableName = 'ai_conversations';

echo "=== Migração: Adicionar state/state_data em {$tableName} ===" . PHP_EOL;

$schema = DB::schema();

if (!$schema->hasTable($tableName)) {
    echo "❌ Tabela {$tableName} não existe. Execute a migração de criação primeiro." . PHP_EOL;
    exit(1);
}

if ($schema->hasColumn($tableName, 'state')) {
    echo "⚠️  Coluna 'state' já existe em {$tableName}. Pulando." . PHP_EOL;
} else {
    $schema->table($tableName, function (Blueprint $table) {
        $table->string('state', 30)->default('idle')->after('titulo');
        $table->json('state_data')->nullable()->after('state');
        $table->index('state');
    });
    echo "✅ Colunas 'state' e 'state_data' adicionadas com sucesso." . PHP_EOL;
}

echo "=== Migração concluída ===" . PHP_EOL;
