<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;

$email = $argv[1] ?? 'devmaurofelix@gmail.com';
$action = $argv[2] ?? 'unverify'; // unverify ou verify

$user = Usuario::where('email', $email)->first();

if (!$user) {
    echo "Usuário não encontrado: {$email}\n";
    exit(1);
}

if ($action === 'verify') {
    $user->email_verified_at = now();
    $user->email_verification_token = null;
    $user->save();
    echo "✓ Email marcado como VERIFICADO: {$email}\n";
} else {
    $user->email_verified_at = null;
    $user->email_verification_token = bin2hex(random_bytes(32));
    $user->email_verification_sent_at = now();
    $user->save();
    echo "✓ Email marcado como NÃO VERIFICADO: {$email}\n";
}
