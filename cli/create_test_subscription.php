<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Carbon\Carbon;

echo "🔧 CRIANDO ASSINATURA DE TESTE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$userId = 1;

// Buscar usuário
$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário ID {$userId} não encontrado!\n";
    exit(1);
}

echo "👤 Usuário: {$user->nome}\n";
echo "📧 Email: {$user->email}\n\n";

// Buscar plano Pro
$planoPro = Plano::where('code', 'pro')->first();
if (!$planoPro) {
    echo "❌ Plano Pro não encontrado no banco!\n";
    exit(1);
}

echo "💎 Plano: {$planoPro->nome} (ID: {$planoPro->id})\n";
echo "💰 Valor: R$ {$planoPro->preco}\n\n";

// Verificar se já tem assinatura ativa
$assinaturaExistente = AssinaturaUsuario::where('user_id', $userId)
    ->where('status', AssinaturaUsuario::ST_ACTIVE)
    ->first();

if ($assinaturaExistente) {
    echo "⚠️  Já existe uma assinatura ativa!\n";
    echo "   Status: {$assinaturaExistente->status}\n";
    echo "   Renova em: {$assinaturaExistente->renova_em}\n\n";
    echo "🔄 Atualizando para nova data...\n";

    $assinaturaExistente->plano_id = $planoPro->id;
    $assinaturaExistente->status = AssinaturaUsuario::ST_ACTIVE;
    $assinaturaExistente->renova_em = Carbon::now()->addMonth();
    $assinaturaExistente->cancelada_em = null;
    $assinaturaExistente->save();

    $assinatura = $assinaturaExistente;
} else {
    // Criar nova assinatura
    echo "✨ Criando nova assinatura...\n";

    $assinatura = AssinaturaUsuario::create([
        'user_id' => $userId,
        'plano_id' => $planoPro->id,
        'gateway' => 'manual',
        'external_customer_id' => 'test_customer_' . $userId,
        'external_subscription_id' => 'test_sub_' . time(),
        'status' => AssinaturaUsuario::ST_ACTIVE,
        'renova_em' => Carbon::now()->addMonth(),
        'cancelada_em' => null
    ]);
}

echo "\n✅ ASSINATURA CRIADA/ATUALIZADA COM SUCESSO!\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 DETALHES DA ASSINATURA:\n\n";
echo "   ID: {$assinatura->id}\n";
echo "   Status: {$assinatura->status}\n";
echo "   Gateway: {$assinatura->gateway}\n";
echo "   Criada em: {$assinatura->created_at}\n";
echo "   Renova em: {$assinatura->renova_em}\n";
echo "   Dias até renovação: " . Carbon::now()->diffInDays($assinatura->renova_em) . "\n";
echo "\n";

// Verificar se isPro() está funcionando
$user = Usuario::find($userId); // Recarregar
$isPro = $user->isPro();
echo "🔍 Verificação isPro(): " . ($isPro ? '✅ SIM (PRO)' : '❌ NÃO (FREE)') . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 PRÓXIMOS PASSOS PARA TESTE:\n\n";
echo "1. Acesse o sistema com o usuário ID 1\n";
echo "2. Vá em Billing/Planos\n";
echo "3. Cancele a assinatura\n";
echo "4. Verifique que continua com acesso PRO\n";
echo "5. Execute: php cli/check_subscriptions.php\n";
