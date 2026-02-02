<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ESTRUTURA DA TABELA FATURAS ===\n";
$cols = DB::select('DESCRIBE faturas');
foreach ($cols as $c) {
    echo "  {$c->Field} ({$c->Type})\n";
}

echo "\n=== ESTRUTURA DA TABELA FATURAS_CARTAO_ITENS ===\n";
$cols = DB::select('DESCRIBE faturas_cartao_itens');
foreach ($cols as $c) {
    echo "  {$c->Field} ({$c->Type})\n";
}

echo "\n=== CONTAGEM DE REGISTROS ===\n";
$countFaturas = DB::table('faturas')->count();
$countItens = DB::table('faturas_cartao_itens')->count();
echo "  faturas: $countFaturas registros\n";
echo "  faturas_cartao_itens: $countItens registros\n";

echo "\n=== EXEMPLO DE ITENS COM FATURA ===\n";
$itens = DB::table('faturas_cartao_itens')
    ->whereNotNull('fatura_id')
    ->limit(5)
    ->get();
foreach ($itens as $item) {
    echo "  Item ID: {$item->id}, Fatura ID: {$item->fatura_id}, Desc: {$item->descricao}\n";
}

echo "\n=== QUANTOS ITENS POR FATURA (amostra) ===\n";
$grouped = DB::table('faturas_cartao_itens')
    ->select('fatura_id', DB::raw('COUNT(*) as total'))
    ->whereNotNull('fatura_id')
    ->groupBy('fatura_id')
    ->limit(10)
    ->get();
foreach ($grouped as $g) {
    echo "  Fatura ID: {$g->fatura_id} -> {$g->total} itens\n";
}
