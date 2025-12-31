<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

$cartaoId = $argv[1] ?? null;
if (!$cartaoId) {
    echo "Uso: php cli/recalc_limite_cartao.php <cartao_credito_id>\n";
    exit(1);
}

$cartaoId = (int)$cartaoId;
$cartao = CartaoCredito::find($cartaoId);
if (!$cartao) {
    echo "Cartão id={$cartaoId} não encontrado\n";
    exit(1);
}

echo "Antes:\n";
echo "- id: {$cartao->id}\n";
echo "- nome: {$cartao->nome_cartao}\n";
echo "- limite_total: " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "- limite_disponivel: " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
echo "- limite_utilizado (calc): " . number_format($cartao->limite_utilizado, 2, ',', '.') . "\n\n";

// Recalcular
$cartao->atualizarLimiteDisponivel();

$cartao = $cartao->fresh();

echo "Depois:\n";
echo "- limite_total: " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "- limite_disponivel: " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
echo "- limite_utilizado (calc): " . number_format($cartao->limite_utilizado, 2, ',', '.') . "\n";
