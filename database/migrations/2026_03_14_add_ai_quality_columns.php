<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Adiciona colunas de observabilidade de qualidade da IA:
 * - source: origem da resposta (rule, llm, cache, computed)
 * - confidence: confiança do intent detectado (0.0 - 1.0)
 * - prompt_version: versão do prompt usado
 */
return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('ai_logs')) {
            echo "• Tabela ai_logs não existe — ignorando\n";
            return;
        }

        // Adicionar source
        if (!Capsule::schema()->hasColumn('ai_logs', 'source')) {
            Capsule::schema()->table('ai_logs', function ($table) {
                $table->string('source', 20)->nullable()->after('error_message');
            });
            echo "✅ Coluna ai_logs.source adicionada\n";
        }

        // Adicionar confidence
        if (!Capsule::schema()->hasColumn('ai_logs', 'confidence')) {
            Capsule::schema()->table('ai_logs', function ($table) {
                $table->float('confidence')->nullable()->after('source');
                $table->index('confidence', 'idx_ai_logs_confidence');
            });
            echo "✅ Coluna ai_logs.confidence adicionada\n";
        }

        // Adicionar prompt_version
        if (!Capsule::schema()->hasColumn('ai_logs', 'prompt_version')) {
            Capsule::schema()->table('ai_logs', function ($table) {
                $table->string('prompt_version', 10)->nullable()->after('confidence');
            });
            echo "✅ Coluna ai_logs.prompt_version adicionada\n";
        }
    }

    public function down(): void
    {
        if (!Capsule::schema()->hasTable('ai_logs')) {
            return;
        }

        Capsule::schema()->table('ai_logs', function ($table) {
            if (Capsule::schema()->hasColumn('ai_logs', 'source')) {
                $table->dropColumn('source');
            }
            if (Capsule::schema()->hasColumn('ai_logs', 'confidence')) {
                $table->dropIndex('idx_ai_logs_confidence');
                $table->dropColumn('confidence');
            }
            if (Capsule::schema()->hasColumn('ai_logs', 'prompt_version')) {
                $table->dropColumn('prompt_version');
            }
        });
    }
};
