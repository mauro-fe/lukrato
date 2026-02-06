<?php
/**
 * Script para gerenciar migrations antigas
 * 
 * Uso:
 *   php cli/manage_migrations.php list      - Lista migrations executadas
 *   php cli/manage_migrations.php clean     - Remove arquivos de migrations já executadas
 *   php cli/manage_migrations.php consolidate - Consolida todas em uma única migration
 */

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$action = $argv[1] ?? 'list';

echo "=== Gerenciador de Migrations ===\n\n";

// Lista migrations executadas
$executed = DB::table('migrations')->orderBy('id')->pluck('migration')->toArray();

echo "Migrations executadas no banco: " . count($executed) . "\n";
echo str_repeat('-', 60) . "\n";

foreach ($executed as $migration) {
    echo "  ✓ {$migration}\n";
}

// Lista arquivos na pasta
$files = glob(__DIR__ . '/../database/migrations/*.php');
$fileNames = array_map(function($f) {
    return pathinfo($f, PATHINFO_FILENAME);
}, $files);

echo "\n" . str_repeat('-', 60) . "\n";
echo "Arquivos na pasta migrations: " . count($files) . "\n";

// Arquivos que NÃO estão no banco (pendentes ou lixo)
$notExecuted = array_diff($fileNames, $executed);
$onlyInDb = array_diff($executed, $fileNames);

if (!empty($notExecuted)) {
    echo "\n⚠️  Arquivos NÃO executados (pendentes):\n";
    foreach ($notExecuted as $file) {
        // Ignora arquivos especiais
        if (in_array($file, ['run_migration.php', 'seed_achievements'])) continue;
        echo "  - {$file}.php\n";
    }
}

if (!empty($onlyInDb)) {
    echo "\n🗑️  No banco mas sem arquivo (podem ser removidos do banco):\n";
    foreach ($onlyInDb as $migration) {
        echo "  - {$migration}\n";
    }
}

if ($action === 'clean') {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "LIMPEZA DE MIGRATIONS\n";
    echo str_repeat('=', 60) . "\n\n";
    
    echo "Isso irá:\n";
    echo "1. Mover migrations já executadas para 'database/migrations/_archived/'\n";
    echo "2. Manter apenas migrations recentes (últimos 30 dias)\n\n";
    
    echo "Deseja continuar? (s/n): ";
    $confirm = trim(fgets(STDIN));
    
    if (strtolower($confirm) !== 's') {
        echo "Operação cancelada.\n";
        exit(0);
    }
    
    // Cria pasta de arquivo
    $archiveDir = __DIR__ . '/../database/migrations/_archived';
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }
    
    // Data limite (30 dias atrás)
    $cutoffDate = date('Y_m_d', strtotime('-30 days'));
    
    $moved = 0;
    foreach ($files as $file) {
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        
        // Ignora arquivos especiais
        if (in_array($fileName, ['run_migration.php', 'seed_achievements'])) continue;
        
        // Extrai a data do nome do arquivo
        preg_match('/^(\d{4}[-_]\d{2}[-_]\d{2})/', $fileName, $matches);
        $fileDate = $matches[1] ?? null;
        
        if ($fileDate) {
            $fileDate = str_replace('-', '_', $fileDate);
            
            // Se a migration foi executada E é antiga, arquiva
            if (in_array($fileName, $executed) && $fileDate < $cutoffDate) {
                $dest = $archiveDir . '/' . basename($file);
                if (rename($file, $dest)) {
                    echo "📦 Arquivado: {$fileName}.php\n";
                    $moved++;
                }
            }
        }
    }
    
    echo "\n✅ {$moved} arquivo(s) arquivado(s) em database/migrations/_archived/\n";
}

echo "\n";
