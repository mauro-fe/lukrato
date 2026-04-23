<?php
$files = glob(__DIR__ . '/routes/api/*.php');
sort($files);
$map = [];
foreach ($files as $file) {
    $base = basename($file);
    $text = file_get_contents($file);
    if (preg_match_all("/Router::add\(\s*'([^']+)'\s*,\s*'([^']+)'/", $text, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $path = $match[2];
            if (strpos($path, '/api/') === 0 && strpos($path, '/api/v1/') !== 0 && strpos($path, '/api/webhook/') !== 0) {
                $candidate = '/api/v1/' . substr($path, 5);
                $map[$base][$path] = $candidate;
            }
        }
    }
}
$allV1 = [];
foreach ($files as $file) {
    $text = file_get_contents($file);
    if (preg_match_all("/Router::add\(\s*'([^']+)'\s*,\s*'([^']+)'/", $text, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $path = $match[2];
            if (strpos($path, '/api/v1/') === 0) {
                $allV1[$path] = true;
            }
        }
    }
}
$missingCount = 0;
foreach ($map as $file => $paths) {
    foreach ($paths as $legacy => $v1) {
        if (!isset($allV1[$v1])) {
            echo "$file MISSING $legacy -> $v1\n";
            $missingCount++;
        }
    }
}
echo "missing_count=$missingCount\n";
