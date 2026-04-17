<?php
$dir = __DIR__ . '/routes/api';
$files = glob($dir . '/*.php');
sort($files);
$stats = []; $legacy = 0; $v1 = 0; $legacyUnique = []; $v1Unique = [];
foreach ($files as $file) {
    $base = basename($file);
    $stats[$base] = ['legacy' => 0, 'v1' => 0, 'legacy_paths' => [], 'v1_paths' => []];
    $text = file_get_contents($file);
    if (preg_match_all("/Router::add\(\s*'([^']+)'\s*,\s*'([^']+)'/", $text, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $path = $match[2];
            if (strpos($path, '/api/v1/') === 0) { $v1++; $v1Unique[$path] = true; $stats[$base]['v1']++; $stats[$base]['v1_paths'][$path] = true; }
            elseif (strpos($path, '/api/') === 0 && strpos($path, '/api/webhook/') !== 0) { $legacy++; $legacyUnique[$path] = true; $stats[$base]['legacy']++; $stats[$base]['legacy_paths'][$path] = true; }
        }
    }
}
echo "TOTAL legacy=$legacy unique=" . count($legacyUnique) . " v1=$v1 unique=" . count($v1Unique) . PHP_EOL;
foreach ($stats as $file => $row) { if ($row['legacy'] || $row['v1']) { echo "$file legacy={$row['legacy']} (" . count($row['legacy_paths']) . ") v1={$row['v1']} (" . count($row['v1_paths']) . ")" . PHP_EOL; } }
