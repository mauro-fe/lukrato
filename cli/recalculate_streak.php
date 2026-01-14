<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Services\StreakService;
use Application\Models\Usuario;

$userId = $argv[1] ?? 1;

$user = Usuario::find($userId);
if (!$user) {
    echo "Usuário não encontrado!\n";
    exit(1);
}

echo "Recalculando streak para: {$user->nome} (ID: {$user->id})\n\n";

$streakService = new StreakService();
$result = $streakService->recalculateStreak($userId);

print_r($result);

echo "\n✅ Streak recalculado com sucesso!\n";
