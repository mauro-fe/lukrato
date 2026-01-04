<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;

$userId = 22;

echo "🔍 Verificando cartões do usuário {$userId}...\n\n";

$cartoes = CartaoCredito::where('user_id', $userId)->get();

echo "📊 Total: " . $cartoes->count() . " cartões\n\n";

foreach ($cartoes as $cartao) {
    echo "  • {$cartao->nome} (ID: {$cartao->id})\n";
    echo "    - Bandeira: {$cartao->bandeira}\n";
    echo "    - Ativo: " . ($cartao->ativo ? 'Sim' : 'Não') . "\n";
    echo "    - Limite: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
    echo "    - Disponível: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n\n";
}
