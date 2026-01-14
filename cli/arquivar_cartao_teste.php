<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;
$cartaoId = 28;

echo "=== Arquivando cartão ID $cartaoId ===\n\n";

DB::beginTransaction();

try {
    $cartao = CartaoCredito::where('id', $cartaoId)
        ->where('user_id', $userId)
        ->first();

    if (!$cartao) {
        echo "❌ Cartão não encontrado!\n";
        exit(1);
    }

    echo "Cartão: {$cartao->nome_cartao}\n";
    $totalLancamentos = $cartao->lancamentos()->count();
    echo "Lançamentos vinculados: {$totalLancamentos}\n\n";

    // Arquivar
    $cartao->arquivado = true;
    $cartao->save();

    DB::commit();

    echo "✅ Cartão arquivado com sucesso!\n";
    echo "Agora você pode testar a exclusão na página de cartões arquivados.\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Erro: {$e->getMessage()}\n";
}
