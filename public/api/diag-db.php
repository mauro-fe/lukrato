<?php
/**
 * Debug endpoint para diagnosticar conectividade com banco
 * REMOVER APÓS DIAGNÓSTICO
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
$timings = [];

set_time_limit(10);
ini_set('max_execution_time', 10);

// Bootstrap
require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\InstituicaoFinanceira;

try {
    // 1. PDO Connect
    $t1 = microtime(true);
    $pdo = DB::connection()->getPdo();
    $timings['pdo_connect'] = round((microtime(true) - $t1) * 1000, 2);
    
    // 2. Simple SELECT
    $t2 = microtime(true);
    $pdo->query('SELECT 1')->fetch();
    $timings['simple_select'] = round((microtime(true) - $t2) * 1000, 2);
    
    // 3. Count instituicoes
    $t3 = microtime(true);
    $count = InstituicaoFinanceira::count();
    $timings['count_instituicoes'] = round((microtime(true) - $t3) * 1000, 2);
    
    // 4. List instituicoes
    $t4 = microtime(true);
    $list = InstituicaoFinanceira::ativas()->orderBy('nome')->limit(5)->get();
    $timings['list_instituicoes_5'] = round((microtime(true) - $t4) * 1000, 2);
    
    $timings['total'] = round((microtime(true) - $startTime) * 1000, 2);
    
    echo json_encode([
        'status' => 'OK',
        'db_driver' => DB_DRIVER,
        'instituicoes_count' => $count,
        'timings_ms' => $timings,
        'server_time' => date('Y-m-d H:i:s'),
        'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
    ], JSON_PRETTY_PRINT);
    
} catch (\Throwable $e) {
    $timings['total'] = round((microtime(true) - $startTime) * 1000, 2);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'timings_ms' => $timings
    ], JSON_PRETTY_PRINT);
}
