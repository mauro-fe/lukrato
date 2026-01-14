<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;

echo "=== Buscando cartão disponível ===\n\n";

// Buscar cartão existente
$cartao = CartaoCredito::where('user_id', $userId)->first();

if (!$cartao) {
    echo "❌ Nenhum cartão encontrado para user_id {$userId}\n";
    echo "Por favor, crie um cartão primeiro.\n";
    exit(1);
}

$cartaoId = $cartao->id;

echo "✅ Cartão encontrado: {$cartao->nome_cartao} (ID: {$cartaoId})\n\n";

DB::beginTransaction();

try {
    // 1. Atualizar lançamentos
    $lancamentos = DB::table('lancamentos')
        ->where('user_id', $userId)
        ->whereNull('cartao_credito_id')
        ->update(['cartao_credito_id' => $cartaoId]);

    echo "✅ {$lancamentos} lançamento(s) vinculado(s) ao cartão ID {$cartaoId}\n";

    // 2. Atualizar faturas (tabela principal)
    $faturas = DB::table('faturas')
        ->where('user_id', $userId)
        ->update(['cartao_credito_id' => $cartaoId]);

    echo "✅ {$faturas} fatura(s) vinculada(s) ao cartão ID {$cartaoId}\n";

    // 3. Atualizar faturas_cartao_itens
    $itens = DB::table('faturas_cartao_itens')
        ->where('user_id', $userId)
        ->update(['cartao_credito_id' => $cartaoId]);

    echo "✅ {$itens} item(ns) de fatura vinculado(s) ao cartão ID {$cartaoId}\n";

    DB::commit();

    echo "\n✅ Operação concluída com sucesso!\n";
    echo "\nResumo:\n";
    echo "  - Lançamentos: {$lancamentos}\n";
    echo "  - Faturas: {$faturas}\n";
    echo "  - Itens de fatura: {$itens}\n";
    echo "  - Cartão destino: {$cartao->nome_cartao} (ID: {$cartaoId})\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ Erro: {$e->getMessage()}\n";
    exit(1);
}
