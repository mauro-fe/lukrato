<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "=== Verificando Lançamentos Agendados do Usuário 26 ===" . PHP_EOL . PHP_EOL;

// Verificar se há lançamentos com flag agendado
$lancamentos = Lancamento::where('user_id', 26)
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "Últimos 10 lançamentos:" . PHP_EOL;
foreach ($lancamentos as $l) {
    $pago = isset($l->pago) ? ($l->pago ? 'S' : 'N') : '-';
    $agendado = isset($l->agendado) ? ($l->agendado ? 'S' : 'N') : '-';
    echo "ID: {$l->id} - {$l->descricao} - Pago: {$pago} - Agendado: {$agendado} - Data: {$l->data}" . PHP_EOL;
}
