<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;

echo "=== Preparando ambiente de teste ===\n\n";

// Buscar um cartão existente
$cartao = CartaoCredito::where('user_id', $userId)->where('arquivado', false)->first();

if (!$cartao) {
    echo "❌ Nenhum cartão ativo encontrado. Crie um cartão primeiro.\n";
    exit(1);
}

echo "Usando cartão: {$cartao->nome_cartao} (ID: {$cartao->id})\n";

// Buscar uma categoria válida
$categoria = Categoria::where('user_id', $userId)->first();
if (!$categoria) {
    echo "❌ Nenhuma categoria encontrada.\n";
    exit(1);
}

DB::beginTransaction();

try {
    // Criar 5 lançamentos de teste
    for ($i = 1; $i <= 5; $i++) {
        Lancamento::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao->id,
            'descricao' => "Teste exclusão {$i}",
            'valor' => 100.00 * $i,
            'tipo' => 'despesa',
            'data_lancamento' => date('Y-m-d'),
            'categoria_id' => $categoria->id,
            'pago' => false,
        ]);
    }

    // Arquivar o cartão
    $cartao->arquivado = true;
    $cartao->save();

    DB::commit();

    echo "✅ Cartão arquivado com 5 lançamentos de teste\n";
    echo "Cartão ID: {$cartao->id}\n";
    echo "Acesse: " . BASE_URL . "cartoes/arquivadas\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Erro: {$e->getMessage()}\n";
}
