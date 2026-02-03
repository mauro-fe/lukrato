<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Application\Models\CartaoCredito;

echo "=== Debug Pagamento Fatura ===\n\n";

// Buscar lançamento de pagamento de fatura
$pagamento = Lancamento::where('descricao', 'LIKE', 'Pagamento Fatura%')->first();

if ($pagamento) {
    echo "Lançamento encontrado:\n";
    echo "  ID: {$pagamento->id}\n";
    echo "  Descrição: {$pagamento->descricao}\n";
    echo "  Observação: {$pagamento->observacao}\n";
    echo "  origem_tipo: '{$pagamento->origem_tipo}'\n";
    echo "  cartao_credito_id: {$pagamento->cartao_credito_id}\n";
    echo "  user_id: {$pagamento->user_id}\n";

    // Testar regex
    if (preg_match('/Fatura (\d{2})\/(\d{4})/', $pagamento->observacao, $matches)) {
        echo "\n  Regex MATCH: Mês={$matches[1]}, Ano={$matches[2]}\n";
    } else {
        echo "\n  Regex NÃO encontrou na observação\n";
        // Tentar na descrição
        if (preg_match('/(\w{3})\/(\d{4})/', $pagamento->descricao, $matches)) {
            echo "  Encontrado na descrição: {$matches[1]}/{$matches[2]}\n";
        }
    }
} else {
    echo "Nenhum lançamento de pagamento de fatura encontrado.\n";
}
