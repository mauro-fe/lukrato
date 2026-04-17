<?php
$dir = __DIR__ . '/routes/api';
$files = glob($dir . '/*.php');
sort($files);
$legacyRoutes = [];
$v1Routes = [];
$all = [];
foreach ($files as $file) {
    $text = file_get_contents($file);
    if (preg_match_all("/Router::add\(\s*'([^']+)'\s*,\s*'([^']+)'/", $text, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $method = $match[1];
            $path = $match[2];
            $row = ['file' => basename($file), 'method' => $method, 'path' => $path];
            $all[] = $row;
            if (strpos($path, '/api/v1/') === 0) {
                $v1Routes[] = $row;
            } elseif (strpos($path, '/api/') === 0 && strpos($path, '/api/webhook/') !== 0) {
                $legacyRoutes[] = $row;
            }
        }
    }
}
$v1Paths = [];
foreach ($v1Routes as $r) { $v1Paths[$r['path']] = true; }
$missing = [];
foreach ($legacyRoutes as $r) {
    $candidate = '/api/v1/' . substr($r['path'], strlen('/api/'));
    if (!isset($v1Paths[$candidate])) {
        $missing[basename($r['file'])][$r['path']][] = $r['method'];
    }
}
ksort($missing);
$result = [
    'legacy_route_count' => count($legacyRoutes),
    'legacy_unique_path_count' => count(array_unique(array_column($legacyRoutes, 'path'))),
    'v1_route_count' => count($v1Routes),
    'v1_unique_path_count' => count(array_unique(array_column($v1Routes, 'path'))),
    'missing' => $missing,
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
