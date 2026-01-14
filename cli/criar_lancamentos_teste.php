<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\Categoria;
use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;
$cartaoId = 28;

echo "=== Criando lançamentos de teste ===\n\n";

// Buscar uma categoria válida
$categoria = Categoria::where('user_id', $userId)->first();
if (!$categoria) {
    echo "❌ Nenhuma categoria encontrada. Crie uma categoria primeiro.\n";
    exit(1);
}

echo "Usando categoria: {$categoria->nome}\n\n";

DB::beginTransaction();

try {
    // Criar 3 lançamentos de teste
    for ($i = 1; $i <= 3; $i++) {
        Lancamento::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartaoId,
            'descricao' => "Teste de exclusão {$i}",
            'valor' => 50.00 * $i,
            'tipo' => 'despesa',
            'data_lancamento' => date('Y-m-d'),
            'categoria_id' => $categoria->id,
            'pago' => false,
        ]);
        echo "✅ Lançamento {$i} criado\n";
    }

    DB::commit();

    echo "\n✅ Total: 3 lançamentos criados para o cartão ID $cartaoId\n";
    echo "Agora você pode testar a exclusão na página de cartões arquivados.\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Erro: {$e->getMessage()}\n";
}
