<?php

/**
 * Diagnóstico de faturas pagas vs lançamentos
 * 
 * Verifica se as faturas pagas estão corretamente refletidas nos saldos
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=======================================================================\n";
echo "   DIAGNOSTICO DE FATURAS PAGAS\n";
echo "=======================================================================\n\n";

$userId = 1;

// Agora vamos direto ao ponto - verificar lançamentos de cartão
echo "=======================================================================\n";
echo "   ANALISE DE LANCAMENTOS DE CARTAO\n";
echo "=======================================================================\n\n";

// Lançamentos de cartão PAGOS (pago=1)
$lancamentosPagos = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->where('user_id', $userId)
    ->where('pago', 1)
    ->get();

echo "Lancamentos de cartao PAGOS (pago=1): " . $lancamentosPagos->count() . "\n";
echo "Valor total: R$ " . number_format(abs($lancamentosPagos->sum('valor')), 2, ',', '.') . "\n\n";

// Desses pagos, quantos têm conta_id?
$pagosComConta = $lancamentosPagos->filter(fn($l) => $l->conta_id !== null)->count();
$pagosSemConta = $lancamentosPagos->filter(fn($l) => $l->conta_id === null)->count();

echo "  - Com conta_id: {$pagosComConta}\n";
echo "  - SEM conta_id: {$pagosSemConta}\n\n";

// Desses pagos, quantos têm afeta_caixa = true?
$pagosAfetaCaixa = $lancamentosPagos->filter(fn($l) => $l->afeta_caixa == 1)->count();
$pagosNaoAfetaCaixa = $lancamentosPagos->filter(fn($l) => $l->afeta_caixa == 0 || $l->afeta_caixa === null)->count();

echo "  - afeta_caixa = true: {$pagosAfetaCaixa}\n";
echo "  - afeta_caixa = false/null: {$pagosNaoAfetaCaixa}\n\n";

// Lançamentos de cartão PENDENTES (pago=0)
$lancamentosPendentes = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->where('user_id', $userId)
    ->where('pago', 0)
    ->get();

echo "Lancamentos de cartao PENDENTES (pago=0): " . $lancamentosPendentes->count() . "\n";
echo "Valor total: R$ " . number_format(abs($lancamentosPendentes->sum('valor')), 2, ',', '.') . "\n\n";

// Desses pendentes, quantos têm conta_id?
$pendentesComConta = $lancamentosPendentes->filter(fn($l) => $l->conta_id !== null)->count();
$pendentesSemConta = $lancamentosPendentes->filter(fn($l) => $l->conta_id === null)->count();

echo "  - Com conta_id: {$pendentesComConta}\n";
echo "  - SEM conta_id: {$pendentesSemConta}\n\n";

// Verificar FaturaCartaoItem - quais estão em faturas pagas?
echo "=======================================================================\n";
echo "   ANALISE DE FATURAS (FaturaCartaoItem)\n";
echo "=======================================================================\n\n";

// Buscar itens com fatura_paga = 1
$itensFaturaPaga = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('fatura_paga', 1)
    ->get();

echo "Itens em faturas PAGAS (fatura_paga=1): " . $itensFaturaPaga->count() . "\n";
echo "Valor total: R$ " . number_format(abs($itensFaturaPaga->sum('valor')), 2, ',', '.') . "\n\n";

// Verificar se os lançamentos correspondentes têm afeta_caixa = true
$itensComLancamento = 0;
$lancamentosAfetaCaixaTrue = 0;
$lancamentosAfetaCaixaFalse = 0;
$lancamentosPagosTrue = 0;
$lancamentosPagosFalse = 0;

foreach ($itensFaturaPaga as $item) {
    if ($item->lancamento_id) {
        $itensComLancamento++;
        $lanc = DB::table('lancamentos')->where('id', $item->lancamento_id)->first();
        if ($lanc) {
            if ($lanc->afeta_caixa) {
                $lancamentosAfetaCaixaTrue++;
            } else {
                $lancamentosAfetaCaixaFalse++;
            }
            if ($lanc->pago) {
                $lancamentosPagosTrue++;
            } else {
                $lancamentosPagosFalse++;
            }
        }
    }
}

echo "Itens com lancamento_id: {$itensComLancamento}\n";
echo "  - Lancamentos com afeta_caixa=true: {$lancamentosAfetaCaixaTrue}\n";
echo "  - Lancamentos com afeta_caixa=false: {$lancamentosAfetaCaixaFalse}\n";
echo "  - Lancamentos com pago=true: {$lancamentosPagosTrue}\n";
echo "  - Lancamentos com pago=false: {$lancamentosPagosFalse}\n\n";

if ($lancamentosAfetaCaixaFalse > 0 || $lancamentosPagosFalse > 0) {
    echo "⚠️  PROBLEMA DETECTADO!\n";
    echo "Faturas foram pagas mas os lancamentos nao estao marcados corretamente.\n\n";
}

// Verificar itens PENDENTES
$itensFaturaPendente = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('fatura_paga', 0)
    ->get();

echo "Itens em faturas PENDENTES (fatura_paga=0): " . $itensFaturaPendente->count() . "\n";
echo "Valor total: R$ " . number_format(abs($itensFaturaPendente->sum('valor')), 2, ',', '.') . "\n\n";

// RESUMO FINAL
echo "=======================================================================\n";
echo "   RESUMO DO PROBLEMA\n";
echo "=======================================================================\n\n";

echo "REGRA DE NEGOCIO CORRETA:\n";
echo "1. Lancamento de cartao PENDENTE deve ter:\n";
echo "   - pago = 0\n";
echo "   - afeta_caixa = false (nao afeta saldo da conta)\n";
echo "   - conta_id = NULL\n\n";

echo "2. Lancamento de cartao PAGO (fatura paga) deve ter:\n";
echo "   - pago = 1\n";
echo "   - afeta_caixa = true (afeta saldo da conta que pagou)\n";
echo "   - conta_id = conta que pagou a fatura\n\n";

// Verificar se há lançamentos PAGOS sem conta_id ou sem afeta_caixa
$problematicos = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->where('user_id', $userId)
    ->where('pago', 1)
    ->where(function ($q) {
        $q->whereNull('conta_id')
            ->orWhere('afeta_caixa', 0)
            ->orWhereNull('afeta_caixa');
    })
    ->get();

echo "PROBLEMAS ENCONTRADOS:\n\n";

if ($problematicos->count() > 0) {
    echo "❌ Lancamentos PAGOS sem conta_id ou afeta_caixa=false: " . $problematicos->count() . "\n";
    echo "   Valor: R$ " . number_format(abs($problematicos->sum('valor')), 2, ',', '.') . "\n";
    echo "   Esses lancamentos deveriam estar descontando do saldo mas NAO ESTAO!\n\n";

    // Mostrar exemplos
    echo "Exemplos:\n";
    foreach ($problematicos->take(5) as $l) {
        echo "  ID {$l->id}: {$l->descricao}\n";
        echo "    Valor: R$ " . number_format(abs($l->valor), 2, ',', '.') . "\n";
        echo "    pago: " . ($l->pago ? 'true' : 'false') . "\n";
        echo "    afeta_caixa: " . ($l->afeta_caixa ? 'true' : 'false/null') . "\n";
        echo "    conta_id: " . ($l->conta_id ?: 'NULL') . "\n";
        echo "\n";
    }
} else {
    echo "✅ Nenhum lancamento pago com problema de conta_id/afeta_caixa\n\n";
}

// Verificar pendentes com problemas (já corrigidos)
$pendentesProblem = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->where('user_id', $userId)
    ->where('pago', 0)
    ->whereNotNull('conta_id')
    ->count();

if ($pendentesProblem > 0) {
    echo "❌ Lancamentos PENDENTES com conta_id definido: {$pendentesProblem}\n";
    echo "   Esses estao afetando o saldo indevidamente!\n\n";
} else {
    echo "✅ Lancamentos pendentes estao corretos (sem conta_id)\n\n";
}

echo "=== FIM DO DIAGNOSTICO ===\n";
