<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Support\Facades\DB;

$userId = 22;

echo "=== Verificando range de dados ===\n\n";

// Buscar primeiro e último lançamento
$primeiro = Lancamento::where('user_id', $userId)->orderBy('data', 'asc')->first();
$ultimo = Lancamento::where('user_id', $userId)->orderBy('data', 'desc')->first();

if ($primeiro) {
    echo "Primeiro lançamento: {$primeiro->data} - {$primeiro->descricao}\n";
}
if ($ultimo) {
    echo "Último lançamento: {$ultimo->data} - {$ultimo->descricao}\n";
}

echo "\n=== Lançamentos por mês ===\n\n";

$porMes = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->selectRaw('DATE_FORMAT(data, "%Y-%m") as mes, COUNT(*) as total, SUM(valor) as soma')
    ->groupBy('mes')
    ->orderBy('mes', 'desc')
    ->limit(12)
    ->get();

foreach ($porMes as $mes) {
    echo "{$mes->mes}: {$mes->total} lançamentos - R$ {$mes->soma}\n";
}
