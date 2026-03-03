<?php

/**
 * Diagnóstico detalhado para entender a causa raiz dos saldos incorretos
 */
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = (int)($argv[1] ?? 1);
echo "=== DIAGNOSTICO DETALHADO - User {$userId} ===" . PHP_EOL . PHP_EOL;

// 1. Lancamentos por origem_tipo
echo "--- LANCAMENTOS POR ORIGEM_TIPO ---" . PHP_EOL;
$stats = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->selectRaw("COALESCE(origem_tipo, 'NULL') as ot, tipo, COUNT(*) as total, 
        SUM(CASE WHEN pago=1 AND afeta_caixa=1 THEN 1 ELSE 0 END) as pago_afeta,
        SUM(CASE WHEN pago=1 AND afeta_caixa=1 THEN valor ELSE 0 END) as soma_pago")
    ->groupBy('origem_tipo', 'tipo')
    ->orderBy('origem_tipo')
    ->orderBy('tipo')
    ->get();
foreach ($stats as $s) {
    echo sprintf(
        "  origem=%-20s tipo=%-15s total=%d pago+afeta=%d R$%.2f",
        $s->ot,
        $s->tipo,
        $s->total,
        $s->pago_afeta,
        $s->soma_pago
    ) . PHP_EOL;
}

// 2. Contas e saldos
echo PHP_EOL . "--- CONTAS E SALDOS CALCULADOS ---" . PHP_EOL;
$contas = DB::table('contas')->where('user_id', $userId)->where('ativo', 1)->get();
foreach ($contas as $c) {
    $rec = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'receita')->sum('valor');
    $des = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'despesa')->sum('valor');
    $tIn = (float) DB::table('lancamentos')->where('conta_id_destino', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
    $tOut = (float) DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
    $saldo = $c->saldo_inicial + $rec - $des + $tIn - $tOut;
    echo sprintf(
        "  #%-3d %-20s SI=%.2f +R=%.2f -D=%.2f +TI=%.2f -TO=%.2f = R$%.2f",
        $c->id,
        $c->nome,
        $c->saldo_inicial,
        $rec,
        $des,
        $tIn,
        $tOut,
        $saldo
    ) . PHP_EOL;
}

// 3. Para conta com saldo negativo, detalhar por origem_tipo
echo PHP_EOL . "--- SALDO POR CONTA/ORIGEM_TIPO ---" . PHP_EOL;
foreach ($contas as $c) {
    $total = (float)DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->count();
    if ($total == 0) continue;

    echo "  Conta #{$c->id} {$c->nome}:" . PHP_EOL;
    $byOrigem = DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->selectRaw("COALESCE(origem_tipo, 'NULL') as ot, tipo, COUNT(*) as cnt, SUM(valor) as soma")
        ->groupBy('origem_tipo', 'tipo')
        ->get();
    foreach ($byOrigem as $bo) {
        echo sprintf("    origem=%-20s tipo=%-10s count=%-4d R$%.2f", $bo->ot, $bo->tipo, $bo->cnt, $bo->soma) . PHP_EOL;
    }
}

// 4. Check if ALL non-transfer lancamentos for user 1 have origem_tipo='cartao_credito'
echo PHP_EOL . "--- VERIFICAR SE TODOS LANCs SAO CARTAO_CREDITO ---" . PHP_EOL;
$allLancs = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('eh_transferencia', 0)
    ->selectRaw("COALESCE(origem_tipo, 'NULL') as ot, COUNT(*) as cnt")
    ->groupBy('origem_tipo')
    ->get();
echo "  Lancamentos nao-transferencia por origem:" . PHP_EOL;
foreach ($allLancs as $al) {
    echo "    {$al->ot}: {$al->cnt}" . PHP_EOL;
}

