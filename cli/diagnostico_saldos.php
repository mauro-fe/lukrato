<?php

/**
 * Diagnóstico de saldos de contas
 * Verifica consistência de afeta_caixa, pago, e calcula saldos
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DIAGNOSTICO DE SALDOS ===\n\n";

// 1. afeta_caixa NULL
$nulls = DB::table('lancamentos')->whereNull('afeta_caixa')->count();
echo "1. Lancamentos com afeta_caixa = NULL: {$nulls}\n";

// 2. Pendentes com afeta_caixa=1 (inconsistente)
$pendentesErrados = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('afeta_caixa', 1)
    ->count();
echo "2. Pendentes com afeta_caixa=1 (incorreto): {$pendentesErrados}\n";

// 2b. Detalhe por origem_tipo
if ($pendentesErrados > 0) {
    $detalhePendentes = DB::table('lancamentos')
        ->where('pago', 0)
        ->where('afeta_caixa', 1)
        ->selectRaw("COALESCE(origem_tipo, 'null') as ot, COUNT(*) as total")
        ->groupBy('ot')
        ->get();
    foreach ($detalhePendentes as $d) {
        echo "   - origem_tipo='{$d->ot}': {$d->total}\n";
    }
}

// 3. Pagos com afeta_caixa=0 (possivel inconsistencia)
$pagosErrados = DB::table('lancamentos')
    ->where('pago', 1)
    ->where('afeta_caixa', 0)
    ->count();
echo "3. Pagos com afeta_caixa=0: {$pagosErrados}\n";

if ($pagosErrados > 0) {
    $detalhePagos = DB::table('lancamentos')
        ->where('pago', 1)
        ->where('afeta_caixa', 0)
        ->selectRaw("COALESCE(origem_tipo, 'null') as ot, COUNT(*) as total")
        ->groupBy('ot')
        ->get();
    foreach ($detalhePagos as $d) {
        echo "   - origem_tipo='{$d->ot}': {$d->total}\n";
    }
}

// 4. Totais
$total = DB::table('lancamentos')->count();
$ac1 = DB::table('lancamentos')->where('afeta_caixa', 1)->count();
$ac0 = DB::table('lancamentos')->where('afeta_caixa', 0)->count();
$p1 = DB::table('lancamentos')->where('pago', 1)->count();
$p0 = DB::table('lancamentos')->where('pago', 0)->count();
echo "\n4. Totais:\n";
echo "   Total lancamentos: {$total}\n";
echo "   afeta_caixa=1: {$ac1} | afeta_caixa=0: {$ac0} | NULL: {$nulls}\n";
echo "   pago=1: {$p1} | pago=0: {$p0}\n";

// 5. Column definitions
$cols = DB::select("SHOW COLUMNS FROM lancamentos WHERE Field IN ('afeta_caixa', 'pago')");
echo "\n5. Column definitions:\n";
foreach ($cols as $c) {
    echo "   {$c->Field}: Type={$c->Type}, Default=" . ($c->Default ?? 'NULL') . ", Null={$c->Null}\n";
}

// 6. Migrations
echo "\n6. Migrations aplicadas:\n";
try {
    $migrations = DB::table('migrations')->pluck('migration')->all();
    echo "   Total: " . count($migrations) . "\n";
    foreach ($migrations as $m) {
        echo "   - {$m}\n";
    }
} catch (\Exception $e) {
    echo "   Tabela migrations nao existe: " . $e->getMessage() . "\n";
}

// 7. Saldo por conta
echo "\n7. Saldo calculado por conta:\n";
echo str_pad("ID", 4) . str_pad("Nome", 30) . str_pad("Saldo Inicial", 15)
    . str_pad("Receitas", 12) . str_pad("Despesas", 12)
    . str_pad("Transf In", 12) . str_pad("Transf Out", 12)
    . str_pad("Saldo Atual", 15) . "\n";
echo str_repeat("-", 112) . "\n";

$contas = DB::table('contas')->where('ativo', 1)->get();
$totalGeral = 0.0;

foreach ($contas as $c) {
    $si = (float) $c->saldo_inicial;

    $rec = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->where('tipo', 'receita')->sum('valor');

    $des = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 0)
        ->where('tipo', 'despesa')->sum('valor');

    $tIn = (float) DB::table('lancamentos')
        ->where('conta_id_destino', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)
        ->sum('valor');

    $tOut = (float) DB::table('lancamentos')
        ->where('conta_id', $c->id)->where('user_id', $c->user_id)
        ->where('pago', 1)->where('afeta_caixa', 1)->where('eh_transferencia', 1)
        ->sum('valor');

    $saldo = $si + $rec - $des + $tIn - $tOut;
    $totalGeral += $saldo;

    echo str_pad($c->id, 4)
        . str_pad(mb_substr($c->nome, 0, 28), 30)
        . str_pad(number_format($si, 2, ',', '.'), 15)
        . str_pad(number_format($rec, 2, ',', '.'), 12)
        . str_pad(number_format($des, 2, ',', '.'), 12)
        . str_pad(number_format($tIn, 2, ',', '.'), 12)
        . str_pad(number_format($tOut, 2, ',', '.'), 12)
        . str_pad(number_format($saldo, 2, ',', '.'), 15)
        . "\n";
}

echo str_repeat("-", 112) . "\n";
echo str_pad("", 4) . str_pad("TOTAL GERAL", 30) . str_pad("", 63)
    . str_pad(number_format($totalGeral, 2, ',', '.'), 15) . "\n";

echo "\nDone.\n";
