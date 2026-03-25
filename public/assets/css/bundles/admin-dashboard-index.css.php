<?php

/**
 * Dashboard CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta dashboard/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../pages/admin-dashboard';

$modules = [
    '_modern-dashboard.css',
    '_dashboard-page.css',
    '_cards.css',
    '_charts-grid.css',
    '_table-card.css',
    '_provisao.css',
    '_gamification.css',
    '_responsive.css',
    '_customize-modal.css',
    '_light-theme.css',
    'floating-action-button.css'
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

$etag = '"dash-' . md5($lastModified) . '"';

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
