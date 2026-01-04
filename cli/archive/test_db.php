<?php

require dirname(__DIR__) . '/bootstrap.php';
require BASE_PATH . '/config/config.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    // Testa a conexão
    $pdo = Capsule::connection()->getPdo();
    
    echo "✓ Conexão com o banco de dados estabelecida com sucesso!\n\n";
    echo "Configuração:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Banco: " . DB_NAME . "\n";
    echo "Usuário: " . DB_USER . "\n\n";
    
    // Lista as tabelas
    $tables = Capsule::select("SHOW TABLES");
    
    if (!empty($tables)) {
        echo "Tabelas no banco de dados:\n";
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            echo "  - $tableName\n";
        }
    } else {
        echo "Nenhuma tabela encontrada no banco de dados.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "\n";
    exit(1);
}
