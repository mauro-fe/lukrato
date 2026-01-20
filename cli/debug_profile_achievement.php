<?php

/**
 * Debug: Verificar conquista de perfil completo
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Services\AchievementService;

$userId = $argv[1] ?? 1;
$user = Usuario::find($userId);

if (!$user) {
    echo "❌ Usuário não encontrado!\n";
    exit(1);
}

echo "=== Dados do Usuário #{$user->id} ===\n";
echo "Nome: '{$user->nome}' (length: " . strlen(trim((string)$user->nome)) . ")\n";
echo "Email: '{$user->email}'\n";
echo "Username: '{$user->username}' (length: " . strlen(trim((string)$user->username)) . ")\n";
echo "Data Nascimento: '{$user->data_nascimento}'\n";
echo "ID Sexo: '{$user->id_sexo}'\n";

echo "\n=== Verificação Manual ===\n";
$nome = trim((string)$user->nome);
$email = trim((string)$user->email);
$username = trim((string)$user->username);
$dataNascimento = $user->data_nascimento;
$idSexo = $user->id_sexo;

echo "✓ Nome >= 3 chars: " . (strlen($nome) >= 3 ? "SIM" : "NÃO ({$nome})") . "\n";
echo "✓ Email válido: " . (filter_var($email, FILTER_VALIDATE_EMAIL) ? "SIM" : "NÃO") . "\n";
echo "✓ Username >= 3 chars: " . (strlen($username) >= 3 ? "SIM" : "NÃO ({$username})") . "\n";
echo "✓ Data nascimento: " . (!empty($dataNascimento) ? "SIM" : "NÃO") . "\n";
echo "✓ Sexo selecionado: " . (!empty($idSexo) && $idSexo >= 1 ? "SIM" : "NÃO ({$idSexo})") . "\n";

echo "\n=== Tentando Desbloquear ===\n";
$service = new AchievementService();
$result = $service->checkAndUnlockAchievements($user->id, 'profile_debug');

if (empty($result)) {
    echo "Nenhuma conquista desbloqueada.\n";

    // Verificar se já foi desbloqueada antes
    $existing = \Application\Models\UserAchievement::where('user_id', $user->id)
        ->whereHas('achievement', function ($q) {
            $q->where('code', 'PROFILE_COMPLETE');
        })
        ->first();

    if ($existing) {
        echo "⚠️  Conquista PROFILE_COMPLETE já foi desbloqueada em: {$existing->unlocked_at}\n";
    }
} else {
    echo "🎉 Conquistas desbloqueadas:\n";
    foreach ($result as $ach) {
        echo "  - {$ach['name']} (+{$ach['points_reward']} pts)\n";
    }
}