// 5. Check cartao_credito_id distribution for cartao_credito origem
echo PHP_EOL . "--- LANCAMENTOS COM origem_tipo=cartao_credito ---" . PHP_EOL;
$ccLancs = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('origem_tipo', 'cartao_credito')
    ->selectRaw("
        CASE WHEN cartao_credito_id IS NULL THEN 'null' ELSE CAST(cartao_credito_id AS CHAR) END as cc_id,
        COUNT(*) as cnt,
        SUM(valor) as soma
    ")
    ->groupBy('cartao_credito_id')
    ->get();
echo "  Por cartao_credito_id:" . PHP_EOL;
foreach ($ccLancs as $cl) {
    echo "    cartao_credito_id={$cl->cc_id}: count={$cl->cnt}, sum=R$" . number_format($cl->soma, 2) . PHP_EOL;
}

// 6. Check when these cartao_credito + null cart lancamentos were CREATED
echo PHP_EOL . "--- DATAS CREATED_AT dos lancamentos com origem_tipo=cartao_credito e cartao_credito_id NULL ---" . PHP_EOL;
$dateRange = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id')
    ->selectRaw("MIN(created_at) as min_created, MAX(created_at) as max_created, COUNT(*) as cnt")
    ->first();
echo "  Count: {$dateRange->cnt}" . PHP_EOL;
echo "  First created: {$dateRange->min_created}" . PHP_EOL;
echo "  Last created:  {$dateRange->max_created}" . PHP_EOL;

// 7. Sample of first 5 and last 5
echo PHP_EOL . "--- AMOSTRA (primeiros 5 e ultimos 5) ---" . PHP_EOL;
$first5 = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id')
    ->orderBy('created_at')
    ->limit(5)
    ->get(['id', 'conta_id', 'tipo', 'valor', 'data', 'descricao', 'created_at', 'pago', 'afeta_caixa']);
echo "  Primeiros 5:" . PHP_EOL;
foreach ($first5 as $l) {
    echo sprintf(
        "    #%d conta=%d tipo=%s R$%.2f data=%s created=%s pago=%d afeta=%d '%s'",
        $l->id,
        $l->conta_id,
        $l->tipo,
        $l->valor,
        $l->data,
        $l->created_at,
        $l->pago,
        $l->afeta_caixa,
        mb_substr($l->descricao, 0, 40)
    ) . PHP_EOL;
}

$last5 = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'conta_id', 'tipo', 'valor', 'data', 'descricao', 'created_at', 'pago', 'afeta_caixa']);
echo "  Ultimos 5:" . PHP_EOL;
foreach ($last5 as $l) {
    echo sprintf(
        "    #%d conta=%d tipo=%s R$%.2f data=%s created=%s pago=%d afeta=%d '%s'",
        $l->id,
        $l->conta_id,
        $l->tipo,
        $l->valor,
        $l->data,
        $l->created_at,
        $l->pago,
        $l->afeta_caixa,
        mb_substr($l->descricao, 0, 40)
    ) . PHP_EOL;
}

// 8. Check if there's a migration/script that changed origem_tipo in bulk
echo PHP_EOL . "--- VERIFICAR SE LANCAMENTOS 'NORMAL' EXISTEM para user 1 ---" . PHP_EOL;
$normalLancs = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('origem_tipo', 'normal')
    ->where('eh_transferencia', 0)
    ->count();
echo "  Lancamentos com origem_tipo='normal' (nao-transfer): {$normalLancs}" . PHP_EOL;

// 9. COMPARAR: excluir cartao_credito com cartao_credito_id=null e recalcular
echo PHP_EOL . "--- SALDO SEM LANCAMENTOS cartao_credito/null ---" . PHP_EOL;
foreach ($contas as $c) {
    $rec = (float)DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'receita')
        ->where(function ($q) {
            $q->where('origem_tipo', '!=', 'cartao_credito')->orWhereNotNull('cartao_credito_id');
        })
        ->sum('valor');
    $des = (float)DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)->where('tipo', 'despesa')
        ->where(function ($q) {
            $q->where('origem_tipo', '!=', 'cartao_credito')->orWhereNotNull('cartao_credito_id');
        })
        ->sum('valor');
    $tIn = (float)DB::table('lancamentos')->where('conta_id_destino', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
    $tOut = (float)DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
    $saldo = $c->saldo_inicial + $rec - $des + $tIn - $tOut;

    $totalExcluded = DB::table('lancamentos')->where('conta_id', $c->id)->where('user_id', $userId)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->where('origem_tipo', 'cartao_credito')->whereNull('cartao_credito_id')->count();

    if ($totalExcluded > 0) {
        echo sprintf(
            "  #%-3d %-20s R$%.2f (excluidos %d lancs com origem=cc + cart=null)",
            $c->id,
            $c->nome,
            $saldo,
            $totalExcluded
        ) . PHP_EOL;
    }
}

// 10. Cartoes e suas contas vinculadas
echo PHP_EOL . "--- CARTOES DE CREDITO ---" . PHP_EOL;
$cartoes = DB::table('cartoes_credito')->where('user_id', $userId)->get();
foreach ($cartoes as $cc) {
    echo sprintf("  Cartao #%d '%s' -> debita conta #%d", $cc->id, $cc->nome_cartao ?? $cc->bandeira ?? '?', $cc->conta_id ?? 0) . PHP_EOL;
}

echo PHP_EOL . "=== FIM ===" . PHP_EOL;
