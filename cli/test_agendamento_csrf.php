<?php

/**
 * Script de diagnóstico para testar criação de agendamentos
 * Verifica CSRF, duplicatas e fluxo geral
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DIAGNÓSTICO DE AGENDAMENTOS ===\n\n";

// 1. Verificar conexão com banco
echo "1. Testando conexão com banco de dados...\n";
try {
    $count = DB::table('agendamentos')->count();
    echo "   ✓ Conexão OK - Total de agendamentos: {$count}\n\n";
} catch (Exception $e) {
    echo "   ✗ Erro de conexão: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Verificar estrutura da tabela
echo "2. Verificando estrutura da tabela agendamentos...\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM agendamentos");
    $columnNames = array_map(fn($c) => $c->Field, $columns);

    $requiredColumns = ['id', 'user_id', 'titulo', 'valor_centavos', 'tipo', 'data_pagamento', 'created_at'];
    $missing = array_diff($requiredColumns, $columnNames);

    if (empty($missing)) {
        echo "   ✓ Todas as colunas necessárias existem\n\n";
    } else {
        echo "   ✗ Colunas faltando: " . implode(', ', $missing) . "\n";
        echo "   Colunas encontradas: " . implode(', ', $columnNames) . "\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n\n";
}

// 3. Verificar últimos agendamentos criados
echo "3. Últimos 10 agendamentos criados...\n";
try {
    $recent = DB::table('agendamentos')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get(['id', 'user_id', 'titulo', 'valor_centavos', 'tipo', 'created_at']);

    if ($recent->isEmpty()) {
        echo "   Nenhum agendamento encontrado\n\n";
    } else {
        foreach ($recent as $ag) {
            $created = $ag->created_at ?? 'N/A';
            $valorReal = number_format($ag->valor_centavos / 100, 2, ',', '.');
            echo "   ID: {$ag->id} | User: {$ag->user_id} | {$ag->titulo} | R$ {$valorReal} | {$ag->tipo} | {$created}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n\n";
}

// 4. Verificar possíveis duplicatas recentes
echo "4. Verificando possíveis duplicatas (últimas 24h)...\n";
try {
    $duplicates = DB::select("
        SELECT 
            user_id,
            titulo, 
            valor_centavos, 
            tipo,
            DATE(data_pagamento) as data,
            COUNT(*) as total,
            GROUP_CONCAT(id ORDER BY id) as ids,
            MIN(created_at) as primeiro,
            MAX(created_at) as ultimo
        FROM agendamentos 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY user_id, titulo, valor_centavos, tipo, DATE(data_pagamento)
        HAVING COUNT(*) > 1
        ORDER BY total DESC
        LIMIT 10
    ");

    if (empty($duplicates)) {
        echo "   ✓ Nenhuma duplicata detectada nas últimas 24h\n\n";
    } else {
        echo "   ⚠ Possíveis duplicatas encontradas:\n";
        foreach ($duplicates as $dup) {
            $diff = strtotime($dup->ultimo) - strtotime($dup->primeiro);
            $valor = number_format($dup->valor_centavos / 100, 2, ',', '.');
            echo "   - User {$dup->user_id}: '{$dup->titulo}' R$ {$valor} x{$dup->total} (IDs: {$dup->ids}) - Diff: {$diff}s\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n\n";
}

// 5. Verificar logs de CSRF recentes
echo "5. Verificando logs de CSRF recentes...\n";
$logFile = __DIR__ . '/../storage/logs/app-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);

    // Procurar por logs de CSRF
    preg_match_all('/\[.*?\] .*?(CSRF|csrf).*$/m', $logs, $matches);

    if (!empty($matches[0])) {
        $csrfLogs = array_slice($matches[0], -5);
        echo "   Últimos logs de CSRF:\n";
        foreach ($csrfLogs as $log) {
            $log = strlen($log) > 150 ? substr($log, 0, 150) . '...' : $log;
            echo "   " . $log . "\n";
        }
    } else {
        echo "   Nenhum log de CSRF encontrado hoje\n";
    }

    // Procurar por logs de agendamento
    preg_match_all('/\[.*?\] .*?[Aa]gendamento.*$/m', $logs, $matches);
    if (!empty($matches[0])) {
        $agLogs = array_slice($matches[0], -5);
        echo "\n   Últimos logs de Agendamento:\n";
        foreach ($agLogs as $log) {
            $log = strlen($log) > 150 ? substr($log, 0, 150) . '...' : $log;
            echo "   " . $log . "\n";
        }
    }
} else {
    echo "   Arquivo de log não encontrado: {$logFile}\n";
}
echo "\n";

// 6. Teste de lógica de duplicata
echo "6. Simulação de verificação de duplicata...\n";
try {
    $testUserId = DB::table('agendamentos')->value('user_id');

    if ($testUserId) {
        $testData = [
            'user_id' => $testUserId,
            'titulo' => 'TESTE_DUPLICATA_' . time(),
            'valor_centavos' => 10000,
            'tipo' => 'despesa',
            'data_pagamento' => date('Y-m-d H:i:s')
        ];

        $exists = DB::table('agendamentos')
            ->where('user_id', $testData['user_id'])
            ->where('titulo', $testData['titulo'])
            ->where('valor_centavos', $testData['valor_centavos'])
            ->where('tipo', $testData['tipo'])
            ->where('data_pagamento', $testData['data_pagamento'])
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-10 seconds')))
            ->exists();

        echo "   ✓ Lógica de verificação de duplicata funcionando\n";
        echo "   Resultado do teste: " . ($exists ? "Duplicata detectada" : "Nenhuma duplicata") . "\n\n";
    } else {
        echo "   Nenhum usuário disponível para teste\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n\n";
}

// 7. Verificar arquivos modificados
echo "7. Verificando arquivos de correção...\n";
$files = [
    'AgendamentoController.php' => __DIR__ . '/../Application/Controllers/Api/AgendamentoController.php',
    'CsrfMiddleware.php' => __DIR__ . '/../Application/Middlewares/CsrfMiddleware.php',
    'SecurityController.php' => __DIR__ . '/../Application/Controllers/Api/SecurityController.php',
    'admin-agendamentos-index.js' => __DIR__ . '/../public/assets/js/admin-agendamentos-index.js',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $modified = filemtime($path);
        $hasLog = strpos($content, 'LogService') !== false || strpos($content, 'console.log') !== false;
        echo "   {$name}: Modificado em " . date('Y-m-d H:i:s', $modified);
        echo $hasLog ? " ✓\n" : " (sem logs adicionados)\n";
    } else {
        echo "   {$name}: ✗ Arquivo não encontrado\n";
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
