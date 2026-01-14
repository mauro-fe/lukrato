<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

$cartoes = CartaoCredito::where('user_id', 1)->get();

echo "Total de cartões: {$cartoes->count()}\n\n";

foreach ($cartoes as $c) {
    echo "ID: {$c->id} - {$c->nome_cartao}\n";
    echo "  Arquivado: " . ($c->arquivado ? 'Sim' : 'Não') . "\n";
    echo "  Ativo: " . ($c->ativo ? 'Sim' : 'Não') . "\n";
    echo "---\n";
}
