<?php

/**
 * Categorias CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta categorias/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../categorias';

$modules = [
    '_base.css',
    '_icons.css',
    '_icon-picker-trigger.css',
    '_icon-picker-drawer.css',
    '_suggestions.css',
    '_edit-modal-picker.css',
    '_grid-categorias.css',
    '_card-individual.css',
    '_orcamento-inline.css',
    '_responsive-base.css',
    '_responsive-tablet.css',
    '_responsive-mobile.css',
    '_responsive-small.css',
    '_modal-orcamento.css',
    '_subcategorias.css',
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

$etag = '"cat-' . md5($lastModified) . '"';

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
