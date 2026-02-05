<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Application\Models\CartaoCredito;

echo "=== ITENS DE FATURA POR STATUS DE PAGAMENTO ===\n";
$totalPagos = FaturaCartaoItem::where('pago', true)->count();
$totalNaoPagos = FaturaCartaoItem::where('pago', false)->count();

echo "Itens pagos: $totalPagos\n";
echo "Itens não pagos: $totalNaoPagos\n";

echo "\n=== FATURAS POR STATUS ===\n";
$faturas = Fatura::selectRaw('status, count(*) as total')->groupBy('status')->get();
foreach ($faturas as $f) {
    echo "{$f->status}: {$f->total}\n";
}

echo "\n=== CARTÕES ATIVOS ===\n";
$cartoes = CartaoCredito::where('ativo', true)->get();
foreach ($cartoes as $cartao) {
    echo "ID: {$cartao->id}, Nome: {$cartao->nome_cartao}, Dia Venc: {$cartao->dia_vencimento}\n";
}

echo "\n=== SIMULAÇÃO DE VERIFICAÇÃO DE VENCIMENTOS ===\n";

$dataHoje = new DateTime();
$mesAtual = (int)$dataHoje->format('n');
$anoAtual = (int)$dataHoje->format('Y');

echo "Hoje: " . $dataHoje->format('Y-m-d') . " (mês: $mesAtual, ano: $anoAtual)\n\n";

foreach ($cartoes as $cartao) {
    $dataVencimento = DateTime::createFromFormat(
        'Y-n-j',
        "{$anoAtual}-{$mesAtual}-{$cartao->dia_vencimento}"
    );
    
    if (!$dataVencimento) {
        echo "Cartão {$cartao->nome_cartao}: Erro ao criar data de vencimento\n";
        continue;
    }
    
    if ($dataVencimento < $dataHoje) {
        $dataVencimento->modify('+1 month');
        $mesAtual = (int)$dataVencimento->format('n');
        $anoAtual = (int)$dataVencimento->format('Y');
    }
    
    echo "Cartão: {$cartao->nome_cartao}\n";
    echo "  Data calculada vencimento: " . $dataVencimento->format('Y-m-d') . "\n";
    echo "  Buscando itens no mês $mesAtual/$anoAtual...\n";
    
    $totalFatura = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
        ->where('pago', false)
        ->whereYear('data_vencimento', $anoAtual)
        ->whereMonth('data_vencimento', $mesAtual)
        ->sum('valor');
    
    $itensCount = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
        ->where('pago', false)
        ->whereYear('data_vencimento', $anoAtual)
        ->whereMonth('data_vencimento', $mesAtual)
        ->count();
    
    echo "  Itens não pagos encontrados: $itensCount\n";
    echo "  Total não pago: R$ " . number_format((float)$totalFatura, 2, ',', '.') . "\n";
    
    if ($totalFatura > 0) {
        echo "  ⚠️  ESTE CARTÃO GERARIA ALERTA DE VENCIMENTO!\n";
    }
    
    echo "\n";
}

echo "=== ITENS NÃO PAGOS DETALHADOS (últimos 20) ===\n";
$itensNaoPagos = FaturaCartaoItem::where('pago', false)
    ->orderBy('data_vencimento', 'desc')
    ->limit(20)
    ->get();

foreach ($itensNaoPagos as $i) {
    echo "ID: {$i->id}, Cartão: {$i->cartao_credito_id}, Desc: {$i->descricao}, Venc: {$i->data_vencimento}, Valor: {$i->valor}\n";
}

echo "\n=== VERIFICAÇÃO: FATURAS PAGAS COM ITENS NÃO PAGOS ===\n";
$faturasPagas = Fatura::where('status', 'paga')->get();
echo "Faturas com status 'paga': " . count($faturasPagas) . "\n";

foreach ($faturasPagas as $f) {
    $itensNaoPagos = FaturaCartaoItem::where('fatura_id', $f->id)->where('pago', false)->count();
    $itensPagos = FaturaCartaoItem::where('fatura_id', $f->id)->where('pago', true)->count();
    
    if ($itensNaoPagos > 0) {
        echo "  ⚠️ INCONSISTÊNCIA - Fatura ID {$f->id}: {$itensPagos} pagos, {$itensNaoPagos} NÃO PAGOS\n";
    } else {
        echo "  ✅ Fatura ID {$f->id}: {$itensPagos} pagos, {$itensNaoPagos} não pagos\n";
    }
}
