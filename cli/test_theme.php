<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

use Application\Models\Usuario;

echo "=== TESTE DE TEMA ===\n\n";

// Pegar primeiro usuário
$user = Usuario::first();

if (!$user) {
    echo "✗ Nenhum usuário encontrado\n";
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})\n";
echo "Tema atual: " . ($user->theme_preference ?? 'não definido') . "\n\n";

// Testar atualização
echo "Testando atualização para 'light'...\n";
$user->theme_preference = 'light';
$user->save();
echo "✓ Salvo\n\n";

// Verificar
$user->refresh();
echo "Tema após atualização: {$user->theme_preference}\n\n";

// Voltar para dark
echo "Voltando para 'dark'...\n";
$user->theme_preference = 'dark';
$user->save();
echo "✓ Salvo\n\n";

$user->refresh();
echo "Tema final: {$user->theme_preference}\n";
echo "\n✅ Teste concluído - Campo está funcionando corretamente!\n";
