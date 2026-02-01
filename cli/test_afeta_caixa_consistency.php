<?php

/**
 * Script de validação da consistência do campo afeta_caixa
 * 
 * Este script verifica se:
 * 1. Lançamentos de cartão pendentes têm afeta_caixa = false
 * 2. Lançamentos de cartão pagos têm afeta_caixa = true
 * 3. Lançamentos normais têm afeta_caixa = true (ou null para backward compatibility)
 * 4. O saldo calculado está consistente
 * 
 * @author Lukrato Team
 * @date 2026-02-01
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\Conta;

echo "=======================================================================\n";
echo "   VALIDAÇÃO DE CONSISTÊNCIA - CAMPO afeta_caixa\n";
echo "=======================================================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Verificar se o campo afeta_caixa existe
echo "1️⃣  Verificando estrutura do banco de dados...\n";

$hasAfetaCaixa = DB::schema()->hasColumn('lancamentos', 'afeta_caixa');
$hasDataCompetencia = DB::schema()->hasColumn('lancamentos', 'data_competencia');
$hasAfetaCompetencia = DB::schema()->hasColumn('lancamentos', 'afeta_competencia');
$hasOrigemTipo = DB::schema()->hasColumn('lancamentos', 'origem_tipo');

if ($hasAfetaCaixa) {
    $success[] = "✅ Campo afeta_caixa existe";
} else {
    $errors[] = "❌ Campo afeta_caixa NÃO existe - execute a migration!";
}

if ($hasDataCompetencia) {
    $success[] = "✅ Campo data_competencia existe";
} else {
    $errors[] = "❌ Campo data_competencia NÃO existe";
}

if ($hasAfetaCompetencia) {
    $success[] = "✅ Campo afeta_competencia existe";
} else {
    $errors[] = "❌ Campo afeta_competencia NÃO existe";
}

if ($hasOrigemTipo) {
    $success[] = "✅ Campo origem_tipo existe";
} else {
    $errors[] = "❌ Campo origem_tipo NÃO existe";
}

echo "\n";

// 2. Verificar lançamentos de cartão pendentes (devem ter afeta_caixa = false)
echo "2️⃣  Verificando lançamentos de cartão PENDENTES...\n";

$lancamentosCartaoPendentes = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', false)
    ->where('tipo', 'despesa')
    ->get();

$pendentesComAfetaCaixaTrue = $lancamentosCartaoPendentes->filter(function ($l) {
    return $l->afeta_caixa === true;
});

$pendentesComAfetaCaixaNull = $lancamentosCartaoPendentes->filter(function ($l) {
    return $l->afeta_caixa === null;
});

$pendentesComAfetaCaixaFalse = $lancamentosCartaoPendentes->filter(function ($l) {
    return $l->afeta_caixa === false;
});

echo "   Total de lançamentos de cartão pendentes: " . $lancamentosCartaoPendentes->count() . "\n";
echo "   - Com afeta_caixa = TRUE:  " . $pendentesComAfetaCaixaTrue->count() . " ⚠️\n";
echo "   - Com afeta_caixa = FALSE: " . $pendentesComAfetaCaixaFalse->count() . " ✅\n";
echo "   - Com afeta_caixa = NULL:  " . $pendentesComAfetaCaixaNull->count() . " ⚠️\n";

if ($pendentesComAfetaCaixaTrue->count() > 0) {
    $errors[] = "❌ " . $pendentesComAfetaCaixaTrue->count() . " lançamentos PENDENTES com afeta_caixa=true (deveria ser false)";
}

if ($pendentesComAfetaCaixaNull->count() > 0) {
    $warnings[] = "⚠️ " . $pendentesComAfetaCaixaNull->count() . " lançamentos PENDENTES com afeta_caixa=null (recomendado: false)";
}

echo "\n";

// 3. Verificar lançamentos de cartão pagos (devem ter afeta_caixa = true)
echo "3️⃣  Verificando lançamentos de cartão PAGOS...\n";

$lancamentosCartaoPagos = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', true)
    ->where('tipo', 'despesa')
    ->get();

$pagosComAfetaCaixaFalse = $lancamentosCartaoPagos->filter(function ($l) {
    return $l->afeta_caixa === false;
});

$pagosComAfetaCaixaTrue = $lancamentosCartaoPagos->filter(function ($l) {
    return $l->afeta_caixa === true;
});

$pagosComAfetaCaixaNull = $lancamentosCartaoPagos->filter(function ($l) {
    return $l->afeta_caixa === null;
});

echo "   Total de lançamentos de cartão pagos: " . $lancamentosCartaoPagos->count() . "\n";
echo "   - Com afeta_caixa = TRUE:  " . $pagosComAfetaCaixaTrue->count() . " ✅\n";
echo "   - Com afeta_caixa = FALSE: " . $pagosComAfetaCaixaFalse->count() . " ⚠️\n";
echo "   - Com afeta_caixa = NULL:  " . $pagosComAfetaCaixaNull->count() . " ✅ (backward compatible)\n";

if ($pagosComAfetaCaixaFalse->count() > 0) {
    $errors[] = "❌ " . $pagosComAfetaCaixaFalse->count() . " lançamentos PAGOS com afeta_caixa=false (deveria ser true)";
}

echo "\n";

// 4. Verificar vínculo entre FaturaCartaoItem e Lancamento
echo "4️⃣  Verificando vínculo FaturaCartaoItem -> Lancamento...\n";

$itensComLancamento = FaturaCartaoItem::whereNotNull('lancamento_id')->count();
$itensSemLancamento = FaturaCartaoItem::whereNull('lancamento_id')->count();
$totalItens = $itensComLancamento + $itensSemLancamento;

echo "   Total de itens de fatura: {$totalItens}\n";
echo "   - Com lancamento_id vinculado: {$itensComLancamento}\n";
echo "   - Sem lancamento_id (legado):  {$itensSemLancamento}\n";

if ($itensSemLancamento > 0 && $itensComLancamento > 0) {
    $warnings[] = "⚠️ {$itensSemLancamento} itens de fatura sem lancamento_id (dados legados)";
}

// Verificar se lançamentos vinculados existem
$itensComLancamentoOrfao = FaturaCartaoItem::whereNotNull('lancamento_id')
    ->whereNotIn('lancamento_id', function ($query) {
        $query->select('id')->from('lancamentos');
    })
    ->count();

if ($itensComLancamentoOrfao > 0) {
    $errors[] = "❌ {$itensComLancamentoOrfao} itens de fatura com lancamento_id apontando para lançamento inexistente";
} else {
    $success[] = "✅ Todos os vínculos FaturaCartaoItem -> Lancamento estão válidos";
}

echo "\n";

// 5. Verificar consistência de saldos por conta
echo "5️⃣  Verificando consistência de saldos...\n";

$contas = Conta::take(5)->get();

foreach ($contas as $conta) {
    $saldoInicial = (float) ($conta->saldo_inicial ?? 0);

    // Saldo CORRETO (considerando afeta_caixa)
    $receitasCorreto = (float) Lancamento::where('conta_id', $conta->id)
        ->where('tipo', 'receita')
        ->where('eh_transferencia', 0)
        ->where(function ($q) {
            $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
        })
        ->sum('valor');

    $despesasCorreto = (float) Lancamento::where('conta_id', $conta->id)
        ->where('tipo', 'despesa')
        ->where('eh_transferencia', 0)
        ->where(function ($q) {
            $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
        })
        ->sum('valor');

    $saldoCorreto = $saldoInicial + $receitasCorreto - $despesasCorreto;

    // Saldo SEM filtro (errado se não considerar afeta_caixa)
    $receitasSemFiltro = (float) Lancamento::where('conta_id', $conta->id)
        ->where('tipo', 'receita')
        ->where('eh_transferencia', 0)
        ->sum('valor');

    $despesasSemFiltro = (float) Lancamento::where('conta_id', $conta->id)
        ->where('tipo', 'despesa')
        ->where('eh_transferencia', 0)
        ->sum('valor');

    $saldoSemFiltro = $saldoInicial + $receitasSemFiltro - $despesasSemFiltro;

    $diferenca = abs($saldoCorreto - $saldoSemFiltro);

    echo "   Conta #{$conta->id} ({$conta->nome}):\n";
    echo "      Saldo correto (com afeta_caixa): R$ " . number_format($saldoCorreto, 2, ',', '.') . "\n";
    echo "      Saldo sem filtro:                R$ " . number_format($saldoSemFiltro, 2, ',', '.') . "\n";

    if ($diferenca > 0.01) {
        echo "      ⚠️ Diferença: R$ " . number_format($diferenca, 2, ',', '.') . " (lançamentos pendentes de cartão)\n";
    } else {
        echo "      ✅ Sem diferença\n";
    }
}

echo "\n";

// Resumo final
echo "=======================================================================\n";
echo "   RESUMO\n";
echo "=======================================================================\n\n";

if (!empty($success)) {
    echo "✅ SUCESSOS:\n";
    foreach ($success as $s) {
        echo "   {$s}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️ AVISOS:\n";
    foreach ($warnings as $w) {
        echo "   {$w}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERROS:\n";
    foreach ($errors as $e) {
        echo "   {$e}\n";
    }
    echo "\n";
    echo "💡 Execute o script de normalização para corrigir:\n";
    echo "   php cli/normalizar_competencia_cartao.php\n\n";
} else {
    echo "🎉 Nenhum erro encontrado! O sistema está consistente.\n\n";
}

echo "=======================================================================\n";
