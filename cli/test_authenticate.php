<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    echo "Uso: php cli/test_authenticate.php <email> <senha>\n";
    exit(1);
}

$user = Usuario::authenticate($email, $password);

if ($user) {
    echo "Usuário autenticado!\n";
    echo "ID: " . $user->id . "\n";
    echo "Nome: " . $user->nome . "\n";
    echo "Email: " . $user->email . "\n";
} else {
    echo "Falha na autenticação.\n";
}
