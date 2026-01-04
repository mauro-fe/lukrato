<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Atualizando Cores das Contas ===" . PHP_EOL . PHP_EOL;

$userId = 22;

// Atualizar Nubank para roxo
$nubank = \Application\Models\Conta::where('user_id', $userId)
    ->where('nome', 'Nubank')
    ->first();

if ($nubank) {
    $nubank->cor = '#8A05BE'; // Roxo do Nubank
    $nubank->save();
    echo "✅ Nubank atualizado para roxo (#8A05BE)" . PHP_EOL;
} else {
    echo "❌ Conta Nubank não encontrada" . PHP_EOL;
}

// Atualizar PicPay para verde
$picpay = \Application\Models\Conta::where('user_id', $userId)
    ->where('nome', 'PicPay')
    ->first();

if ($picpay) {
    $picpay->cor = '#21C25E'; // Verde do PicPay
    $picpay->save();
    echo "✅ PicPay atualizado para verde (#21C25E)" . PHP_EOL;
} else {
    echo "❌ Conta PicPay não encontrada" . PHP_EOL;
}

echo PHP_EOL . "=== Verificando Resultado ===" . PHP_EOL . PHP_EOL;

$contas = \Application\Models\Conta::where('user_id', $userId)->get();

foreach ($contas as $conta) {
    echo "Conta: " . ($conta->nome ?? 'N/A') . " - Cor: " . ($conta->cor ?? 'NULL') . PHP_EOL;
}
