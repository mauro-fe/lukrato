<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

$cartaoId = $argv[1] ?? null;
if (!$cartaoId) {
    echo "Uso: php cli/show_cartao_detail.php <cartao_credito_id>\n";
    exit;
}

$cartao = CartaoCredito::find((int)$cartaoId);
if (!$cartao) {
    echo "Cartão não encontrado\n";
    exit;
}

echo "Cartão id={$cartao->id}\n";
echo "nome: {$cartao->nome_cartao}\n";
echo "limite_total: " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "limite_disponivel: " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
echo "limite_utilizado: " . number_format($cartao->limite_utilizado, 2, ',', '.') . "\n";
echo "created_at: " . ($cartao->created_at?->format('Y-m-d H:i:s') ?? 'NULL') . "\n";
echo "updated_at: " . ($cartao->updated_at?->format('Y-m-d H:i:s') ?? 'NULL') . "\n";
