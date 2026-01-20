<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;

echo "\n🔍 VERIFICAÇÃO FINAL\n";
echo "════════════════════════════════════════════════════════\n\n";

// Verificar itens pagos
$itensPagos = FaturaCartaoItem::where('pago', true)->count();
$itensSemLancamento = FaturaCartaoItem::where('pago', true)->whereNull('lancamento_id')->count();
$itensComLancamento = FaturaCartaoItem::where('pago', true)->whereNotNull('lancamento_id')->count();

echo "📊 ITENS DE FATURA:\n";
echo "   • Total de itens pagos: {$itensPagos}\n";
echo "   • Com lançamento vinculado: {$itensComLancamento}\n";
echo "   • Sem lançamento: {$itensSemLancamento}\n\n";

// Verificar lançamentos incorretos
$lancamentosIncorretos = Lancamento::where('descricao', 'like', 'Pagamento Fatura%')->count();

echo "📋 LANÇAMENTOS:\n";
echo "   • Lançamentos incorretos ('Pagamento Fatura'): {$lancamentosIncorretos}\n\n";

if ($lancamentosIncorretos > 0) {
    echo "⚠️  ATENÇÃO: Ainda há lançamentos incorretos!\n";
    echo "   Execute: php cli/cleanup_pagamento_fatura.php\n\n";
}

if ($itensSemLancamento > 0) {
    echo "⚠️  ATENÇÃO: Há itens pagos sem lançamento!\n";
    echo "   Execute: php cli/fix_itens_para_lancamentos.php\n\n";
}

if ($lancamentosIncorretos == 0 && $itensSemLancamento == 0) {
    echo "✅ TUDO CORRETO!\n";
    echo "   A partir de agora, quando você pagar uma fatura:\n";
    echo "   • Cada item virará um lançamento individual\n";
    echo "   • Na data do pagamento (hoje)\n";
    echo "   • Com a categoria original do item\n\n";
}

// Mostrar exemplo
$exemploItem = FaturaCartaoItem::where('pago', true)
    ->whereNotNull('lancamento_id')
    ->with(['lancamento', 'cartao'])
    ->first();

if ($exemploItem) {
    echo "📝 EXEMPLO DE ITEM CORRETO:\n";
    echo "   Item ID: {$exemploItem->id}\n";
    echo "   Descrição: {$exemploItem->descricao}\n";
    echo "   Valor: R$ {$exemploItem->valor}\n";
    echo "   Pago em: {$exemploItem->data_pagamento}\n";
    echo "   Lançamento vinculado: ID {$exemploItem->lancamento_id}\n";

    if ($exemploItem->lancamento) {
        echo "\n   Lançamento:\n";
        echo "   • Data: {$exemploItem->lancamento->data}\n";
        echo "   • Descrição: {$exemploItem->lancamento->descricao}\n";
        echo "   • Valor: R$ {$exemploItem->lancamento->valor}\n";
    }
    echo "\n";
}
