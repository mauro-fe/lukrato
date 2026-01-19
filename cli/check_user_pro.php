<?php

require_once __DIR__ . '/../bootstrap.php';

session_start();

$userId = $_SESSION['user_id'] ?? null;

echo "🔍 Verificando usuário logado:\n\n";

if (!$userId) {
    echo "❌ Nenhum usuário logado!\n";
    exit;
}

$user = Application\Models\Usuario::find($userId);

echo "👤 User ID: {$userId}\n";
echo "👤 Nome: {$user->nome}\n";
echo "💎 isPro(): " . ($user->isPro() ? 'SIM ✅' : 'NÃO ❌') . "\n\n";

$assinatura = $user->assinaturaAtiva;

if ($assinatura) {
    echo "📋 Assinatura:\n";
    echo "   Status: {$assinatura->status}\n";
    echo "   Renova em: {$assinatura->renova_em}\n";
    echo "   Criado em: {$assinatura->created_at}\n";
} else {
    echo "❌ Sem assinatura ativa\n";
}

echo "\n✅ Verificação completa!\n";
