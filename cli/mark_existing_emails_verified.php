<?php

/**
 * Script para marcar emails de usuários existentes como verificados
 * 
 * Marca como verificados:
 * 1. Usuários que fizeram login via Google (têm google_id)
 * 2. Usuários que já fizeram pelo menos um login (opcional)
 * 
 * Uso: php cli/mark_existing_emails_verified.php [--all]
 *   --all: Marca todos os usuários existentes como verificados
 *   Sem --all: Marca apenas usuários com Google ID
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;

echo "=== Marcando emails de usuários existentes como verificados ===\n\n";

$markAll = in_array('--all', $argv);

if ($markAll) {
    echo "Modo: Marcar TODOS os usuários existentes\n";
    $query = Usuario::whereNull('email_verified_at');
} else {
    echo "Modo: Marcar apenas usuários com Google ID\n";
    $query = Usuario::whereNull('email_verified_at')
        ->whereNotNull('google_id')
        ->where('google_id', '!=', '');
}

$count = $query->count();
echo "Usuários a serem atualizados: {$count}\n\n";

if ($count === 0) {
    echo "Nenhum usuário para atualizar.\n";
    exit(0);
}

echo "Deseja continuar? (s/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 's') {
    echo "Operação cancelada.\n";
    exit(0);
}

$updated = 0;
$errors = 0;

$query->chunk(100, function ($users) use (&$updated, &$errors) {
    foreach ($users as $user) {
        try {
            $user->email_verified_at = now();
            $user->email_verification_token = null;
            $user->save();
            $updated++;
            echo "✓ {$user->email}\n";
        } catch (\Exception $e) {
            $errors++;
            echo "✗ {$user->email}: {$e->getMessage()}\n";
        }
    }
});

echo "\n=== Resultado ===\n";
echo "Atualizados: {$updated}\n";
echo "Erros: {$errors}\n";
