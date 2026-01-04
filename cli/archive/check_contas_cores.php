<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Verificando Cores das Contas ===" . PHP_EOL . PHP_EOL;

$userId = 22;

// Buscar contas
$contas = \Application\Models\Conta::where('user_id', $userId)->get();

echo "Total de contas: " . $contas->count() . PHP_EOL . PHP_EOL;

foreach ($contas as $conta) {
    echo "ID: {$conta->id}" . PHP_EOL;
    echo "Nome: " . ($conta->nome ?? 'N/A') . PHP_EOL;
    echo "Apelido: " . ($conta->apelido ?? 'N/A') . PHP_EOL;
    echo "Cor: " . ($conta->cor ?? 'NULL') . PHP_EOL;
    echo "---" . PHP_EOL . PHP_EOL;
}

// Verificar cartões e suas contas
echo PHP_EOL . "=== Cartões e suas Contas ===" . PHP_EOL . PHP_EOL;

$cartoes = \Application\Models\CartaoCredito::where('user_id', $userId)->get();

foreach ($cartoes as $cartao) {
    echo "Cartão: {$cartao->nome_cartao}" . PHP_EOL;
    echo "Conta ID: {$cartao->conta_id}" . PHP_EOL;

    $conta = $cartao->conta;
    if ($conta) {
        echo "Conta Nome: " . ($conta->nome ?? $conta->apelido ?? 'N/A') . PHP_EOL;
        echo "Conta Cor: " . ($conta->cor ?? 'NULL') . PHP_EOL;
    } else {
        echo "Conta: NÃO ENCONTRADA" . PHP_EOL;
    }

    echo "---" . PHP_EOL . PHP_EOL;
}
