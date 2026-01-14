<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

echo "=== Debug: Cartões Arquivados ===\n\n";

$cartoesArquivados = CartaoCredito::where('user_id', 1)
    ->where('arquivado', true)
    ->get();

if ($cartoesArquivados->isEmpty()) {
    echo "Nenhum cartão arquivado encontrado.\n";
} else {
    foreach ($cartoesArquivados as $cartao) {
        $totalLancamentos = $cartao->lancamentos()->count();
        echo "Cartão ID: {$cartao->id}\n";
        echo "Nome: {$cartao->nome_cartao}\n";
        echo "Lançamentos vinculados: {$totalLancamentos}\n";
        echo "---\n";
    }
}
