<?php
require_once __DIR__ . '/../bootstrap.php';

$cartoes = Application\Models\CartaoCredito::where('user_id', 1)->get();

echo "Verificando cartões...\n\n";

foreach ($cartoes as $c) {
    echo "Cartão ID {$c->id}: {$c->nome_cartao}\n";
    echo "  - Vencimento: {$c->dia_vencimento}\n";
    echo "  - Ativo: " . ($c->ativo ? 'Sim' : 'Não') . "\n";
    echo "  - User ID: {$c->user_id}\n\n";
}
