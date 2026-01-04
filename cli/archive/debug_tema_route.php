<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

echo "=== DEBUG ROTA API TEMA ===\n\n";

echo "BASE_URL: " . BASE_URL . "\n";
echo "URL esperada: " . BASE_URL . "api/perfil/tema\n\n";

// Verificar todas as rotas registradas
use Application\Core\Router;

echo "Verificando rotas registradas...\n\n";

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/lukrato/public/api/perfil/tema';

echo "Rota testada: {$_SERVER['REQUEST_URI']}\n";

// Teste de autenticação
use Application\Models\Usuario;

$user = Usuario::first();
if ($user) {
    echo "\nUsuário de teste: {$user->nome} (ID: {$user->id})\n";
    echo "Tema atual: " . ($user->theme_preference ?? 'NULL') . "\n";
}
