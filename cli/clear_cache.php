<?php
/**
 * Script para limpar cache do sistema
 */
require_once __DIR__ . '/../bootstrap.php';

use Application\Services\CacheService;

echo "=== Limpando Cache ===\n\n";

// 1. Limpar cache de arquivos
$cacheDir = BASE_PATH . '/storage/cache';
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $count = 0;
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if ($todo($fileinfo->getRealPath())) {
            $count++;
        }
    }
    echo "[OK] Cache de arquivos limpo ({$count} itens removidos)\n";
} else {
    echo "[--] Pasta storage/cache não existe\n";
}

// 2. Limpar cache Redis
try {
    $cache = new CacheService();
    if ($cache->isEnabled()) {
        if ($cache->flush()) {
            echo "[OK] Redis cache limpo!\n";
        } else {
            echo "[ERRO] Falha ao limpar Redis\n";
        }
    } else {
        echo "[--] Redis não está habilitado\n";
    }
} catch (Throwable $e) {
    echo "[ERRO] Redis: " . $e->getMessage() . "\n";
}

echo "\n=== Concluído ===\n";
