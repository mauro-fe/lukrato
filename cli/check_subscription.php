<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;
use Application\Models\Usuario;

echo "=== Verificando Assinaturas ===" . PHP_EOL . PHP_EOL;

$userId = 26;

$user = Usuario::find($userId);
if (!$user) {
    echo "Usuário não encontrado" . PHP_EOL;
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})" . PHP_EOL;
echo "isPro: " . ($user->isPro() ? 'Sim' : 'Não') . PHP_EOL . PHP_EOL;

$assinaturas = AssinaturaUsuario::where('user_id', $userId)->get();

if ($assinaturas->isEmpty()) {
    echo "Nenhuma assinatura encontrada para este usuário" . PHP_EOL;
} else {
    echo "Assinaturas encontradas: " . $assinaturas->count() . PHP_EOL . PHP_EOL;

    foreach ($assinaturas as $a) {
        echo "ID: {$a->id}" . PHP_EOL;
        echo "  Status: {$a->status}" . PHP_EOL;
        echo "  Gateway: {$a->gateway}" . PHP_EOL;
        echo "  External Subscription ID: " . ($a->external_subscription_id ?? 'NULL') . PHP_EOL;
        echo "  Renova em: " . ($a->renova_em ?? 'NULL') . PHP_EOL;
        echo "  Cancelada em: " . ($a->cancelada_em ?? 'NULL') . PHP_EOL;
        echo PHP_EOL;
    }
}
