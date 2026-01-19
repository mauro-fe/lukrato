<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Achievement;

echo "📊 Plan Types no banco de dados:\n\n";

$counts = Achievement::selectRaw('plan_type, COUNT(*) as total')
    ->groupBy('plan_type')
    ->get();

foreach ($counts as $row) {
    $planType = $row->plan_type ?? 'NULL';
    echo sprintf("  %-15s = %d conquistas\n", $planType, $row->total);
}

echo "\n📝 Exemplos (primeiras 10 conquistas):\n\n";

Achievement::limit(10)->get()->each(function ($achievement) {
    $planType = $achievement->plan_type ?? 'NULL';
    echo sprintf(
        "  [%-25s] plan_type = %-10s (active = %d)\n",
        $achievement->code,
        $planType,
        $achievement->active ? 1 : 0
    );
});

echo "\n✅ Verificação completa!\n";
