<?php

/**
 * Seed categorias padrão para o primeiro usuário do sistema.
 *
 * Delega para AuthService::criarCategoriasPadrao() para garantir
 * consistência com o fluxo de registro (mesmas categorias + subcategorias).
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

use Application\Models\Categoria;
use Application\Models\Usuario;
use Application\Services\Auth\AuthService;

echo "=== SEED CATEGORIAS PADRÃO (PRIMEIRO USUÁRIO) ===\n\n";

// Pegar primeiro usuário
$user = Usuario::first();
if (!$user) {
    echo "✗ Nenhum usuário encontrado\n";
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})\n\n";

// Verificar se já possui categorias seeded
$existingSeeded = Categoria::where('user_id', $user->id)
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
    Categoria::where('user_id', $user->id)->where('is_seeded', true)->delete();
    echo "   Categorias seeded anteriores removidas.\n\n";
}

// Usar reflexão para chamar o método privado
$authService = new AuthService();
$method = new ReflectionMethod($authService, 'criarCategoriasPadrao');
$method->setAccessible(true);
$method->invoke($authService, $user->id);

// Resumo
$rootCount = Categoria::where('user_id', $user->id)->whereNull('parent_id')->count();
$subCount = Categoria::where('user_id', $user->id)->whereNotNull('parent_id')->count();

echo "\n✅ CONCLUÍDO!\n";
echo "   Categorias raiz: {$rootCount}\n";
echo "   Subcategorias: {$subCount}\n";
echo "   Total: " . ($rootCount + $subCount) . "\n";
