<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$rows = DB::table('assinaturas_usuarios')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "=== ÚLTIMAS ASSINATURAS ===\n\n";

foreach ($rows as $r) {
    echo "ID: {$r->id}\n";
    echo "  User ID: {$r->id_usuario}\n";
    echo "  Status: {$r->status}\n";
    echo "  Gateway: " . ($r->gateway ?? 'NULL') . "\n";
    echo "  Payment ID: " . ($r->external_payment_id ?? 'NULL') . "\n";
    echo "  Subscription ID: " . ($r->external_subscription_id ?? 'NULL') . "\n";
    echo "  Billing Type: " . ($r->billing_type ?? 'NULL') . "\n";
    echo "  Criado: " . ($r->created_at ?? 'NULL') . "\n";
    echo "---\n";
}
