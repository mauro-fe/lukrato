<?php
/**
 * Script para verificar consistência do banco de dados
 */

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICACAO DE CONSISTENCIA DO BANCO ===\n\n";

// 1. Verificar campos de competencia
echo "1. CAMPOS DE COMPETENCIA\n";
echo str_repeat('-', 50) . "\n";

$total = DB::table('lancamentos')->count();
if ($total == 0) {
    echo "Nenhum lancamento encontrado.\n\n";
} else {
    $comAfetaCaixa = DB::table('lancamentos')->whereNotNull('afeta_caixa')->count();
    $comAfetaCompetencia = DB::table('lancamentos')->whereNotNull('afeta_competencia')->count();
    $comDataCompetencia = DB::table('lancamentos')->whereNotNull('data_competencia')->count();

    echo "Total de lancamentos: {$total}\n";
    echo "Com afeta_caixa preenchido: {$comAfetaCaixa} (" . round($comAfetaCaixa/$total*100, 1) . "%)\n";
    echo "Com afeta_competencia preenchido: {$comAfetaCompetencia} (" . round($comAfetaCompetencia/$total*100, 1) . "%)\n";
    echo "Com data_competencia preenchido: {$comDataCompetencia} (" . round($comDataCompetencia/$total*100, 1) . "%)\n\n";
}

// 2. Verificar saldo inicial das contas
echo "2. SALDO INICIAL DAS CONTAS\n";
echo str_repeat('-', 50) . "\n";

$contasSemSaldoInicial = DB::table('contas')->whereNull('saldo_inicial')->count();
$contasComSaldoInicial = DB::table('contas')->whereNotNull('saldo_inicial')->count();
$contasComSaldoZero = DB::table('contas')->where('saldo_inicial', 0)->count();
$totalContas = DB::table('contas')->count();

echo "Total de contas: {$totalContas}\n";
echo "Com saldo_inicial preenchido: {$contasComSaldoInicial}\n";
echo "Com saldo_inicial = 0: {$contasComSaldoZero}\n";
echo "Sem saldo_inicial (NULL): {$contasSemSaldoInicial}\n\n";

// 3. Verificar lancamentos orfaos
echo "3. LANCAMENTOS ORFAOS\n";
echo str_repeat('-', 50) . "\n";

$semConta = DB::table('lancamentos')
    ->whereNull('conta_id')
    ->where('eh_transferencia', 0)
    ->count();

$contaInexistente = DB::table('lancamentos as l')
    ->leftJoin('contas as c', 'l.conta_id', '=', 'c.id')
    ->whereNotNull('l.conta_id')
    ->whereNull('c.id')
    ->count();

$semCategoria = DB::table('lancamentos')
    ->whereNull('categoria_id')
    ->where('eh_transferencia', 0)
    ->count();

echo "Lancamentos sem conta (exceto transf): {$semConta}\n";
echo "Lancamentos com conta_id inexistente: {$contaInexistente}\n";
echo "Lancamentos sem categoria (exceto transf): {$semCategoria}\n\n";

// 4. Verificar usuarios com dados
echo "4. USUARIOS COM DADOS\n";
echo str_repeat('-', 50) . "\n";

$usersComLancamentos = DB::table('lancamentos')
    ->select('user_id')
    ->distinct()
    ->count();

$usersComContas = DB::table('contas')
    ->select('user_id')
    ->distinct()
    ->count();

echo "Usuarios com lancamentos: {$usersComLancamentos}\n";
echo "Usuarios com contas: {$usersComContas}\n\n";

// 5. Comparar saldos (primeiros 5 usuarios)
echo "5. COMPARACAO DE SALDOS (primeiros 5 usuarios com mais lancamentos)\n";
echo str_repeat('-', 50) . "\n";

$users = DB::table('lancamentos')
    ->select('user_id', DB::raw('COUNT(*) as total'))
    ->groupBy('user_id')
    ->orderByDesc('total')
    ->limit(5)
    ->get();

