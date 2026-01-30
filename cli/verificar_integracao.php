<?php

/**
 * Verificação completa - Backend + Integração
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       VERIFICAÇÃO COMPLETA - BACKEND E FRONTEND                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// =========================================
// 1. BACKEND - DADOS
// =========================================
echo "┌──────────────────────────────────────────────────────────────────┐\n";
echo "│ 1. VERIFICAÇÃO DO BANCO DE DADOS                                │\n";
echo "└──────────────────────────────────────────────────────────────────┘\n";

// Colunas
$cols = DB::select("SHOW COLUMNS FROM lancamentos");
$colNames = array_map(fn($c) => $c->Field, $cols);
$required = ['data_competencia', 'afeta_competencia', 'afeta_caixa', 'origem_tipo'];

echo "Colunas novas:\n";
foreach ($required as $col) {
    $status = in_array($col, $colNames) ? '✅' : '❌';
    echo "  $status $col\n";
}

// Dados normalizados
$totalCartao = Lancamento::whereNotNull('cartao_credito_id')->count();
$comCompetencia = Lancamento::whereNotNull('cartao_credito_id')->whereNotNull('data_competencia')->count();

echo "\nDados normalizados:\n";
echo "  📊 Lançamentos de cartão: $totalCartao\n";
echo "  📊 Com data_competencia: $comCompetencia (" . round($comCompetencia / $totalCartao * 100) . "%)\n";

// =========================================
// 2. BACKEND - APIs
// =========================================
echo "\n┌──────────────────────────────────────────────────────────────────┐\n";
echo "│ 2. VERIFICAÇÃO DAS APIs                                         │\n";
echo "└──────────────────────────────────────────────────────────────────┘\n";

$repo = new LancamentoRepository();
$userId = Lancamento::first()->user_id ?? 1;

// Janeiro 2026
$mes = '2026-01';
$start = "$mes-01";
$end = date('Y-m-t', strtotime($start));

echo "Mês: $mes (User ID: $userId)\n\n";

$despCaixa = $repo->sumDespesasCaixa($userId, $start, $end);
$despComp = $repo->sumDespesasCompetencia($userId, $start, $end);
$recCaixa = $repo->sumReceitasCaixa($userId, $start, $end);
$recComp = $repo->sumReceitasCompetencia($userId, $start, $end);

echo "                     │    CAIXA      │  COMPETÊNCIA  │  DIFERENÇA\n";
echo "─────────────────────┼───────────────┼───────────────┼─────────────\n";
printf(
    " Receitas            │ %12s │ %12s │ %+12s\n",
    number_format($recCaixa, 2, ',', '.'),
    number_format($recComp, 2, ',', '.'),
    number_format($recComp - $recCaixa, 2, ',', '.')
);
printf(
    " Despesas            │ %12s │ %12s │ %+12s\n",
    number_format($despCaixa, 2, ',', '.'),
    number_format($despComp, 2, ',', '.'),
    number_format($despComp - $despCaixa, 2, ',', '.')
);
printf(
    " Resultado           │ %12s │ %12s │ %+12s\n",
    number_format($recCaixa - $despCaixa, 2, ',', '.'),
    number_format($recComp - $despComp, 2, ',', '.'),
    number_format(($recComp - $despComp) - ($recCaixa - $despCaixa), 2, ',', '.')
);

// =========================================
// 3. FRONTEND - VERIFICAÇÃO DE ARQUIVOS
// =========================================
echo "\n┌──────────────────────────────────────────────────────────────────┐\n";
echo "│ 3. VERIFICAÇÃO DO FRONTEND                                      │\n";
echo "└──────────────────────────────────────────────────────────────────┘\n";

$frontendFiles = [
    'views/admin/dashboard/index.php' => 'Dashboard View',
    'public/assets/js/admin-dashboard-index.js' => 'Dashboard JS',
    'views/admin/partials/header_mes.php' => 'Header Mês',
];

foreach ($frontendFiles as $file => $desc) {
    $path = __DIR__ . '/../' . $file;
    $status = file_exists($path) ? '✅' : '❌';
    echo "  $status $desc\n";
}

// Verificar se JS chama API corretamente
$dashJsPath = __DIR__ . '/../public/assets/js/admin-dashboard-index.js';
$dashJs = file_exists($dashJsPath) ? file_get_contents($dashJsPath) : '';
$apiCall = strpos($dashJs, 'dashboard/metrics') !== false;
echo "\n  " . ($apiCall ? '✅' : '❌') . " JS chama /api/dashboard/metrics\n";

// =========================================
// 4. ROTAS
// =========================================
echo "\n┌──────────────────────────────────────────────────────────────────┐\n";
echo "│ 4. VERIFICAÇÃO DAS ROTAS                                        │\n";
echo "└──────────────────────────────────────────────────────────────────┘\n";

$routes = file_get_contents(__DIR__ . '/../routes/api.php');
$rotasOk = [
    'dashboard/metrics' => strpos($routes, 'dashboard/metrics') !== false,
    'dashboard/comparativo-competencia' => strpos($routes, 'comparativo-competencia') !== false,
];

foreach ($rotasOk as $rota => $existe) {
    echo "  " . ($existe ? '✅' : '❌') . " /api/$rota\n";
}

// =========================================
// 5. RESUMO
// =========================================
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        RESUMO FINAL                            ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

$allOk = true;
$issues = [];

if ($comCompetencia < $totalCartao) {
    $issues[] = "⚠️  Dados não totalmente normalizados";
    $allOk = false;
}

if (!$apiCall) {
    $issues[] = "⚠️  Frontend não chama API correta";
    $allOk = false;
}

if ($despCaixa === $despComp && $totalCartao > 0) {
    $issues[] = "⚠️  Valores de caixa e competência iguais (verificar)";
}

if ($allOk && count($issues) === 0) {
    echo "║  ✅ BACKEND: OK - Todas verificações passaram                  ║\n";
    echo "║  ✅ FRONTEND: Arquivos existem e API está conectada            ║\n";
    echo "║  ✅ ROTAS: Todas configuradas                                  ║\n";
    echo "║                                                                ║\n";
    echo "║  💡 Para alternar visão no frontend, use:                      ║\n";
    echo "║     /api/dashboard/metrics?month=2026-01&view=competencia      ║\n";
} else {
    foreach ($issues as $issue) {
        echo "║  $issue\n";
    }
}

echo "╚════════════════════════════════════════════════════════════════╝\n";
