<?php

/**
 * Diagnóstico global de saldos - TODOS os usuários
 * 
 * Investiga double-counting causado pela refatoração do sistema de cartões:
 * - Fluxo antigo: CartaoCreditoLancamentoService criava 1 Lancamento por compra
 * - Fluxo novo: Cria apenas FaturaCartaoItem, lançamento só ao pagar fatura
 * - Se ambos coexistem, a despesa é contada DUAS vezes
 * 
 * Usage: php cli/diagnostico_saldos_global.php [--user=ID]
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Parse args
$userFilter = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--user=')) {
        $userFilter = (int) substr($arg, 7);
    }
}

echo "=== DIAGNOSTICO GLOBAL DE SALDOS ===\n";
if ($userFilter) echo "Filtro: user_id = {$userFilter}\n";
echo date('Y-m-d H:i:s') . "\n\n";

// ──────────────────────────────────────────────────────────────────
// 1. DOUBLE-COUNTING: Lançamentos individuais de cartão + pagamento_fatura
// ──────────────────────────────────────────────────────────────────
echo "--- 1. DOUBLE-COUNTING: Lanc. individuais de cartão + pagamento_fatura ---\n\n";

$cartoes = DB::table('cartoes_credito');
if ($userFilter) $cartoes->where('user_id', $userFilter);
$cartoes = $cartoes->get();

$totalDoubleCount = 0;

foreach ($cartoes as $cc) {
    // A) Lançamentos com origem_tipo='pagamento_fatura' (fluxo novo - 1 por fatura paga)
    $lancsPagFatura = DB::table('lancamentos')
        ->where('user_id', $cc->user_id)
        ->where('cartao_credito_id', $cc->id)
        ->where('origem_tipo', 'pagamento_fatura')
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->get(['id', 'conta_id', 'valor', 'data', 'descricao']);

    $totalPagFatura = $lancsPagFatura->sum('valor');

    // B) Lançamentos com origem_tipo='cartao_credito' pagos (fluxo antigo OU fallback)
    $lancsCartaoIndividual = DB::table('lancamentos')
        ->where('user_id', $cc->user_id)
        ->where('cartao_credito_id', $cc->id)
        ->where('origem_tipo', 'cartao_credito')
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->get(['id', 'conta_id', 'valor', 'data', 'descricao']);

    $totalCartaoInd = $lancsCartaoIndividual->sum('valor');

    // C) Lançamentos do cartão SEM origem_tipo definida (podem ser do fluxo antigo)
    $lancsCartaoNull = DB::table('lancamentos')
        ->where('user_id', $cc->user_id)
        ->where('cartao_credito_id', $cc->id)
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->where('tipo', 'despesa')
        ->where(function ($q) {
            $q->whereNull('origem_tipo')
                ->orWhere('origem_tipo', 'normal');
        })
        ->get(['id', 'conta_id', 'valor', 'data', 'descricao']);

    $totalCartaoNull = $lancsCartaoNull->sum('valor');

    // D) Total de itens pagos nas faturas
    $totalItensPagos = (float) DB::table('faturas_cartao_itens')
        ->where('user_id', $cc->user_id)
        ->where('cartao_credito_id', $cc->id)
        ->where('pago', 1)
        ->sum('valor');

    $totalItensTotal = (float) DB::table('faturas_cartao_itens')
        ->where('user_id', $cc->user_id)
        ->where('cartao_credito_id', $cc->id)
        ->sum('valor');

    $totalDebitado = $totalPagFatura + $totalCartaoInd + $totalCartaoNull;

    // Se tem tanto pagamento_fatura quanto cartao_credito individual, é double-counting
    $hasDouble = ($totalPagFatura > 0 && ($totalCartaoInd > 0 || $totalCartaoNull > 0));
    $diff = $totalDebitado - $totalItensPagos;

    if ($totalDebitado > 0 || $totalItensPagos > 0) {
        $flag = $hasDouble ? " *** DOUBLE-COUNT! ***" : "";
        $diffFlag = abs($diff) > 0.01 ? " *** DIFERENCA ***" : "";

        echo sprintf(
            "  Cartao #%d '%s' (user %d, debita conta #%d):%s\n",
            $cc->id,
            $cc->nome_cartao ?? $cc->bandeira ?? '?',
            $cc->user_id,
            $cc->conta_id ?? 0,
            $flag
        );
        echo "    [A] Lanc pagamento_fatura (novo):   R$ " . number_format($totalPagFatura, 2, ',', '.') . " (" . $lancsPagFatura->count() . " lanc)\n";
        echo "    [B] Lanc cartao_credito (antigo):   R$ " . number_format($totalCartaoInd, 2, ',', '.') . " (" . $lancsCartaoIndividual->count() . " lanc)\n";
        echo "    [C] Lanc sem origem (antigo):       R$ " . number_format($totalCartaoNull, 2, ',', '.') . " (" . $lancsCartaoNull->count() . " lanc)\n";
        echo "    Total debitado da conta:            R$ " . number_format($totalDebitado, 2, ',', '.') . "\n";
        echo "    Itens pagos (faturas):              R$ " . number_format($totalItensPagos, 2, ',', '.') . "\n";
        echo "    Itens total (pago+pendente):        R$ " . number_format($totalItensTotal, 2, ',', '.') . "\n";
        echo "    Diferenca (debitado - itens pagos): R$ " . number_format($diff, 2, ',', '.') . "{$diffFlag}\n";

        if ($hasDouble) {
            $totalDoubleCount += ($totalCartaoInd + $totalCartaoNull);
            echo "    >> Excesso provavel: R$ " . number_format($totalCartaoInd + $totalCartaoNull, 2, ',', '.') . "\n";
        }
        echo "\n";
    }
}

if ($totalDoubleCount > 0) {
    echo "  TOTAL DOUBLE-COUNT DETECTADO: R$ " . number_format($totalDoubleCount, 2, ',', '.') . "\n\n";
} else {
    echo "  Nenhum double-counting detectado nesta seção.\n\n";
}

// ──────────────────────────────────────────────────────────────────
// 2. LANCAMENTOS DUPLICADOS (exato mesmo registro)
// ──────────────────────────────────────────────────────────────────
echo "--- 2. LANCAMENTOS DUPLICADOS (mesmo desc, valor, data, tipo, conta) ---\n";
$dupeQuery = DB::table('lancamentos')
    ->select(DB::raw("user_id, conta_id, descricao, valor, data, tipo, origem_tipo, COUNT(*) as qtd"))
    ->where('pago', 1)->where('afeta_caixa', 1)
    ->groupBy('user_id', 'conta_id', 'descricao', 'valor', 'data', 'tipo', 'origem_tipo')
    ->havingRaw('COUNT(*) > 1')
    ->orderByDesc('qtd');

if ($userFilter) $dupeQuery->where('user_id', $userFilter);

$dupes = $dupeQuery->get();
$totalDupeExcesso = 0;

if ($dupes->isEmpty()) {
    echo "  Nenhuma duplicata encontrada.\n\n";
} else {
    echo "  " . $dupes->count() . " combinacoes duplicadas:\n";
    foreach ($dupes as $d) {
        $extra = ($d->qtd - 1) * (float) $d->valor;
        $totalDupeExcesso += $extra;
        echo sprintf(
            "    U:%d C:%d | %dx | '%s' | R$ %.2f | %s | %s | origem=%s | excesso=R$ %.2f\n",
            $d->user_id,
            $d->conta_id,
            $d->qtd,
            mb_substr($d->descricao, 0, 45),
            $d->valor,
            $d->data,
            $d->tipo,
            $d->origem_tipo ?? 'null',
            $extra
        );
    }
    echo "  Valor total em excesso: R$ " . number_format($totalDupeExcesso, 2, ',', '.') . "\n\n";
}

// ──────────────────────────────────────────────────────────────────
// 3. ITENS DE FATURA PAGOS SEM LANCAMENTO_ID
// ──────────────────────────────────────────────────────────────────
echo "--- 3. ITENS DE FATURA PAGOS SEM LANCAMENTO_ID (risco fallback) ---\n";
$orphanQuery = DB::table('faturas_cartao_itens')
    ->whereNull('lancamento_id')
    ->where('pago', 1);
if ($userFilter) $orphanQuery->where('user_id', $userFilter);

$orphanCount = (clone $orphanQuery)->count();
$orphanValor = (float) (clone $orphanQuery)->sum('valor');
echo "  Total: {$orphanCount} itens pagos sem lancamento_id\n";
echo "  Valor: R$ " . number_format($orphanValor, 2, ',', '.') . "\n\n";

// ──────────────────────────────────────────────────────────────────
// 4. LANCAMENTOS DE AGENDAMENTO (sistema removido)
// ──────────────────────────────────────────────────────────────────
echo "--- 4. LANCAMENTOS ORIGEM AGENDAMENTO ---\n";
$agendQuery = DB::table('lancamentos')
    ->where('origem_tipo', 'agendamento')
    ->where('pago', 1)->where('afeta_caixa', 1);
if ($userFilter) $agendQuery->where('user_id', $userFilter);

$agendCount = (clone $agendQuery)->count();
$agendValor = (float) (clone $agendQuery)->sum('valor');
echo "  Lancamentos de agendamento (pago+afeta_caixa): {$agendCount}\n";
echo "  Valor: R$ " . number_format($agendValor, 2, ',', '.') . "\n";

// Verificar se há agendamentos duplicando lançamentos normais
$agendDupes = DB::table('lancamentos as a')
    ->join('lancamentos as b', function ($join) {
        $join->on('a.user_id', '=', 'b.user_id')
            ->on('a.conta_id', '=', 'b.conta_id')
            ->on('a.valor', '=', 'b.valor')
            ->on('a.data', '=', 'b.data')
            ->on('a.tipo', '=', 'b.tipo')
            ->whereRaw('a.id < b.id');
    })
    ->where('a.origem_tipo', 'agendamento')
    ->where('b.origem_tipo', '!=', 'agendamento');
if ($userFilter) $agendDupes->where('a.user_id', $userFilter);

$agendDupeCount = $agendDupes->count();
echo "  Agendamentos duplicando outros lancamentos: {$agendDupeCount}\n\n";

// ──────────────────────────────────────────────────────────────────
// 5. CONTAS COM SALDO NEGATIVO (todos usuarios)
// ──────────────────────────────────────────────────────────────────
echo "--- 5. CONTAS COM SALDO NEGATIVO ---\n";
$contasQuery = DB::table('contas')->where('ativo', 1);
if ($userFilter) $contasQuery->where('user_id', $userFilter);
$contas = $contasQuery->get();

$negativas = [];
foreach ($contas as $c) {
    $si = (float) $c->saldo_inicial;
    $rec = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->where('tipo', 'receita')->sum('valor');
    $des = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->where('tipo', 'despesa')->sum('valor');
    $tIn = (float) DB::table('lancamentos')
        ->where('conta_id_destino', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');
    $tOut = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)->sum('valor');

    $saldo = $si + $rec - $des + $tIn - $tOut;
    if ($saldo < -0.01) {
        $negativas[] = (object) compact('c', 'si', 'rec', 'des', 'tIn', 'tOut', 'saldo');
    }
}

if (empty($negativas)) {
    echo "  Nenhuma conta com saldo negativo.\n\n";
} else {
    echo "  " . count($negativas) . " contas com saldo negativo:\n";
    foreach ($negativas as $n) {
        echo sprintf(
            "    U:%d #%d '%s': SI=%.2f +Rec=%.2f -Des=%.2f +TIn=%.2f -TOut=%.2f = R$ %.2f\n",
            $n->c->user_id,
            $n->c->id,
            $n->c->nome,
            $n->si,
            $n->rec,
            $n->des,
            $n->tIn,
            $n->tOut,
            $n->saldo
        );
    }
    echo "\n";
}

// ──────────────────────────────────────────────────────────────────
// 6. DETALHE POR USUARIO (se filtrado)
// ──────────────────────────────────────────────────────────────────
if ($userFilter) {
    echo "--- 6. DETALHE LANCAMENTOS PAGAMENTO_FATURA (user {$userFilter}) ---\n";
    $lancsFatura = DB::table('lancamentos')
        ->where('user_id', $userFilter)
        ->where('origem_tipo', 'pagamento_fatura')
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->orderBy('data')
        ->get(['id', 'conta_id', 'cartao_credito_id', 'descricao', 'valor', 'data']);

    if ($lancsFatura->isEmpty()) {
        echo "  Nenhum\n";
    } else {
        foreach ($lancsFatura as $l) {
            echo sprintf(
                "    #%d | conta=%d | cartao=%s | R$ %.2f | %s | %s\n",
                $l->id,
                $l->conta_id,
                $l->cartao_credito_id ?? '-',
                $l->valor,
                $l->data,
                mb_substr($l->descricao, 0, 60)
            );
        }
    }

    echo "\n--- 6b. LANCAMENTOS cartao_credito PAGOS (fallback) user {$userFilter} ---\n";
    $lancsCC = DB::table('lancamentos')
        ->where('user_id', $userFilter)
        ->where('origem_tipo', 'cartao_credito')
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->whereNotNull('conta_id')
        ->orderBy('data')
        ->get(['id', 'conta_id', 'cartao_credito_id', 'descricao', 'valor', 'data']);

    if ($lancsCC->isEmpty()) {
        echo "  Nenhum fallback\n";
    } else {
        echo "  " . $lancsCC->count() . " lancamentos fallback:\n";
        foreach ($lancsCC as $l) {
            echo sprintf(
                "    #%d | conta=%d | cartao=%s | R$ %.2f | %s | %s\n",
                $l->id,
                $l->conta_id,
                $l->cartao_credito_id ?? '-',
                $l->valor,
                $l->data,
                mb_substr($l->descricao, 0, 60)
            );
        }
    }

    echo "\n--- 6c. LANCAMENTOS sem origem_tipo + cartao_credito_id (antigos) ---\n";
    $lancsOld = DB::table('lancamentos')
        ->where('user_id', $userFilter)
        ->whereNotNull('cartao_credito_id')
        ->where('pago', 1)->where('afeta_caixa', 1)
        ->where('tipo', 'despesa')
        ->where(function ($q) {
            $q->whereNull('origem_tipo')
                ->orWhere('origem_tipo', 'normal');
        })
        ->orderBy('data')
        ->get(['id', 'conta_id', 'cartao_credito_id', 'descricao', 'valor', 'data', 'origem_tipo']);

    if ($lancsOld->isEmpty()) {
        echo "  Nenhum lancamento antigo\n";
    } else {
        echo "  " . $lancsOld->count() . " lancamentos antigos:\n";
        foreach ($lancsOld as $l) {
            echo sprintf(
                "    #%d | conta=%d | cartao=%d | R$ %.2f | %s | %s | origem=%s\n",
                $l->id,
                $l->conta_id,
                $l->cartao_credito_id,
                $l->valor,
                $l->data,
                mb_substr($l->descricao, 0, 50),
                $l->origem_tipo ?? 'null'
            );
        }
    }
}

echo "\n=== FIM DIAGNOSTICO ===\n";
