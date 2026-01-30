<?php

/**
 * Script de teste - APIs de Competência
 * Verifica se o backend está funcionando corretamente
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE VERIFICAÇÃO - BACKEND COMPETÊNCIA            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$success = [];

// 1. Verificar se as colunas existem
echo "1️⃣  Verificando colunas na tabela lancamentos...\n";
$requiredColumns = ['data_competencia', 'afeta_competencia', 'afeta_caixa', 'origem_tipo'];
$existingColumns = DB::select("SHOW COLUMNS FROM lancamentos");
$columnNames = array_map(fn($c) => $c->Field, $existingColumns);

foreach ($requiredColumns as $col) {
    if (in_array($col, $columnNames)) {
        $success[] = "   ✅ Coluna '$col' existe";
    } else {
        $errors[] = "   ❌ Coluna '$col' NÃO existe";
    }
}
echo implode("\n", array_merge($success, $errors)) . "\n\n";
$success = [];
$errors = [];

// 2. Verificar dados normalizados
echo "2️⃣  Verificando dados normalizados...\n";
$totalCartao = Lancamento::whereNotNull('cartao_credito_id')->count();
$comCompetencia = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNotNull('data_competencia')
    ->count();
$comOrigemTipo = Lancamento::where('origem_tipo', 'cartao_credito')->count();

echo "   📊 Total lançamentos cartão: $totalCartao\n";
echo "   📊 Com data_competencia: $comCompetencia\n";
echo "   📊 Com origem_tipo='cartao_credito': $comOrigemTipo\n";

if ($comCompetencia === $totalCartao) {
    $success[] = "   ✅ Todos os lançamentos de cartão têm data_competencia";
} else {
    $errors[] = "   ⚠️  " . ($totalCartao - $comCompetencia) . " lançamentos sem data_competencia";
}
echo "\n";

// 3. Verificar Model Lancamento
echo "3️⃣  Verificando Model Lancamento...\n";
try {
    $lancamento = new Lancamento();

    // Verificar constantes
    if (defined(Lancamento::class . '::ORIGEM_CARTAO_CREDITO')) {
        $success[] = "   ✅ Constante ORIGEM_CARTAO_CREDITO existe";
    } else {
        $errors[] = "   ❌ Constante ORIGEM_CARTAO_CREDITO não existe";
    }

    // Verificar se fillable contém os novos campos
    $fillable = $lancamento->getFillable();
    $newFields = ['data_competencia', 'afeta_competencia', 'afeta_caixa', 'origem_tipo'];
    foreach ($newFields as $field) {
        if (in_array($field, $fillable)) {
            $success[] = "   ✅ Campo '$field' está em \$fillable";
        } else {
            $errors[] = "   ❌ Campo '$field' NÃO está em \$fillable";
        }
    }

    // Verificar métodos
    if (method_exists($lancamento, 'isCartaoCredito')) {
        $success[] = "   ✅ Método isCartaoCredito() existe";
    }
    if (method_exists($lancamento, 'temCompetenciaDiferente')) {
        $success[] = "   ✅ Método temCompetenciaDiferente() existe";
    }
    if (method_exists($lancamento, 'scopeCompetenciaEntre')) {
        $success[] = "   ✅ Scope scopeCompetenciaEntre() existe";
    }
} catch (Exception $e) {
    $errors[] = "   ❌ Erro no Model: " . $e->getMessage();
}
echo implode("\n", $success) . "\n";
if (count($errors) > 0) echo implode("\n", $errors) . "\n";
echo "\n";
$success = [];
$errors = [];

// 4. Verificar Repository
echo "4️⃣  Verificando LancamentoRepository...\n";
try {
    $repo = new LancamentoRepository();

    // Verificar métodos
    $methods = [
        'sumReceitasCompetencia',
        'sumDespesasCompetencia',
        'sumReceitasCaixa',
        'sumDespesasCaixa',
        'findByMonthAndViewType',
        'getResumoCompetenciaVsCaixa'
    ];

    foreach ($methods as $method) {
        if (method_exists($repo, $method)) {
            $success[] = "   ✅ Método $method() existe";
        } else {
            $errors[] = "   ❌ Método $method() NÃO existe";
        }
    }

    // Testar um método
    $userId = Lancamento::first()->user_id ?? 1;
    $start = '2025-12-01';
    $end = '2025-12-31';

    $despesasComp = $repo->sumDespesasCompetencia($userId, $start, $end);
    $despesasCaixa = $repo->sumDespesasCaixa($userId, $start, $end);

    echo "   📊 Despesas DEZ/2025 (Competência): R$ " . number_format($despesasComp, 2, ',', '.') . "\n";
    echo "   📊 Despesas DEZ/2025 (Caixa): R$ " . number_format($despesasCaixa, 2, ',', '.') . "\n";

    if ($despesasComp !== $despesasCaixa) {
        $success[] = "   ✅ Valores diferentes entre competência e caixa (esperado)";
    } else {
        $success[] = "   ℹ️  Valores iguais (pode ser normal se não houver diferença no período)";
    }
} catch (Exception $e) {
    $errors[] = "   ❌ Erro no Repository: " . $e->getMessage();
}
echo implode("\n", $success) . "\n";
if (count($errors) > 0) echo implode("\n", $errors) . "\n";
echo "\n";
$success = [];
$errors = [];

// 5. Verificar Controllers
echo "5️⃣  Verificando Controllers...\n";
try {
    // FinanceiroController
    $financeiroClass = 'Application\\Controllers\\Api\\FinanceiroController';
    if (class_exists($financeiroClass)) {
        $success[] = "   ✅ FinanceiroController existe";
        $fc = new $financeiroClass();
        if (method_exists($fc, 'metrics')) {
            $success[] = "   ✅ FinanceiroController::metrics() existe";
        }
    }

    // DashboardController
    $dashboardClass = 'Application\\Controllers\\Api\\DashboardController';
    if (class_exists($dashboardClass)) {
        $success[] = "   ✅ DashboardController existe";
        $dc = new $dashboardClass();
        if (method_exists($dc, 'comparativoCompetenciaCaixa')) {
            $success[] = "   ✅ DashboardController::comparativoCompetenciaCaixa() existe";
        }
    }
} catch (Exception $e) {
    $errors[] = "   ❌ Erro nos Controllers: " . $e->getMessage();
}
echo implode("\n", $success) . "\n";
if (count($errors) > 0) echo implode("\n", $errors) . "\n";
echo "\n";
$success = [];
$errors = [];

// 6. Verificar Services
echo "6️⃣  Verificando Services...\n";
try {
    $services = [
        'Application\\Services\\CartaoFaturaService',
        'Application\\Services\\FaturaService',
        'Application\\Services\\CartaoCreditoLancamentoService'
    ];

    foreach ($services as $serviceClass) {
        if (class_exists($serviceClass)) {
            $success[] = "   ✅ " . basename(str_replace('\\', '/', $serviceClass)) . " existe";
        } else {
            $errors[] = "   ❌ " . basename(str_replace('\\', '/', $serviceClass)) . " NÃO existe";
        }
    }
} catch (Exception $e) {
    $errors[] = "   ❌ Erro nos Services: " . $e->getMessage();
}
echo implode("\n", $success) . "\n";
if (count($errors) > 0) echo implode("\n", $errors) . "\n";
echo "\n";

// 7. Verificar exemplo de lançamento com competência diferente
echo "7️⃣  Verificando lançamentos com competência diferente do caixa...\n";
$diferente = DB::select("
    SELECT l.id, l.descricao, l.data as data_caixa, l.data_competencia, l.valor
    FROM lancamentos l
    WHERE l.data_competencia IS NOT NULL 
    AND DATE_FORMAT(l.data, '%Y-%m') != DATE_FORMAT(l.data_competencia, '%Y-%m')
    LIMIT 5
");

if (count($diferente) > 0) {
    echo "   ✅ Encontrados " . count($diferente) . " lançamentos com competência diferente:\n";
    foreach ($diferente as $l) {
        echo "      - ID {$l->id}: Caixa={$l->data_caixa}, Competência={$l->data_competencia}\n";
    }
} else {
    echo "   ℹ️  Nenhum lançamento com competência diferente do caixa encontrado\n";
}
echo "\n";

// Resumo final
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMO FINAL                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

$totalErrors = count($errors);
if ($totalErrors === 0) {
    echo "✅ BACKEND OK - Todas as verificações passaram!\n";
} else {
    echo "⚠️  ATENÇÃO - Encontrados $totalErrors problemas\n";
    foreach ($errors as $e) echo $e . "\n";
}
