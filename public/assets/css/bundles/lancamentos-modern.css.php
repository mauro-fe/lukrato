<?php

/**
 * Lançamentos CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta lancamentos/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../lancamentos';

$modules = [
    '_base-layout.css',
    '_export-controls.css',
    '_buttons.css',
    '_filters.css',
    '_table-wrapper.css',
    '_table.css',
    '_pagination.css',
    '_checkboxes.css',
    '_responsive.css',
    '_animations.css',
    '_pro-lock.css',
    '_system-layout.css',
    '_system-responsive.css',
    '_system-filters-table.css',
    '_badges.css',
    '_modal-edit.css',
    '_accessibility.css',
    '_mobile-table.css',
    '_mobile-cards.css',
    '_card-components.css',
    '_complementary.css',
    '_parcelamento.css',
    '_table-details.css',
    '_table-badges-filters.css',
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

$etag = '"lanc-' . md5($lastModified) . '"';

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
