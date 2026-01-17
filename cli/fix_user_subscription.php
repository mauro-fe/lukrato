<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;
use Application\Models\Usuario;

echo "=== Corrigindo Assinatura do Usuário 26 ===" . PHP_EOL . PHP_EOL;

$userId = 26;

$user = Usuario::find($userId);
if (!$user) {
    echo "Usuário não encontrado" . PHP_EOL;
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})" . PHP_EOL;
echo "isPro (antes): " . ($user->isPro() ? 'Sim' : 'Não') . PHP_EOL . PHP_EOL;

// Buscar assinatura
$assinatura = AssinaturaUsuario::where('user_id', $userId)->first();

if ($assinatura) {
    echo "Assinatura encontrada:" . PHP_EOL;
    echo "  ID: {$assinatura->id}" . PHP_EOL;
    echo "  Status atual: {$assinatura->status}" . PHP_EOL;
    echo "  Plano ID: {$assinatura->plano_id}" . PHP_EOL;
    echo "  Gateway: {$assinatura->gateway}" . PHP_EOL;

    // Corrigir status
    $assinatura->status = 'active';
    $assinatura->cancelada_em = null;
    $assinatura->save();

    echo PHP_EOL . "=== Status atualizado para 'active' ===" . PHP_EOL;
} else {
    echo "Nenhuma assinatura encontrada" . PHP_EOL;
}

// Verificar novamente
echo PHP_EOL . "isPro (depois): " . ($user->isPro() ? 'Sim' : 'Não') . PHP_EOL;
