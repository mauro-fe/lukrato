<?php
require 'bootstrap.php';

use Application\Models\Lancamento;

// Buscar todos os lançamentos de pagamento de fatura
$lancs = Lancamento::where('origem_tipo', 'pagamento_fatura')->get();

if ($lancs->isEmpty()) {
    echo "Nenhum lançamento de pagamento de fatura encontrado\n";
} else {
    foreach ($lancs as $l) {
        echo "ID: {$l->id}\n";
        echo "  Descricao: {$l->descricao}\n";
        echo "  Observacao: {$l->observacao}\n";
        echo "  cartao_credito_id: {$l->cartao_credito_id}\n";
        echo "  data: {$l->data}\n";
        echo "\n";
    }
}

// Buscar lançamentos que parecem ser de pagamento de fatura pelo nome
echo "=== Buscando por descrição 'Pagamento Fatura%' ===\n";
$lancs2 = Lancamento::where('descricao', 'LIKE', 'Pagamento Fatura%')->get();

foreach ($lancs2 as $l) {
    echo "ID: {$l->id}\n";
    echo "  Descricao: {$l->descricao}\n";
    echo "  cartao_credito_id: {$l->cartao_credito_id}\n";
    echo "\n";
}
