<?php

/**
 * FIX: Corrigir saldos de TODAS as contas de TODOS os usuários
 * 
 * PROBLEMAS IDENTIFICADOS:
 * 
 * 1. TRANSFERENCIAS COM pago=0 (CAUSA RAIZ dos saldos errados)
 *    - TransferenciaService SEMPRE cria com pago=1, afeta_caixa=1
 *    - Porém TODAS as transferências no DB têm pago=0
 *    - Resultado: transferências não são contadas no cálculo de saldo
 *    - Impacto: contas que deveriam estar zeradas mostram saldo negativo/positivo
 * 
 * 2. ORIGEM_TIPO INCORRETA (user 1 específico)
 *    - 451 lançamentos normais com origem_tipo='cartao_credito' e cartao_credito_id=NULL
 *    - Não afeta saldo diretamente mas causa problemas em scripts de fix
 * 
 * 3. BUG CartaoFaturaService::calcularSaldoConta()
 *    - Falta filtro pago=1 e eh_transferencia=0 nas queries de receita/despesa
 *    - Será corrigido diretamente no código
 * 
 * USO:
 *   php cli/fix_saldos.php --dry-run    # Ver o que seria alterado
 *   php cli/fix_saldos.php              # Aplicar correções
 *   php cli/fix_saldos.php --user=1     # Corrigir só user 1
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Parse args
$dryRun = in_array('--dry-run', $argv ?? []);
$userFilter = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--user=')) {
        $userFilter = (int) substr($arg, 7);
    }
}

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  CORREÇÃO DE SALDOS - TODAS AS CONTAS           ║\n";
echo "╚══════════════════════════════════════════════════╝\n";
echo $dryRun ? "  ⚠️  MODO DRY-RUN - nenhuma alteração será feita\n" : "";
echo $userFilter ? "  Filtro: user_id = {$userFilter}\n" : "  Aplicando para TODOS os usuários\n";
echo "  " . date('Y-m-d H:i:s') . "\n\n";

// ═══════════════════════════════════════════════════════
// ANTES: Mostrar saldos atuais
// ═══════════════════════════════════════════════════════
function calcularSaldos(?int $userId = null): array
{
    $query = DB::table('contas')->where('ativo', 1);
    if ($userId) $query->where('user_id', $userId);
    $contas = $query->get();

    $resultado = [];
    foreach ($contas as $c) {
        $rec = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $c->user_id)
            ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'receita')->sum('valor');
        $des = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $c->user_id)
            ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'despesa')->sum('valor');
        $tIn = (float) DB::table('lancamentos')->where('conta_id_destino', $c->id)->where('user_id', $c->user_id)
            ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
        $tOut = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $c->user_id)
            ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
        $saldo = $c->saldo_inicial + $rec - $des + $tIn - $tOut;

        $resultado[] = [
            'user_id' => $c->user_id,
            'conta_id' => $c->id,
            'nome' => $c->nome,
            'saldo_inicial' => (float) $c->saldo_inicial,
            'receitas' => $rec,
            'despesas' => $des,
            'transf_in' => $tIn,
            'transf_out' => $tOut,
            'saldo' => $saldo,
        ];
    }
    return $resultado;
}

echo ">>> SALDOS ANTES DA CORREÇÃO:\n";
$saldosAntes = calcularSaldos($userFilter);
foreach ($saldosAntes as $s) {
    $flag = $s['saldo'] < -0.01 ? ' ⚠️ NEGATIVO' : '';
    echo sprintf(
        "  U:%-3d #%-3d %-20s R$ %10.2f%s\n",
        $s['user_id'],
        $s['conta_id'],
        $s['nome'],
        $s['saldo'],
        $flag
    );
}

// ═══════════════════════════════════════════════════════
// FIX 1: Transferências com pago=0 → pago=1, afeta_caixa=1
// ═══════════════════════════════════════════════════════
echo "\n─── FIX 1: TRANSFERÊNCIAS COM pago=0 ───\n";
echo "  TransferenciaService SEMPRE cria com pago=1, então TODAS devem ter pago=1.\n\n";

$query1 = DB::table('lancamentos')
    ->where('eh_transferencia', 1)
    ->where('pago', 0);
if ($userFilter) $query1->where('user_id', $userFilter);

$transfersPendentes = (clone $query1)->count();
$transfersValor = (float) (clone $query1)->sum('valor');

echo "  Transferências com pago=0: {$transfersPendentes}\n";
echo "  Valor total: R$ " . number_format($transfersValor, 2, ',', '.') . "\n";

if ($transfersPendentes > 0) {
    // Detalhar por user
    $porUser = (clone $query1)->selectRaw('user_id, COUNT(*) as cnt')->groupBy('user_id')->get();
    foreach ($porUser as $pu) {
        echo "    User {$pu->user_id}: {$pu->cnt} transferências\n";
    }

    if (!$dryRun) {
        $updated1 = DB::table('lancamentos')
            ->where('eh_transferencia', 1)
            ->where('pago', 0);
        if ($userFilter) $updated1->where('user_id', $userFilter);
        $count1 = $updated1->update([
            'pago' => 1,
            'afeta_caixa' => 1,
            'data_pagamento' => DB::raw('data'), // data_pagamento = data do lançamento
        ]);
        echo "  ✅ Corrigidas: {$count1} transferências → pago=1, afeta_caixa=1\n";
    }
}

// ═══════════════════════════════════════════════════════
// FIX 2: origem_tipo incorreta (cartao_credito para lançamentos normais)
// ═══════════════════════════════════════════════════════
echo "\n─── FIX 2: ORIGEM_TIPO INCORRETA ───\n";
echo "  Lançamentos com origem_tipo='cartao_credito' mas cartao_credito_id=NULL\n";
echo "  são lançamentos normais mistagged.\n\n";

$query2 = DB::table('lancamentos')
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id');
if ($userFilter) $query2->where('user_id', $userFilter);

$misTagged = (clone $query2)->count();

echo "  Lançamentos mistagged: {$misTagged}\n";

if ($misTagged > 0) {
    $porUser2 = (clone $query2)->selectRaw('user_id, COUNT(*) as cnt')->groupBy('user_id')->get();
    foreach ($porUser2 as $pu) {
        echo "    User {$pu->user_id}: {$pu->cnt} lançamentos\n";
    }

    if (!$dryRun) {
        $q2 = DB::table('lancamentos')
            ->where('origem_tipo', 'cartao_credito')
            ->whereNull('cartao_credito_id');
        if ($userFilter) $q2->where('user_id', $userFilter);
        $count2 = $q2->update(['origem_tipo' => null]);
        echo "  ✅ Corrigidos: {$count2} lançamentos → origem_tipo=NULL\n";
    }
}

// ═══════════════════════════════════════════════════════
// FIX 3: Sincronizar afeta_caixa com pago (pós-correção)
// ═══════════════════════════════════════════════════════
echo "\n─── FIX 3: SINCRONIZAR afeta_caixa COM pago ───\n";

// 3a. pago=1 mas afeta_caixa=0 (deveria ser 1, exceto se user marcou manualmente)
$query3a = DB::table('lancamentos')
    ->where('pago', 1)
    ->where('afeta_caixa', 0)
    ->where('eh_transferencia', 0);
if ($userFilter) $query3a->where('user_id', $userFilter);
$desync3a = (clone $query3a)->count();

echo "  pago=1 com afeta_caixa=0 (exceto transfer): {$desync3a}\n";

// 3b. pago=0 mas afeta_caixa=1 (deveria ser 0)
$query3b = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('afeta_caixa', 1)
    ->where('eh_transferencia', 0);
if ($userFilter) $query3b->where('user_id', $userFilter);
$desync3b = (clone $query3b)->count();

echo "  pago=0 com afeta_caixa=1 (exceto transfer): {$desync3b}\n";

if ($desync3b > 0 && !$dryRun) {
    $q3b = DB::table('lancamentos')
        ->where('pago', 0)
        ->where('afeta_caixa', 1)
        ->where('eh_transferencia', 0);
    if ($userFilter) $q3b->where('user_id', $userFilter);
    $count3b = $q3b->update(['afeta_caixa' => 0]);
    echo "  ✅ Corrigidos: {$count3b} lançamentos pendentes → afeta_caixa=0\n";
}

// Nota: NÃO corrigimos 3a automaticamente porque o user pode ter marcado como "não afeta caixa"
if ($desync3a > 0) {
    echo "  ℹ️  Os {$desync3a} lançamentos pagos com afeta_caixa=0 podem ter sido definidos manualmente.\n";
    echo "     Não alterando automaticamente.\n";
}

// ═══════════════════════════════════════════════════════
// DEPOIS: Mostrar saldos após correção
// ═══════════════════════════════════════════════════════
if (!$dryRun) {
    echo "\n>>> SALDOS DEPOIS DA CORREÇÃO:\n";
    $saldosDepois = calcularSaldos($userFilter);

    foreach ($saldosDepois as $idx => $s) {
        $antes = $saldosAntes[$idx]['saldo'] ?? 0;
        $diff = $s['saldo'] - $antes;
        $diffStr = $diff != 0 ? sprintf(' (era R$ %.2f, var %+.2f)', $antes, $diff) : '';
        $flag = $s['saldo'] < -0.01 ? ' ⚠️ NEGATIVO' : '';

        echo sprintf(
            "  U:%-3d #%-3d %-20s R$ %10.2f%s%s\n",
            $s['user_id'],
            $s['conta_id'],
            $s['nome'],
            $s['saldo'],
            $diffStr,
            $flag
        );
    }

    // Detalhes de transferências para contas que mudaram muito
    echo "\n>>> RESUMO DE TRANSFERÊNCIAS ATIVADAS:\n";
    foreach ($saldosDepois as $idx => $s) {
        $antes = $saldosAntes[$idx]['saldo'] ?? 0;
        $diff = $s['saldo'] - $antes;
        if (abs($diff) > 0.01) {
            echo sprintf(
                "  U:%-3d #%-3d %-20s TI=%.2f TO=%.2f\n",
                $s['user_id'],
                $s['conta_id'],
                $s['nome'],
                $s['transf_in'],
                $s['transf_out']
            );
        }
    }
} else {
    echo "\n>>> Para ver os saldos corrigidos, execute sem --dry-run\n";
}

// ═══════════════════════════════════════════════════════
// CHECAGEM: Contas ainda com saldo negativo
// ═══════════════════════════════════════════════════════
echo "\n─── VERIFICAÇÃO FINAL ───\n";
$finalSaldos = !$dryRun ? $saldosDepois : $saldosAntes;
$negativos = array_filter($finalSaldos, fn($s) => $s['saldo'] < -0.01);
if (empty($negativos)) {
    echo "  ✅ Nenhuma conta com saldo negativo!\n";
} else {
    echo "  ⚠️ " . count($negativos) . " contas ainda com saldo negativo:\n";
    foreach ($negativos as $n) {
        echo sprintf("    U:%d #%d %s: R$ %.2f\n", $n['user_id'], $n['conta_id'], $n['nome'], $n['saldo']);
    }
}

echo "\n╔══════════════════════════════════════════════════╗\n";
echo $dryRun
    ? "║  Execute sem --dry-run para aplicar correções    ║\n"
    : "║  ✅ CORREÇÃO CONCLUÍDA!                           ║\n";
echo "╚══════════════════════════════════════════════════╝\n";
