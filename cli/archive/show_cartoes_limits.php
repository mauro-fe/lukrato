<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

$userId = getenv('USER_ID') ?: 1;
$cartoes = CartaoCredito::forUser($userId)->get();

if ($cartoes->isEmpty()) {
    echo "Nenhum cartão encontrado para o usuário {$userId}\n";
    exit;
}

echo "Cartões (user={$userId}):\n";
foreach ($cartoes as $c) {
    printf(
        "- id:%d nome:%s limite_total:%s limite_disponivel:%s limite_utilizado:%s\n",
        $c->id,
        $c->nome_cartao,
        number_format($c->limite_total, 2, ',', '.'),
        number_format($c->limite_disponivel, 2, ',', '.'),
        number_format($c->limite_utilizado, 2, ',', '.')
    );
}
