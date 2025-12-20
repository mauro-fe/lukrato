<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Carrega configuração do banco de dados
require BASE_PATH . '/config/config.php';

echo "Iniciando migrations...\n\n";

// Busca todos os arquivos de migration
$migrationsPath = BASE_PATH . '/database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

if (empty($migrationFiles)) {
    echo "Nenhuma migration encontrada.\n";
    exit(0);
}

foreach ($migrationFiles as $file) {
    echo "Executando: " . basename($file) . "\n";
    
    require_once $file;
    
    // Extrai o nome da classe do arquivo
    $className = str_replace('.php', '', basename($file));
    $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $className);
    $className = str_replace('_', '', ucwords($className, '_'));
    
    if (class_exists($className)) {
        $migration = new $className();
        
        try {
            $migration->up();
            echo "✓ " . basename($file) . " executada com sucesso!\n\n";
        } catch (Exception $e) {
            echo "✗ Erro ao executar " . basename($file) . ": " . $e->getMessage() . "\n\n";
        }
    }
}

echo "Migrations concluídas!\n";
