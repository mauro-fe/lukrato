<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Verificando Cartões (sem filtro ativo) ===" . PHP_EOL . PHP_EOL;

$userId = 1;

$cartoes = \Application\Models\CartaoCredito::where('user_id', $userId)->get();

echo "Total de cartões (incluindo inativos): " . $cartoes->count() . PHP_EOL . PHP_EOL;

foreach ($cartoes as $cartao) {
    echo "ID: {$cartao->id}" . PHP_EOL;
    echo "Nome: " . ($cartao->nome_cartao ?? 'N/A') . PHP_EOL;
    echo "Bandeira: " . ($cartao->bandeira ?? 'N/A') . PHP_EOL;
    echo "Limite Total: " . ($cartao->limite_total ?? 0) . PHP_EOL;
    echo "Ativo: " . var_export($cartao->ativo, true) . " (tipo: " . gettype($cartao->ativo) . ")" . PHP_EOL;
    echo "---" . PHP_EOL;
}

// Verificar estrutura da tabela
echo PHP_EOL . "=== Estrutura da Tabela ===" . PHP_EOL;
$columns = \Illuminate\Support\Facades\DB::select("DESCRIBE cartoes_credito");
foreach ($columns as $col) {
    echo "{$col->Field} - {$col->Type} - Default: {$col->Default}" . PHP_EOL;
}
