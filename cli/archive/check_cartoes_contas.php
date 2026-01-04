<?php

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\CartaoCredito;
use Application\Models\Conta;

echo "=== VERIFICANDO RELACIONAMENTO CARTÕES x CONTAS ===\n\n";

$cartoes = DB::table('cartoes_credito')
    ->select('cartoes_credito.id', 'cartoes_credito.nome_cartao', 'cartoes_credito.conta_id', 'cartoes_credito.user_id', 'contas.nome as conta_nome')
    ->leftJoin('contas', 'cartoes_credito.conta_id', '=', 'contas.id')
    ->limit(10)
    ->get();

foreach ($cartoes as $cartao) {
    $status = $cartao->conta_nome ? '✅' : '❌';
    echo "{$status} Cartão #{$cartao->id} - {$cartao->nome_cartao}\n";
    echo "   User ID: {$cartao->user_id}\n";
    echo "   Conta ID: {$cartao->conta_id}\n";
    echo "   Conta Nome: " . ($cartao->conta_nome ?? 'NÃO ENCONTRADA') . "\n\n";
}

// Verificar se há contas sem cartões
$contasOrfas = DB::table('cartoes_credito')
    ->whereNotNull('conta_id')
    ->whereNotIn('conta_id', function ($query) {
        $query->select('id')->from('contas');
    })
    ->get();

if ($contasOrfas->count() > 0) {
    echo "\n⚠️  CARTÕES COM CONTA_ID INVÁLIDA:\n";
    foreach ($contasOrfas as $cartao) {
        echo "   - Cartão #{$cartao->id}: conta_id={$cartao->conta_id} (não existe)\n";
    }
}

echo "\n✅ Verificação concluída!\n";
