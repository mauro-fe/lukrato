<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;

echo "=== Assinaturas Ativas por Gateway ===\n\n";

$gateways = AssinaturaUsuario::selectRaw('gateway, COUNT(*) as count')
    ->where('status', 'active')
    ->where('renova_em', '>', date('Y-m-d H:i:s'))
    ->groupBy('gateway')
    ->get();

foreach ($gateways as $g) {
    echo $g->gateway . ': ' . $g->count . "\n";
}

echo "\n=== Todas as assinaturas por Gateway (incluindo inativas) ===\n\n";

$allGateways = AssinaturaUsuario::selectRaw('gateway, COUNT(*) as count')
    ->groupBy('gateway')
    ->get();

foreach ($allGateways as $g) {
    echo $g->gateway . ': ' . $g->count . "\n";
}
