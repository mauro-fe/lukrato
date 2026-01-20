<?php

/**
 * Teste da funcionalidade de salvar dados do checkout no perfil
 * Simula o comportamento do PremiumController após um checkout bem-sucedido
 * 
 * Usage: php cli/test_checkout_profile_save.php [user_id]
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Services\AchievementService;
use Application\Providers\PerfilControllerFactory;
use Illuminate\Database\Capsule\Manager as DB;

$userId = $argv[1] ?? 1;

echo "🧪 Teste de Salvamento de Dados do Checkout no Perfil\n";
echo str_repeat("=", 60) . "\n\n";

// Buscar usuário
$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário ID {$userId} não encontrado\n";
    exit(1);
}

echo "👤 Usuário: {$user->nome} (ID: {$user->id})\n";
echo "📧 Email: {$user->email}\n\n";

// Simular dados do checkout (como viriam do DTO)
$checkoutData = [
    'cpf' => '12345678901',
    'phone' => '11987654321',
    'cep' => '01310100',
];

echo "📦 Dados do Checkout:\n";
echo "   CPF: {$checkoutData['cpf']}\n";
echo "   Telefone: {$checkoutData['phone']}\n";
echo "   CEP: {$checkoutData['cep']}\n\n";

// Verificar dados atuais do perfil
$perfilService = PerfilControllerFactory::createService();

echo "📋 Estado atual do perfil:\n";

// Verificar CPF
$docAtual = DB::table('documentos')
    ->where('id_usuario', $userId)
    ->where('id_tipo', 1)
    ->value('numero');
echo "   CPF atual: " . ($docAtual ?: '(vazio)') . "\n";

$phoneAtual = DB::table('telefones')
    ->where('id_usuario', $userId)
    ->value('numero');
echo "   Telefone atual: " . ($phoneAtual ?: '(vazio)') . "\n";

$cepAtual = DB::table('enderecos')
    ->where('user_id', $userId)
    ->value('cep');
echo "   CEP atual: " . ($cepAtual ?: '(vazio)') . "\n\n";

// Perguntar se quer prosseguir
echo "⚠️  Deseja simular o salvamento dos dados? (s/n): ";
$input = trim(fgets(STDIN));

if (strtolower($input) !== 's') {
    echo "❌ Cancelado pelo usuário\n";
    exit(0);
}

echo "\n🔄 Salvando dados do checkout no perfil...\n";

try {
    // Chamar o método que seria chamado pelo PremiumController
    $perfilService->salvarDadosCheckout($userId, $checkoutData);

    echo "✅ Dados salvos com sucesso!\n\n";

    // Verificar conquistas
    echo "🏆 Verificando conquistas...\n";
    $achievementService = new AchievementService();
    $unlocked = $achievementService->checkAndUnlockAchievements($userId, 'checkout_profile_save');

    if (!empty($unlocked)) {
        echo "🎉 Conquistas desbloqueadas:\n";
        foreach ($unlocked as $achievement) {
            echo "   - {$achievement['name']} (+{$achievement['points']} pts)\n";
        }
    } else {
        echo "   Nenhuma nova conquista desbloqueada\n";
    }

    // Verificar estado após salvamento
    echo "\n📋 Estado após salvamento:\n";

    $docNovo = DB::table('documentos')
        ->where('id_usuario', $userId)
        ->where('id_tipo', 1)
        ->value('numero');
    echo "   CPF: " . ($docNovo ?: '(vazio)') . ($docNovo !== $docAtual ? " ← ATUALIZADO" : "") . "\n";

    $phoneNovo = DB::table('telefones')
        ->where('id_usuario', $userId)
        ->value('numero');
    echo "   Telefone: " . ($phoneNovo ?: '(vazio)') . ($phoneNovo !== $phoneAtual ? " ← ATUALIZADO" : "") . "\n";

    $cepNovo = DB::table('enderecos')
        ->where('user_id', $userId)
        ->value('cep');
    echo "   CEP: " . ($cepNovo ?: '(vazio)') . ($cepNovo !== $cepAtual ? " ← ATUALIZADO" : "") . "\n";
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Teste concluído com sucesso!\n";
