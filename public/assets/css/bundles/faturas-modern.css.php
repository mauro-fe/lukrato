<?php

/**
 * Faturas CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta faturas/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../faturas';

$modules = [
    '_base-sweetalert.css',
    '_page-layout.css',
    '_cards.css',
    '_mobile-parcelas.css',
    '_responsive.css',
    '_parcelamento-card.css',
    '_toggle-parcela.css',
    '_filters.css',
    '_responsive-large.css',
    '_buttons.css',
    '_faturas-header.css',
    '_parcelamentos-grid.css',
    '_list-view.css',
    '_responsive-mobile.css',
    '_loading-modal.css',
    '_accessibility.css',
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

$etag = '"fat-' . md5($lastModified) . '"';

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
