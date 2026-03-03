<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Check column definition
$col = DB::select("SHOW COLUMNS FROM lancamentos WHERE Field = 'origem_tipo'");
print_r($col);

// Check for all users: how many lancamentos have origem_tipo=normal vs cartao_credito without a card?
echo "\n=== GLOBAL: lancamentos por origem_tipo (sem transferencia) ===" . PHP_EOL;
$stats = DB::table('lancamentos')
    ->where('eh_transferencia', 0)
    ->selectRaw("COALESCE(origem_tipo, 'NULL') as ot, COUNT(*) as cnt")
    ->groupBy('origem_tipo')
    ->get();
foreach ($stats as $s) {
    echo "  {$s->ot}: {$s->cnt}" . PHP_EOL;
}

echo "\n=== lancamentos com origem_tipo='cartao_credito' AND cartao_credito_id IS NULL ===" . PHP_EOL;
$wrongCC = DB::table('lancamentos')
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id')
    ->selectRaw("user_id, COUNT(*) as cnt")
    ->groupBy('user_id')
    ->get();
foreach ($wrongCC as $w) {
    echo "  user {$w->user_id}: {$w->cnt} lancamentos" . PHP_EOL;
}
