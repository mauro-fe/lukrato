<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Illuminate\Database\Capsule\Manager as DB;

// Simula o que o código faz
$userId = 23; // Ajuste conforme necessário
$cartaoId = 20; // Ajuste conforme necessário - veja no console qual cartão está tentando pagar

echo "=== SIMULANDO PAGAMENTO DE FATURA ===\n\n";

echo "User ID: {$userId}\n";
echo "Cartão ID: {$cartaoId}\n\n";

try {
    $cartao = CartaoCredito::where('id', $cartaoId)
        ->where('user_id', $userId)
        ->firstOrFail();

    echo "✅ Cartão encontrado: {$cartao->nome_cartao}\n";
    echo "   Conta ID: {$cartao->conta_id}\n\n";

    $contaId = $cartao->conta_id;

    if (!$contaId) {
        echo "❌ Cartão não tem conta vinculada!\n";
        exit(1);
    }

    echo "Buscando conta ID {$contaId} para user {$userId}...\n";

    $conta = Conta::where('id', $contaId)
        ->where('user_id', $userId)
        ->first();

    if (!$conta) {
        echo "❌ ERRO: Conta não encontrada!\n\n";

        // Verificar se a conta existe sem filtro de user
        $contaSemFiltro = Conta::where('id', $contaId)->first();

        if ($contaSemFiltro) {
            echo "⚠️  A conta existe, mas com user_id diferente:\n";
            echo "   Conta User ID: {$contaSemFiltro->user_id}\n";
            echo "   Esperado User ID: {$userId}\n";
        } else {
            echo "⚠️  A conta ID {$contaId} não existe no banco de dados!\n";
        }
    } else {
        echo "✅ Conta encontrada: {$conta->nome}\n";
        echo "   Saldo Inicial: R$ " . number_format($conta->saldo_inicial, 2, ',', '.') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
