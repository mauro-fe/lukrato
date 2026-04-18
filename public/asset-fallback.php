<?php

declare(strict_types=1);

/**
 * Resolve assets buildados do Vite quando o HTML em cache ainda aponta para um hash antigo.
 * Servimos o arquivo mais recente com o mesmo prefixo para evitar erro de MIME em modules.
 */

$asset = trim((string) ($_GET['asset'] ?? ''));

if ($asset === '' || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(js|css)$/', $asset, $matches) !== 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid asset request.';
    exit;
}

$extension = strtolower((string) ($matches[1] ?? ''));
$assetsDir = __DIR__ . '/build/assets';

if (!is_dir($assetsDir)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Build assets directory not found.';
    exit;
}

$filename = pathinfo($asset, PATHINFO_FILENAME);
$prefix = preg_replace('/-[^-]+$/', '', $filename);
$prefix = is_string($prefix) && $prefix !== '' ? $prefix : $filename;

$matchesByMtime = [];
$patterns = [
    $assetsDir . '/' . $prefix . '-*.' . $extension,
];

if (!str_ends_with($prefix, '-')) {
    $patterns[] = $assetsDir . '/' . $prefix . '.*';
}

foreach ($patterns as $pattern) {
    foreach (glob($pattern) ?: [] as $candidatePath) {
        if (!is_file($candidatePath)) {
            continue;
        }

        if (pathinfo($candidatePath, PATHINFO_EXTENSION) !== $extension) {
            continue;
        }

        $matchesByMtime[$candidatePath] = filemtime($candidatePath) ?: 0;
    }
}

if ($matchesByMtime === []) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Asset fallback not found.';
    exit;
}

arsort($matchesByMtime, SORT_NUMERIC);
$resolvedPath = array_key_first($matchesByMtime);

if (!is_string($resolvedPath) || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Resolved asset is not readable.';
    exit;
}

$contentType = $extension === 'css'
    ? 'text/css; charset=UTF-8'
    : 'application/javascript; charset=UTF-8';

header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=300, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Vite-Asset-Fallback: 1');
header('Content-Length: ' . (string) filesize($resolvedPath));

readfile($resolvedPath);
exit;
