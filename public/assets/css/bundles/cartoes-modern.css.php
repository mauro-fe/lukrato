<?php

/**
 * Cartões CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta cartoes/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../cartoes';

$modules = [
    '_base-page.css',
    '_stats-grid.css',
    '_toolbar.css',
    '_container.css',
    '_credit-card.css',
    '_skeleton.css',
    '_list-view.css',
    '_responsive.css',
    '_modal-fatura.css',
    '_fatura-parcelamentos.css',
    '_dark-theme.css',
    '_header-actions.css',
    '_historico-faturas.css',
    '_alertas.css',
    '_error-state.css',
];

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

$etag = '"cart-' . md5($lastModified) . '"';

header('Content-Type: text/css; charset=UTF-8');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified)
) {
    http_response_code(304);
    exit;
}

$isProd = defined('APP_ENV') && APP_ENV === 'production';
if ($isProd) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: no-cache, must-revalidate');
}

foreach ($modules as $mod) {
    $path = $cssDir . '/' . $mod;
    if (file_exists($path)) {
        echo "/* === {$mod} === */\n";
        readfile($path);
        echo "\n\n";
    }
}
