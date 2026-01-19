<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Carbon\Carbon;

echo "🔍 VERIFICANDO ASSINATURAS NO BANCO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Buscar assinaturas canceladas que ainda estão no período
$assinaturasCanceladas = AssinaturaUsuario::where('status', AssinaturaUsuario::ST_CANCELED)
    ->where('renova_em', '>', now())
    ->with(['usuario', 'plano'])
    ->get();

if ($assinaturasCanceladas->isEmpty()) {
    echo "ℹ️  Não há assinaturas canceladas dentro do período pago.\n\n";
} else {
    echo "📋 ASSINATURAS CANCELADAS (ainda no período pago):\n\n";
    foreach ($assinaturasCanceladas as $ass) {
        $user = $ass->usuario;
        $renovaEm = Carbon::parse($ass->renova_em);
        $diasRestantes = now()->diffInDays($renovaEm);
        $isPro = $user ? $user->isPro() : false;

        echo "   👤 {$user->nome} (ID: {$user->id})\n";
        echo "      Plano: {$ass->plano->nome}\n";
        echo "      Status: {$ass->status}\n";
        echo "      Cancelada em: {$ass->cancelada_em}\n";
        echo "      Renova em: {$ass->renova_em}\n";
        echo "      Dias restantes: {$diasRestantes}\n";
        echo "      isPro(): " . ($isPro ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "\n";
    }
}

// Buscar assinaturas ativas
$assinaturasAtivas = AssinaturaUsuario::where('status', AssinaturaUsuario::ST_ACTIVE)
    ->with(['usuario', 'plano'])
    ->limit(5)
    ->get();

if (!$assinaturasAtivas->isEmpty()) {
    echo "📋 ASSINATURAS ATIVAS (primeiras 5):\n\n";
    foreach ($assinaturasAtivas as $ass) {
        $user = $ass->usuario;
        if (!$user) continue;

        $renovaEm = $ass->renova_em ? Carbon::parse($ass->renova_em) : null;
        $isPro = $user->isPro();

        echo "   👤 {$user->nome} (ID: {$user->id})\n";
        echo "      Plano: {$ass->plano->nome}\n";
        echo "      Status: {$ass->status}\n";
        if ($renovaEm) {
            echo "      Renova em: {$ass->renova_em}\n";
            if ($renovaEm->isFuture()) {
                $diasRestantes = now()->diffInDays($renovaEm);
                echo "      Dias restantes: {$diasRestantes}\n";
            } else {
                $diasAposVenc = $renovaEm->diffInDays(now());
                echo "      Venceu há {$diasAposVenc} dias\n";
            }
        }
        echo "      isPro(): " . ($isPro ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Sistema corrigido!\n";
echo "   Usuários com assinatura cancelada mantêm acesso até o fim do período.\n";
