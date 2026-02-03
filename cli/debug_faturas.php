<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Fatura;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== FATURAS (tabela 'faturas') ===\n\n";

$faturas = DB::table('faturas')->limit(30)->get();

echo "ID | Descrição | Status\n";
echo str_repeat('-', 70) . "\n";

foreach ($faturas as $f) {
    echo "{$f->id} | {$f->descricao} | {$f->status}\n";
}

echo "\nTotal: " . count($faturas) . " faturas\n";

// Verificar os mes_referencia dos itens agrupados
echo "\n=== ITENS AGRUPADOS POR MÊS/ANO ===\n";
$itens = DB::table('faturas_cartao_itens')
    ->select('mes_referencia', 'ano_referencia')
    ->selectRaw('COUNT(*) as total')
    ->groupBy('mes_referencia', 'ano_referencia')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->get();

foreach ($itens as $i) {
    echo "{$i->mes_referencia}/{$i->ano_referencia}: {$i->total} itens\n";
}