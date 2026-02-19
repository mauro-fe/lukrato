<?php

/**
 * Migration: Adiciona coluna support_code na tabela usuarios
 * 
 * Código de suporte único, não sequencial, para identificação segura do usuário.
 * Formato: LUK-XXXX-XXXX (letras maiúsculas + números, sem ambiguidade)
 */

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Migration: add support_code to usuarios ===" . PHP_EOL;

try {
    // Verifica se a coluna já existe
    $columns = DB::select("SHOW COLUMNS FROM usuarios LIKE 'support_code'");

    if (!empty($columns)) {
        echo "Coluna 'support_code' já existe. Pulando." . PHP_EOL;
        return;
    }

    DB::statement('ALTER TABLE usuarios ADD COLUMN support_code VARCHAR(13) NULL DEFAULT NULL AFTER id');
    DB::statement('ALTER TABLE usuarios ADD UNIQUE INDEX idx_support_code (support_code)');

    echo "Coluna 'support_code' adicionada com sucesso!" . PHP_EOL;
    echo "Execute: php cli/generate_support_codes.php  para gerar códigos para usuários existentes." . PHP_EOL;
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