foreach ($users as $user) {
    $userId = $user->user_id;
    
    // Saldo via lancamentos (sem considerar afeta_caixa - metodo antigo)
    $receitasAntigo = (float) DB::table('lancamentos')
        ->where('user_id', $userId)
        ->where('tipo', 'receita')
        ->where('eh_transferencia', 0)
        ->sum('valor');
    
    $despesasAntigo = (float) DB::table('lancamentos')
        ->where('user_id', $userId)
        ->where('tipo', 'despesa')
        ->where('eh_transferencia', 0)
        ->sum('valor');
    
    $saldoAntigo = $receitasAntigo - $despesasAntigo;
    
    // Saldo via lancamentos (considerando afeta_caixa - metodo novo)
    $receitasNovo = (float) DB::table('lancamentos')
        ->where('user_id', $userId)
        ->where('tipo', 'receita')
        ->where('eh_transferencia', 0)
        ->where(function($q) {
            $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
        })
        ->sum('valor');
    
    $despesasNovo = (float) DB::table('lancamentos')
        ->where('user_id', $userId)
        ->where('tipo', 'despesa')
        ->where('eh_transferencia', 0)
        ->where(function($q) {
            $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
        })
        ->sum('valor');
    
    $saldoNovo = $receitasNovo - $despesasNovo;
    
    // Saldo inicial das contas
    $saldoInicialContas = (float) DB::table('contas')
        ->where('user_id', $userId)
        ->where('ativo', true)
        ->sum('saldo_inicial');
    
    $saldoTotalNovo = $saldoInicialContas + $saldoNovo;
    
    // Diferenca
    $diferenca = $saldoNovo - $saldoAntigo;
    $diferencaStr = $diferenca != 0 ? " (diff: " . number_format($diferenca, 2, ',', '.') . ")" : "";
    
    echo "User {$userId} ({$user->total} lanc):\n";
    echo "  Saldo Inicial Contas: R$ " . number_format($saldoInicialContas, 2, ',', '.') . "\n";
    echo "  Metodo Antigo (sem afeta_caixa): R$ " . number_format($saldoAntigo, 2, ',', '.') . "\n";
    echo "  Metodo Novo (com afeta_caixa): R$ " . number_format($saldoNovo, 2, ',', '.') . $diferencaStr . "\n";
    echo "  SALDO TOTAL: R$ " . number_format($saldoTotalNovo, 2, ',', '.') . "\n\n";
}

// 6. Verificar valores invalidos
echo "6. VERIFICACAO DE VALORES INVALIDOS\n";
echo str_repeat('-', 50) . "\n";

$valoresNegativos = DB::table('lancamentos')->where('valor', '<', 0)->count();
$valoresZero = DB::table('lancamentos')->where('valor', 0)->count();
$valoresNulos = DB::table('lancamentos')->whereNull('valor')->count();

echo "Lancamentos com valor negativo: {$valoresNegativos}\n";
echo "Lancamentos com valor zero: {$valoresZero}\n";
echo "Lancamentos com valor NULL: {$valoresNulos}\n\n";

// 7. Verificar transferencias
echo "7. TRANSFERENCIAS\n";
echo str_repeat('-', 50) . "\n";

$totalTransferencias = DB::table('lancamentos')->where('eh_transferencia', 1)->count();
$transfSemDestino = DB::table('lancamentos')
    ->where('eh_transferencia', 1)
    ->whereNull('conta_id_destino')
    ->count();

echo "Total de transferencias: {$totalTransferencias}\n";
echo "Transferencias sem conta_destino: {$transfSemDestino}\n\n";

// 8. Verificar cartoes de credito
echo "8. CARTOES DE CREDITO\n";
echo str_repeat('-', 50) . "\n";

$totalCartoes = DB::table('cartoes_credito')->count();
$lancCartao = DB::table('lancamentos')->whereNotNull('cartao_credito_id')->count();
$cartaoInexistente = DB::table('lancamentos as l')
    ->leftJoin('cartoes_credito as cc', 'l.cartao_credito_id', '=', 'cc.id')
    ->whereNotNull('l.cartao_credito_id')
    ->whereNull('cc.id')
    ->count();

echo "Total de cartoes: {$totalCartoes}\n";
echo "Lancamentos de cartao: {$lancCartao}\n";
echo "Lancamentos com cartao_id inexistente: {$cartaoInexistente}\n\n";

// Resumo
echo "=== RESUMO ===\n";
$problemas = [];

if ($contaInexistente > 0) $problemas[] = "Lancamentos com conta inexistente: {$contaInexistente}";
if ($transfSemDestino > 0) $problemas[] = "Transferencias sem destino: {$transfSemDestino}";
if ($cartaoInexistente > 0) $problemas[] = "Lancamentos com cartao inexistente: {$cartaoInexistente}";
if ($valoresNegativos > 0) $problemas[] = "Valores negativos: {$valoresNegativos}";
if ($valoresNulos > 0) $problemas[] = "Valores nulos: {$valoresNulos}";

if (empty($problemas)) {
    echo "✅ Nenhuma inconsistencia critica encontrada!\n";
} else {
    echo "⚠️ Problemas encontrados:\n";
    foreach ($problemas as $p) {
        echo "  - {$p}\n";
    }
}

echo "\n=== FIM DA VERIFICACAO ===\n";
