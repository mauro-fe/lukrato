<?php

/**
 * Migration: Adiciona campos de lembrete de fatura ao cartão de crédito
 *
 * Campos:
 * - lembrar_fatura_antes_segundos: segundos antes do vencimento para lembrar
 * - fatura_canal_email: enviar lembrete por email
 * - fatura_canal_inapp: enviar lembrete in-app
 * - fatura_notificado_mes: último mês/ano que foi notificado (YYYY-MM) para evitar duplicatas
 */

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Migration: Adicionar lembrete de fatura aos cartões ===\n\n";

$columns = [
    'lembrar_fatura_antes_segundos' => "ALTER TABLE cartoes_credito ADD COLUMN lembrar_fatura_antes_segundos INT NULL DEFAULT NULL AFTER arquivado",
    'fatura_canal_email' => "ALTER TABLE cartoes_credito ADD COLUMN fatura_canal_email TINYINT(1) NOT NULL DEFAULT 0 AFTER lembrar_fatura_antes_segundos",
    'fatura_canal_inapp' => "ALTER TABLE cartoes_credito ADD COLUMN fatura_canal_inapp TINYINT(1) NOT NULL DEFAULT 1 AFTER fatura_canal_email",
    'fatura_notificado_mes' => "ALTER TABLE cartoes_credito ADD COLUMN fatura_notificado_mes VARCHAR(7) NULL DEFAULT NULL AFTER fatura_canal_inapp",
];

foreach ($columns as $col => $sql) {
    try {
        $exists = DB::select("SHOW COLUMNS FROM cartoes_credito LIKE '{$col}'");
        if (!empty($exists)) {
            echo "  ⏭  Coluna '{$col}' já existe.\n";
            continue;
        }

        DB::statement($sql);
        echo "  ✅ Coluna '{$col}' adicionada com sucesso.\n";
    } catch (\Throwable $e) {
        echo "  ❌ Erro ao adicionar '{$col}': " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Migration concluída!\n";
