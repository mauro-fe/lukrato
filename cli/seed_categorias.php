<?php

/**
 * Seed categorias padrão para um usuário específico.
 *
 * Uso: php cli/seed_categorias.php [user_id]
 *   - Se user_id não for informado, usa o ID 23 como padrão.
 *
 * Delega para AuthService::criarCategoriasPadrao() para garantir
 * consistência com o fluxo de registro (mesmas categorias + subcategorias).
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Categoria;
use Application\Services\Auth\AuthService;

$userId = isset($argv[1]) ? (int) $argv[1] : 23;

$user = Usuario::find($userId);
if (!$user) {
    echo "✗ Usuário ID {$userId} não encontrado.\n";
    exit(1);
}

echo "=== SEED CATEGORIAS PADRÃO ===\n";
echo "Usuário: {$user->nome} (ID: {$user->id})\n\n";

// Verificar se já possui categorias seeded
$existingSeeded = Categoria::where('user_id', $userId)
    ->where('is_seeded', true)
    ->whereNull('parent_id')
    ->count();

if ($existingSeeded > 0) {
    echo "⚠️  Usuário já possui {$existingSeeded} categorias padrão. Pulando.\n";
    echo "   Use --force para recriar.\n";

    if (!in_array('--force', $argv ?? [], true)) {
        exit(0);
    }

    echo "\n🔄 Flag --force detectada, recriando...\n";
    Categoria::where('user_id', $userId)->where('is_seeded', true)->delete();
    echo "   Categorias seeded anteriores removidas.\n\n";
}

// Usar reflexão para chamar o método privado (ou torná-lo acessível)
$authService = new AuthService();
$method = new ReflectionMethod($authService, 'criarCategoriasPadrao');
$method->setAccessible(true);
$method->invoke($authService, $userId);

// Resumo
$rootCount = Categoria::where('user_id', $userId)->whereNull('parent_id')->count();
$subCount = Categoria::where('user_id', $userId)->whereNotNull('parent_id')->count();

echo "\n✅ Concluído!\n";
echo "   Categorias raiz: {$rootCount}\n";
echo "   Subcategorias: {$subCount}\n";
echo "   Total: " . ($rootCount + $subCount) . "\n";
