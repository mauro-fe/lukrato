<?php

/**
 * Migration: Adicionar campos de recorrência e lembrete na tabela lancamentos
 * 
 * Unificação: Agendamentos → Lançamentos
 * 
 * Novos campos:
 * - recorrente: BOOLEAN - se é lançamento recorrente
 * - recorrencia_freq: VARCHAR - semanal, quinzenal, mensal, bimestral, trimestral, semestral, anual
 * - recorrencia_fim: DATE - data de fim da recorrência (null = infinito)
 * - recorrencia_pai_id: INT FK - referência ao primeiro lançamento do grupo recorrente
 * - cancelado_em: DATETIME - quando a recorrência/lançamento futuro foi cancelado
 * - lembrar_antes_segundos: INT - tempo de antecedência do lembrete (em segundos)
 * - canal_email: BOOLEAN - notificar por e-mail
 * - canal_inapp: BOOLEAN - notificar no sistema
 * - notificado_em: DATETIME - quando o lembrete foi disparado
 * - lembrete_antecedencia_em: DATETIME - quando o lembrete de antecedência foi enviado
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campos de recorrência e lembrete aos lançamentos ===\n\n";

        $columns = DB::select("SHOW COLUMNS FROM lancamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        $newColumns = [
            'recorrente' => "ALTER TABLE lancamentos ADD COLUMN recorrente TINYINT(1) NOT NULL DEFAULT 0 AFTER origem_tipo",
            'recorrencia_freq' => "ALTER TABLE lancamentos ADD COLUMN recorrencia_freq VARCHAR(20) NULL DEFAULT NULL AFTER recorrente",
            'recorrencia_fim' => "ALTER TABLE lancamentos ADD COLUMN recorrencia_fim DATE NULL DEFAULT NULL AFTER recorrencia_freq",
            'recorrencia_pai_id' => "ALTER TABLE lancamentos ADD COLUMN recorrencia_pai_id INT UNSIGNED NULL DEFAULT NULL AFTER recorrencia_fim",
            'cancelado_em' => "ALTER TABLE lancamentos ADD COLUMN cancelado_em DATETIME NULL DEFAULT NULL AFTER recorrencia_pai_id",
            'lembrar_antes_segundos' => "ALTER TABLE lancamentos ADD COLUMN lembrar_antes_segundos INT NULL DEFAULT NULL AFTER cancelado_em",
            'canal_email' => "ALTER TABLE lancamentos ADD COLUMN canal_email TINYINT(1) NOT NULL DEFAULT 0 AFTER lembrar_antes_segundos",
            'canal_inapp' => "ALTER TABLE lancamentos ADD COLUMN canal_inapp TINYINT(1) NOT NULL DEFAULT 0 AFTER canal_email",
            'notificado_em' => "ALTER TABLE lancamentos ADD COLUMN notificado_em DATETIME NULL DEFAULT NULL AFTER canal_inapp",
            'lembrete_antecedencia_em' => "ALTER TABLE lancamentos ADD COLUMN lembrete_antecedencia_em DATETIME NULL DEFAULT NULL AFTER notificado_em",
        ];

        foreach ($newColumns as $name => $sql) {
            if (!in_array($name, $columnNames)) {
                DB::statement($sql);
                echo "✅ Coluna '{$name}' adicionada.\n";
            } else {
                echo "ℹ️ Coluna '{$name}' já existe.\n";
            }
        }

        // Índice para busca de lançamentos recorrentes pendentes
        try {
            DB::statement("CREATE INDEX idx_lancamentos_recorrencia ON lancamentos (recorrente, recorrencia_pai_id)");
            echo "✅ Índice 'idx_lancamentos_recorrencia' criado.\n";
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                echo "ℹ️ Índice 'idx_lancamentos_recorrencia' já existe.\n";
            } else {
                echo "⚠️ Erro ao criar índice: {$e->getMessage()}\n";
            }
        }

        // Índice para busca de lançamentos futuros não pagos (lembretes)
        try {
            DB::statement("CREATE INDEX idx_lancamentos_lembretes ON lancamentos (pago, data, notificado_em)");
            echo "✅ Índice 'idx_lancamentos_lembretes' criado.\n";
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                echo "ℹ️ Índice 'idx_lancamentos_lembretes' já existe.\n";
            } else {
                echo "⚠️ Erro ao criar índice: {$e->getMessage()}\n";
            }
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campos de recorrência e lembrete dos lançamentos ===\n\n";

        $columns = [
            'recorrente',
            'recorrencia_freq',
            'recorrencia_fim',
            'recorrencia_pai_id',
            'cancelado_em',
            'lembrar_antes_segundos',
            'canal_email',
            'canal_inapp',
            'notificado_em',
            'lembrete_antecedencia_em',
        ];

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE lancamentos DROP COLUMN IF EXISTS {$col}");
            echo "✅ Coluna '{$col}' removida.\n";
        }

        try {
            DB::statement("DROP INDEX idx_lancamentos_recorrencia ON lancamentos");
        } catch (\Throwable) {
        }
        try {
            DB::statement("DROP INDEX idx_lancamentos_lembretes ON lancamentos");
        } catch (\Throwable) {
        }

        echo "\n✅ Rollback concluído!\n";
    }
};
