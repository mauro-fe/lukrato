<?php

/**
 * Verificar lançamentos de pagamento de fatura
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "\n🔍 LANÇAMENTOS DE PAGAMENTO DE FATURA\n";
echo "════════════════════════════════════════════════════════\n\n";

$lancamentos = Lancamento::where('descricao', 'like', 'Pagamento Fatura%')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

if ($lancamentos->isEmpty()) {
    echo "❌ Nenhum lançamento de pagamento de fatura encontrado!\n\n";
    exit(0);
}

echo "Total: {$lancamentos->count()} lançamentos encontrados\n\n";

foreach ($lancamentos as $lanc) {
    echo "┌─────────────────────────────────────────────────────\n";
    echo "│ ID: {$lanc->id}\n";
    echo "│ User ID: {$lanc->user_id}\n";
    echo "│ Descrição: {$lanc->descricao}\n";
    echo "│ Valor: R$ " . number_format($lanc->valor, 2, ',', '.') . "\n";
    echo "│ Data: {$lanc->data}\n";
    echo "│ Tipo: {$lanc->tipo}\n";
    echo "│ Pago: " . ($lanc->pago ? 'Sim' : 'Não') . "\n";
    echo "│ Categoria ID: " . ($lanc->categoria_id ?? 'null') . "\n";
    echo "│ Conta ID: " . ($lanc->conta_id ?? 'null') . "\n";
    echo "│ Parcelamento ID: " . ($lanc->parcelamento_id ?? 'null') . "\n";
    echo "│ Criado em: {$lanc->created_at}\n";
    echo "└─────────────────────────────────────────────────────\n\n";
}

// Verificar se aparecem na query do index
echo "\n🔍 TESTANDO QUERY DO INDEX (mês atual)\n";
echo "════════════════════════════════════════════════════════\n\n";

$userId = $lancamentos->first()->user_id ?? 1;
$month = date('Y-m');
[$y, $m] = array_map('intval', explode('-', $month));
$from = sprintf('%04d-%02d-01', $y, $m);
$to = date('Y-m-t', strtotime($from));

echo "Filtrando para user_id: {$userId}\n";
echo "Período: {$from} até {$to}\n\n";

$query = \Illuminate\Database\Capsule\Manager::table('lancamentos as l')
    ->where('l.user_id', $userId)
    ->whereBetween('l.data', [$from, $to])
    ->where(function ($w) {
        $w->whereNull('l.parcelamento_id')
            ->orWhere('l.pago', 0);
    })
    ->orderBy('l.data', 'desc')
    ->orderBy('l.id', 'desc');

echo "SQL: " . $query->toSql() . "\n\n";

$results = $query->get();
echo "Total de lançamentos na query: {$results->count()}\n\n";

$pagamentosFatura = $results->filter(function ($r) {
    return str_contains($r->descricao, 'Pagamento Fatura');
});

echo "Lançamentos de 'Pagamento Fatura' na query: {$pagamentosFatura->count()}\n\n";

if ($pagamentosFatura->isEmpty()) {
    echo "❌ PROBLEMA CONFIRMADO: Lançamentos de pagamento não aparecem na query!\n";
    echo "\nPossíveis causas:\n";
    echo "1. Campo 'pago' = 1 E campo 'parcelamento_id' não é null (filtro linha 145-149)\n";
    echo "2. Data fora do range do mês\n";
    echo "3. User ID diferente\n\n";

    // Analisar o primeiro lançamento de pagamento
    $primeiro = $lancamentos->first();
    echo "\nAnalisando o lançamento mais recente (ID {$primeiro->id}):\n";
    echo "• pago = " . ($primeiro->pago ? 'true' : 'false') . "\n";
    echo "• parcelamento_id = " . ($primeiro->parcelamento_id ?? 'null') . "\n";
    echo "• Passa no filtro? ";

    if (!$primeiro->parcelamento_id || !$primeiro->pago) {
        echo "✅ SIM\n";
    } else {
        echo "❌ NÃO (pago=1 E parcelamento_id != null)\n";
    }
} else {
    echo "✅ Lançamentos aparecem na query normalmente!\n";
}

echo "\n";
