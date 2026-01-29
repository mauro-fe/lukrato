<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;

echo "=== Corrigindo assinatura Asaas ===\n\n";

$asaasSub = AssinaturaUsuario::where('gateway', 'asaas')->first();

if ($asaasSub) {
    echo "ANTES:\n";
    echo "ID: {$asaasSub->id}\n";
    echo "Status: '{$asaasSub->status}'\n";
    echo "Renova em: {$asaasSub->renova_em}\n\n";
    
    // Atualizar status para active e renovação para daqui 30 dias
    $asaasSub->status = 'active';
    $asaasSub->renova_em = date('Y-m-d H:i:s', strtotime('+30 days'));
    $asaasSub->save();
    
    echo "DEPOIS:\n";
    echo "Status: {$asaasSub->status}\n";
    echo "Renova em: {$asaasSub->renova_em}\n\n";
    
    echo "✅ Assinatura corrigida com sucesso!\n";
} else {
    echo "❌ Nenhuma assinatura Asaas encontrada.\n";
}
