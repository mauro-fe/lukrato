<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICANDO COLUNA theme_preference ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM usuarios');
$hasThemePreference = false;

foreach ($columns as $col) {
    if ($col->Field === 'theme_preference') {
        $hasThemePreference = true;
        echo "✓ Coluna theme_preference encontrada\n";
        echo "  Tipo: {$col->Type}\n";
        echo "  Null: {$col->Null}\n";
        echo "  Default: {$col->Default}\n";
        break;
    }
}

if (!$hasThemePreference) {
    echo "✗ Coluna theme_preference NÃO existe\n";
    echo "\nAdicionando coluna...\n";

    try {
        DB::statement("ALTER TABLE usuarios ADD COLUMN theme_preference VARCHAR(10) DEFAULT 'dark' AFTER username");
        echo "✓ Coluna adicionada com sucesso!\n";
    } catch (Exception $e) {
        echo "✗ Erro ao adicionar coluna: " . $e->getMessage() . "\n";
    }
}
