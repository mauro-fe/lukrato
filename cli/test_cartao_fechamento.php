<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

echo "=== CONFIGURAÇÃO DE CARTÕES ===\n\n";

$cartoes = CartaoCredito::select('id', 'nome_cartao', 'dia_vencimento', 'dia_fechamento', 'user_id')
    ->get();

foreach ($cartoes as $cartao) {
    $diaFechamento = $cartao->dia_fechamento ?? 'não definido';
    if ($diaFechamento === 'não definido') {
        $diaFechamento = max(1, $cartao->dia_vencimento - 5) . ' (calculado: venc-5)';
    }

    echo "Cartão: {$cartao->nome_cartao}\n";
    echo "  - Dia vencimento: {$cartao->dia_vencimento}\n";
    echo "  - Dia fechamento: {$diaFechamento}\n";
    echo "  - User ID: {$cartao->user_id}\n";
    echo "\n";
}

echo "\n=== TESTE DE LÓGICA ===\n";
echo "Hoje é dia " . date('d') . " de " . date('m/Y') . "\n\n";

// Testar com dia 6
$diaVencimento = 6;
$diaFechamento = 1; // 5 dias antes
$diaCompra = 29; // hoje

echo "Exemplo: Cartão vence dia {$diaVencimento}, fecha dia {$diaFechamento}\n";
echo "Compra feita no dia {$diaCompra}\n";

if ($diaCompra >= $diaFechamento) {
    echo "➡️  LÓGICA ATUAL: Como {$diaCompra} >= {$diaFechamento}, vai para o PRÓXIMO mês\n";
} else {
    echo "➡️  LÓGICA ATUAL: Como {$diaCompra} < {$diaFechamento}, fica no mês ATUAL\n";
}

echo "\nO que deveria acontecer:\n";
echo "- Se comprou ANTES do fechamento (dia 1): entra na fatura do mês atual\n";
echo "- Se comprou DEPOIS do fechamento (dia 2+): entra na fatura do mês seguinte\n";
echo "- Hoje é dia 29, já passou do fechamento (dia 1), então deveria ir para fevereiro ✅\n";
