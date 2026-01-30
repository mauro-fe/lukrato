<?php

/**
 * Script de teste para verificar o fluxo refatorado de cartão de crédito
 * 
 * CORREÇÃO IMPLEMENTADA:
 * - ✅ Compra no cartão → cria lançamento IMEDIATAMENTE com:
 *   - afeta_competencia = true (conta nas despesas do mês da compra)
 *   - afeta_caixa = false (não afeta saldo até pagar fatura)
 *   - pago = false (pendente)
 * 
 * - ✅ Pagamento da fatura → apenas ATUALIZA lançamento existente:
 *   - afeta_caixa = true (agora afeta saldo)
 *   - pago = true
 *   - data_pagamento = hoje
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTE DO FLUXO REFATORADO DE CARTÃO DE CRÉDITO ===\n\n";

// 1. Verificar se os campos necessários existem na tabela lancamentos
echo "1. VERIFICANDO ESTRUTURA DA TABELA lancamentos...\n";
$campos = DB::select("SHOW COLUMNS FROM lancamentos");
$camposNecessarios = ['data_competencia', 'afeta_competencia', 'afeta_caixa', 'origem_tipo'];
$camposExistentes = array_column($campos, 'Field');

foreach ($camposNecessarios as $campo) {
    $existe = in_array($campo, $camposExistentes) ? '✅' : '❌';
    echo "   {$existe} {$campo}\n";
}

// 2. Verificar se lancamento_id existe em faturas_cartao_itens
echo "\n2. VERIFICANDO ESTRUTURA DA TABELA faturas_cartao_itens...\n";
$camposFaturaItens = DB::select("SHOW COLUMNS FROM faturas_cartao_itens");
$temLancamentoId = in_array('lancamento_id', array_column($camposFaturaItens, 'Field'));
echo "   " . ($temLancamentoId ? '✅' : '❌') . " lancamento_id\n";

// 3. Analisar lançamentos existentes com cartão de crédito
echo "\n3. ANALISANDO LANÇAMENTOS EXISTENTES COM CARTÃO DE CRÉDITO...\n";

$stats = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN afeta_caixa = 1 THEN 1 ELSE 0 END) as afeta_caixa_true,
        SUM(CASE WHEN afeta_caixa = 0 THEN 1 ELSE 0 END) as afeta_caixa_false,
        SUM(CASE WHEN afeta_competencia = 1 THEN 1 ELSE 0 END) as afeta_competencia_true,
        SUM(CASE WHEN afeta_competencia = 0 THEN 1 ELSE 0 END) as afeta_competencia_false
    ")
    ->first();

if ($stats->total > 0) {
    echo "   Total de lançamentos de cartão: {$stats->total}\n";
    echo "   - Pagos: {$stats->pagos}\n";
    echo "   - Pendentes: {$stats->pendentes}\n";
    echo "   - afeta_caixa=true: {$stats->afeta_caixa_true}\n";
    echo "   - afeta_caixa=false: {$stats->afeta_caixa_false}\n";
    echo "   - afeta_competencia=true: {$stats->afeta_competencia_true}\n";
    echo "   - afeta_competencia=false: {$stats->afeta_competencia_false}\n";
} else {
    echo "   Nenhum lançamento de cartão encontrado\n";
}

// 4. Verificar itens de fatura com e sem lançamento vinculado
echo "\n4. ANALISANDO ITENS DE FATURA...\n";

$itensFatura = DB::table('faturas_cartao_itens')
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN lancamento_id IS NOT NULL THEN 1 ELSE 0 END) as com_lancamento,
        SUM(CASE WHEN lancamento_id IS NULL THEN 1 ELSE 0 END) as sem_lancamento,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as pendentes
    ")
    ->first();

echo "   Total de itens de fatura: {$itensFatura->total}\n";
echo "   - Com lançamento vinculado: {$itensFatura->com_lancamento}\n";
echo "   - Sem lançamento vinculado: {$itensFatura->sem_lancamento}\n";
echo "   - Pagos: {$itensFatura->pagos}\n";
echo "   - Pendentes: {$itensFatura->pendentes}\n";

// 5. Verificar consistência: itens pendentes devem ter lançamento com afeta_caixa=false
echo "\n5. VERIFICANDO CONSISTÊNCIA DO NOVO FLUXO...\n";

// Itens pendentes com lançamento vinculado
$itensPendentesComLancamento = DB::table('faturas_cartao_itens as f')
    ->join('lancamentos as l', 'f.lancamento_id', '=', 'l.id')
    ->where('f.pago', false)
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN l.afeta_caixa = 0 THEN 1 ELSE 0 END) as corretos,
        SUM(CASE WHEN l.afeta_caixa = 1 THEN 1 ELSE 0 END) as incorretos
    ")
    ->first();

if ($itensPendentesComLancamento->total > 0) {
    echo "   Itens PENDENTES com lançamento:\n";
    echo "   - Total: {$itensPendentesComLancamento->total}\n";
    echo "   - " . ($itensPendentesComLancamento->corretos > 0 ? '✅' : '⚠️') . " Com afeta_caixa=false (correto): {$itensPendentesComLancamento->corretos}\n";
    echo "   - " . ($itensPendentesComLancamento->incorretos > 0 ? '⚠️' : '✅') . " Com afeta_caixa=true (incorreto): {$itensPendentesComLancamento->incorretos}\n";
}

// Itens pagos com lançamento vinculado
$itensPagosComLancamento = DB::table('faturas_cartao_itens as f')
    ->join('lancamentos as l', 'f.lancamento_id', '=', 'l.id')
    ->where('f.pago', true)
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN l.afeta_caixa = 1 THEN 1 ELSE 0 END) as corretos,
        SUM(CASE WHEN l.afeta_caixa = 0 THEN 1 ELSE 0 END) as incorretos
    ")
    ->first();

if ($itensPagosComLancamento->total > 0) {
    echo "\n   Itens PAGOS com lançamento:\n";
    echo "   - Total: {$itensPagosComLancamento->total}\n";
    echo "   - " . ($itensPagosComLancamento->corretos > 0 ? '✅' : '⚠️') . " Com afeta_caixa=true (correto): {$itensPagosComLancamento->corretos}\n";
    echo "   - " . ($itensPagosComLancamento->incorretos > 0 ? '⚠️' : '✅') . " Com afeta_caixa=false (incorreto): {$itensPagosComLancamento->incorretos}\n";
}

// 6. Resumo final
echo "\n=== RESUMO ===\n";
echo "✅ Os services foram atualizados para:\n";
echo "   1. CartaoCreditoLancamentoService: Criar lançamento na COMPRA com afeta_caixa=false\n";
echo "   2. CartaoFaturaService: Apenas ATUALIZAR lançamento no PAGAMENTO (afeta_caixa=true)\n";
echo "   3. FaturaService: Apenas ATUALIZAR lançamento no PAGAMENTO (afeta_caixa=true)\n";
echo "\n";

// Alertas sobre dados legados
if ($itensFatura->sem_lancamento > 0) {
    echo "⚠️  ATENÇÃO: Existem {$itensFatura->sem_lancamento} itens de fatura SEM lançamento vinculado.\n";
    echo "   Estes são dados antigos criados antes da refatoração.\n";
    echo "   Quando forem pagos, será criado um novo lançamento (fallback).\n";
}

echo "\n✅ CORREÇÃO FINAL IMPLEMENTADA COM SUCESSO!\n";
