<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Carbon\Carbon;

$userId = 1;

echo "🔍 VERIFICANDO ASSINATURAS DO USUÁRIO ID: {$userId}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário não encontrado!\n";
    exit(1);
}

echo "👤 {$user->nome}\n";
echo "📧 {$user->email}\n\n";

// Buscar TODAS as assinaturas
$todasAssinaturas = AssinaturaUsuario::where('user_id', $userId)
    ->orderByDesc('id')
    ->with('plano')
    ->get();

echo "📋 TODAS AS ASSINATURAS:\n\n";
foreach ($todasAssinaturas as $ass) {
    echo "   ID: {$ass->id}\n";
    echo "   Plano: " . ($ass->plano->nome ?? 'N/A') . "\n";
    echo "   Status: {$ass->status}\n";
    echo "   Gateway: {$ass->gateway}\n";
    echo "   Criada em: {$ass->created_at}\n";
    echo "   Renova em: {$ass->renova_em}\n";
    if ($ass->cancelada_em) {
        echo "   Cancelada em: {$ass->cancelada_em}\n";
    }
    echo "   ────────────────────────────────────────\n";
}

echo "\n🔍 Verificando isPro()...\n";
$user = Usuario::find($userId); // Recarregar
$isPro = $user->isPro();
echo "isPro(): " . ($isPro ? '✅ SIM (PRO)' : '❌ NÃO (FREE)') . "\n";
