<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n🔍 DIAGNÓSTICO COMPLETO - LANÇAMENTOS\n";
echo "════════════════════════════════════════════════════════\n\n";

$hoje = date('Y-m-d');
$mesAtual = date('Y-m');

echo "Data de hoje: {$hoje}\n";
echo "Mês atual: {$mesAtual}\n\n";

// Verificar lançamentos de hoje
$lancamentosHoje = Lancamento::whereDate('data', $hoje)->get();

echo "📋 Lançamentos com data de HOJE ({$hoje}): {$lancamentosHoje->count()}\n\n";

if ($lancamentosHoje->count() > 0) {
    foreach ($lancamentosHoje as $lanc) {
        echo "ID {$lanc->id}:\n";
        echo "  • Descrição: {$lanc->descricao}\n";
        echo "  • Tipo: {$lanc->tipo}\n";
        echo "  • Valor: R$ {$lanc->valor}\n";
        echo "  • Data: {$lanc->data}\n";
        echo "  • User ID: {$lanc->user_id}\n";
        echo "  • Categoria ID: {$lanc->categoria_id}\n";
        echo "  • Conta ID: {$lanc->conta_id}\n";
        echo "  • Pago: " . ($lanc->pago ? 'Sim' : 'Não') . "\n";
        echo "  • Parcelamento ID: " . ($lanc->parcelamento_id ?? 'NULL') . "\n";
        echo "  • Created: {$lanc->created_at}\n";
        echo "\n";
    }
}

// Verificar lançamentos do mês atual
echo "📊 Lançamentos do mês {$mesAtual}:\n\n";

$lancamentosMes = Lancamento::where('data', 'like', $mesAtual . '%')
    ->orderBy('data', 'desc')
    ->orderBy('id', 'desc')
    ->get();

echo "Total: {$lancamentosMes->count()}\n\n";

// Simular a query do controller
echo "🔍 SIMULANDO QUERY DO CONTROLLER:\n";
echo "════════════════════════════════════════════════════════\n\n";

$userId = 32; // Ajuste conforme necessário
[$y, $m] = array_map('intval', explode('-', $mesAtual));
$from = sprintf('%04d-%02d-01', $y, $m);
$to = date('Y-m-t', strtotime($from));

echo "User ID: {$userId}\n";
echo "Período: {$from} até {$to}\n\n";

$q = DB::table('lancamentos as l')
    ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
    ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
    ->where('l.user_id', $userId)
    ->whereBetween('l.data', [$from, $to])
    ->orderBy('l.data', 'desc')
    ->orderBy('l.id', 'desc');

echo "SQL: " . $q->toSql() . "\n\n";

$rows = $q->selectRaw('
    l.id, l.data, l.tipo, l.valor, l.descricao, l.observacao, 
    l.categoria_id, l.conta_id, l.pago, l.parcelamento_id
')->get();

echo "Resultados: {$rows->count()}\n\n";

if ($rows->count() > 0) {
    echo "Primeiros 10 lançamentos:\n\n";
    foreach ($rows->take(10) as $r) {
        echo "ID {$r->id}: {$r->descricao} - R$ {$r->valor} ({$r->data})\n";
    }
} else {
    echo "❌ Nenhum lançamento encontrado na query!\n";
}

echo "\n════════════════════════════════════════════════════════\n\n";
