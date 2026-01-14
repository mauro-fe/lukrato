<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\CartaoCreditoService;
use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== Testando exclusão permanente do cartão ===\n\n";

$service = new CartaoCreditoService();
$userId = 1;
$cartaoId = 28;

echo "Primeira tentativa (sem force):\n";
$resultado = $service->excluirCartaoPermanente($cartaoId, $userId, false);
echo "Success: " . ($resultado['success'] ? 'true' : 'false') . "\n";
echo "Requires confirmation: " . ($resultado['requires_confirmation'] ?? false ? 'true' : 'false') . "\n";
echo "Message: {$resultado['message']}\n";
echo "Total lançamentos: " . ($resultado['total_lancamentos'] ?? 0) . "\n\n";

if (!$resultado['success'] && isset($resultado['requires_confirmation']) && $resultado['requires_confirmation']) {
    echo "✅ CORRETO: Está pedindo confirmação porque há lançamentos!\n\n";
    echo "Agora testando com force=true:\n";

    $resultado2 = $service->excluirCartaoPermanente($cartaoId, $userId, true);
    echo "Success: " . ($resultado2['success'] ? 'true' : 'false') . "\n";
    echo "Message: {$resultado2['message']}\n";
    echo "Lançamentos excluídos: " . ($resultado2['deleted_lancamentos'] ?? 0) . "\n";

    if ($resultado2['success']) {
        echo "\n✅ Cartão e lançamentos excluídos com sucesso!\n";

        // Verificar se realmente foi excluído
        $cartaoExiste = CartaoCredito::find($cartaoId);
        $lancamentosExistem = DB::table('lancamentos')->where('cartao_credito_id', $cartaoId)->count();

        echo "\nVerificação:\n";
        echo "Cartão existe no banco: " . ($cartaoExiste ? 'SIM ❌' : 'NÃO ✅') . "\n";
        echo "Lançamentos existem no banco: {$lancamentosExistem} " . ($lancamentosExistem > 0 ? '❌' : '✅') . "\n";
    } else {
        echo "\n❌ Erro ao excluir!\n";
    }
}
