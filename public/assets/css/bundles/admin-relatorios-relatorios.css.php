<?php

/**
 * Relatórios CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta relatorios/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 * 
 * Em desenvolvimento: sempre fresco (sem cache).
 * Em produção: cache de 1 ano com ETag baseado em modificação dos arquivos.
 */

$cssDir = __DIR__ . '/../relatorios';

// Ordem de carregamento (mesma lógica dos @import anteriores)
$modules = [
    // Base
    '_layout.css',
    '_cards-charts.css',

    // Responsividade
    '_responsive.css',
    '_states-utils.css',
    '_responsive-extended.css',

    // Complementares
    '_complementary.css',
    '_skeleton.css',

    // Cartões
    '_cartoes.css',
    '_cartoes-responsive.css',

    // Insights & Comparativos
    '_insights.css',
    '_cartoes-credito.css',

    // Modal
    '_modal-cartao.css',
    '_modal-responsive.css',
];

// Calcular ETag baseado na última modificação de qualquer arquivo
$lastModified = 0;
foreach ($modules as $mod) {
    $path = $cssDir . '/' . $mod;
    if (file_exists($path)) {
        $mtime = filemtime($path);
        if ($mtime > $lastModified) {
            $lastModified = $mtime;
        }
    }
}

$etag = '"rel-' . md5($lastModified) . '"';

// Headers HTTP
header('Content-Type: text/css; charset=UTF-8');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

// Verificar cache do browser
if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified)
) {
    http_response_code(304);
    exit;
}

// Em produção, cache longo (1 ano); em dev, sem cache
$isProd = defined('APP_ENV') && APP_ENV === 'production';
if ($isProd) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: no-cache, must-revalidate');
}

// Concatenar e servir
foreach ($modules as $mod) {
    $path = $cssDir . '/' . $mod;
    if (file_exists($path)) {
        echo "/* === {$mod} === */\n";
        readfile($path);
        echo "\n\n";
    }
}
