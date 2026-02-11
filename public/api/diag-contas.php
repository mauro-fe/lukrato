<?php
/**
 * Debug endpoint para diagnosticar travamento nas contas
 * REMOVER APÓS DIAGNÓSTICO
 */

// Headers CORS
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Medir tempo
$startTime = microtime(true);
$timings = [];

// Configurar timeout mais curto para não travar
set_time_limit(15);
ini_set('max_execution_time', 15);

// Bootstrap
require_once __DIR__ . '/../../bootstrap.php';

use Application\Lib\Auth;
use Application\Models\Conta;
use Application\Models\InstituicaoFinanceira;
use Illuminate\Database\Capsule\Manager as DB;

try {
    // 1. Verificar sessão
    $t1 = microtime(true);
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    $timings['session'] = round((microtime(true) - $t1) * 1000, 2) . 'ms';
    
    if (!$userId) {
        echo json_encode(['error' => 'Não autenticado', 'timings' => $timings]);
        exit;
    }
    
    // 2. Teste de conexão com banco
    $t2 = microtime(true);
    try {
        DB::connection()->getPdo();
        $timings['db_connect'] = round((microtime(true) - $t2) * 1000, 2) . 'ms';
    } catch (\Exception $e) {
        $timings['db_connect'] = 'ERRO: ' . $e->getMessage();
        echo json_encode(['error' => 'Falha na conexão com banco', 'timings' => $timings]);
        exit;
    }
    
    // 3. Query simples de teste
    $t3 = microtime(true);
    $testQuery = DB::select('SELECT 1 as test');
    $timings['db_simple_query'] = round((microtime(true) - $t3) * 1000, 2) . 'ms';
    
    // 4. Contar instituições
    $t4 = microtime(true);
    $instCount = InstituicaoFinanceira::count();
    $timings['instituicoes_count'] = round((microtime(true) - $t4) * 1000, 2) . 'ms';
    
    // 5. Listar instituições
    $t5 = microtime(true);
    $instituicoes = InstituicaoFinanceira::ativas()->orderBy('nome')->get();
    $timings['instituicoes_list'] = round((microtime(true) - $t5) * 1000, 2) . 'ms';
    
    // 6. Contar contas do usuário
    $t6 = microtime(true);
    $contasCount = Conta::where('user_id', $userId)->count();
    $timings['contas_count'] = round((microtime(true) - $t6) * 1000, 2) . 'ms';
    
    // 7. Listar contas sem saldos
    $t7 = microtime(true);
    $contas = Conta::forUser($userId)
        ->with('instituicaoFinanceira')
        ->ativas()
        ->orderBy('created_at', 'desc')
        ->get();
    $timings['contas_list'] = round((microtime(true) - $t7) * 1000, 2) . 'ms';
    
    // Tempo total
    $timings['total'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';
    
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'instituicoes_count' => $instCount,
        'contas_count' => $contasCount,
        'timings' => $timings,
        'php_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
    ], JSON_PRETTY_PRINT);
    
} catch (\Throwable $e) {
    $timings['error'] = $e->getMessage();
    $timings['total'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';
    
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timings' => $timings
    ], JSON_PRETTY_PRINT);
}
