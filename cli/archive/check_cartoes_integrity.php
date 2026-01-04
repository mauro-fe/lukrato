<?php

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICANDO INTEGRIDADE CARTÕES x CONTAS ===\n\n";

// Buscar cartões com problemas
$problemas = DB::table('cartoes_credito as cc')
    ->select('cc.id', 'cc.nome_cartao', 'cc.conta_id', 'cc.user_id as cartao_user_id', 'c.id as conta_id_real', 'c.user_id as conta_user_id', 'c.nome as conta_nome')
    ->leftJoin('contas as c', 'cc.conta_id', '=', 'c.id')
    ->whereRaw('cc.user_id != c.user_id OR c.id IS NULL')
    ->get();

if ($problemas->count() > 0) {
    echo "❌ PROBLEMAS ENCONTRADOS:\n\n";

    foreach ($problemas as $p) {
        echo "Cartão ID: {$p->id} - {$p->nome_cartao}\n";
        echo "  Cartão User ID: {$p->cartao_user_id}\n";
        echo "  Conta ID vinculada: {$p->conta_id}\n";

        if ($p->conta_id_real === null) {
            echo "  ❌ CONTA NÃO EXISTE\n";
        } else {
            echo "  Conta User ID: {$p->conta_user_id}\n";
            echo "  ❌ USER IDs DIFERENTES (cartão: {$p->cartao_user_id}, conta: {$p->conta_user_id})\n";
        }
        echo "\n";
    }

    echo "\n💡 SOLUÇÃO: Execute o script de correção\n";
} else {
    echo "✅ Todos os cartões estão corretamente vinculados!\n";
}
