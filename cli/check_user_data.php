<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Lancamento;
use Application\Models\Categoria;

$userId = 29;

echo "🔍 Verificando dados do usuário ID: {$userId}\n\n";

// Categorias
$categorias = Categoria::where('user_id', $userId)->get();
echo "📁 CATEGORIAS: " . $categorias->count() . " categorias\n";
foreach ($categorias as $cat) {
    echo "   - {$cat->nome} (ID: {$cat->id})\n";
}
echo "\n";

// Lançamentos
$lancamentos = Lancamento::where('user_id', $userId)->get();
echo "💰 LANÇAMENTOS: " . $lancamentos->count() . " lançamento(s)\n";
foreach ($lancamentos as $lanc) {
    echo "   - {$lanc->descricao}: R$ {$lanc->valor} ({$lanc->tipo}) - Data: {$lanc->data}\n";
}
echo "\n";

// Saldo do mês atual
$currentMonth = \Carbon\Carbon::now()->format('Y-m');
$receitas = Lancamento::where('user_id', $userId)
    ->where('tipo', 'receita')
    ->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$currentMonth])
    ->sum('valor');

$despesas = Lancamento::where('user_id', $userId)
    ->where('tipo', 'despesa')
    ->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$currentMonth])
    ->sum('valor');

echo "📊 MÊS ATUAL ({$currentMonth}):\n";
echo "   Receitas: R$ {$receitas}\n";
echo "   Despesas: R$ {$despesas}\n";
echo "   Saldo: R$ " . ($receitas - $despesas) . "\n";
echo "   Positivo? " . ($receitas > $despesas ? '✅ SIM' : '❌ NÃO') . "\n";

if ($receitas > 0) {
    $savingsPercentage = (($receitas - $despesas) / $receitas) * 100;
    echo "   Economia: " . number_format($savingsPercentage, 2) . "%\n";
}
