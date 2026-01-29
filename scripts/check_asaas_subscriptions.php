<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;

echo "=== Detalhes das assinaturas Asaas ===\n\n";

$asaasSubscriptions = AssinaturaUsuario::where('gateway', 'asaas')->get();

foreach ($asaasSubscriptions as $sub) {
    echo "ID: {$sub->id}\n";
    echo "User ID: {$sub->user_id}\n";
    echo "Status: {$sub->status}\n";
    echo "Renova em: {$sub->renova_em}\n";
    echo "Criado em: {$sub->created_at}\n";
    echo "Plano ID: {$sub->plano_id}\n";
    echo "---\n\n";
}

echo "Data atual: " . date('Y-m-d H:i:s') . "\n";
