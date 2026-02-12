<?php

/**
 * Migration: Adiciona coluna email_verification_reminder_sent_at na tabela usuarios
 * 
 * Controla se o lembrete de verificação de email já foi enviado para evitar duplicidade.
 */

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Migration: add email_verification_reminder_sent_at ===" . PHP_EOL;

try {
    // Verifica se a coluna já existe
    $columns = DB::select("SHOW COLUMNS FROM usuarios LIKE 'email_verification_reminder_sent_at'");

    if (!empty($columns)) {
        echo "Coluna 'email_verification_reminder_sent_at' já existe. Pulando." . PHP_EOL;
        exit(0);
    }

    DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_reminder_sent_at TIMESTAMP NULL DEFAULT NULL AFTER email_verification_sent_at');

    echo "Coluna 'email_verification_reminder_sent_at' adicionada com sucesso!" . PHP_EOL;
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
