<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$pdo = DB::connection()->getPdo();

echo "=== ESTRUTURA DA TABELA documentos ===\n";
$stmt = $pdo->query('DESCRIBE documentos');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== ESTRUTURA DA TABELA enderecos ===\n";
$stmt = $pdo->query('DESCRIBE enderecos');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
