<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;
$cartaoId = 28;

try {
    DB::beginTransaction();

    // Verificar se o cartão existe
    $cartao = DB::table('cartoes_credito')
        ->where('id', $cartaoId)
        ->where('user_id', $userId)
        ->first();

    if (!$cartao) {
        echo "❌ Cartão ID $cartaoId não encontrado para o usuário $userId\n";
        exit(1);
    }

    echo "✅ Cartão encontrado: {$cartao->nome_cartao}\n\n";

    // Atualizar lançamentos (tabela lancamentos)
    $lancamentos = DB::table('lancamentos')
        ->where('user_id', $userId)
        ->whereNotNull('cartao_credito_id')
        ->update(['cartao_credito_id' => $cartaoId]);

    echo "✅ $lancamentos lançamento(s) vinculado(s) ao cartão ID $cartaoId\n";

    // Verificar e atualizar faturas_cartao
    $tabelasExistentes = DB::select("SHOW TABLES");
    $tabelas = array_map('current', $tabelasExistentes);

    // Atualizar tabela faturas (sem cartao no nome)
    if (in_array('faturas', $tabelas)) {
        $faturas = DB::table('faturas')
            ->where('user_id', $userId)
            ->update(['cartao_credito_id' => $cartaoId]);

        echo "✅ $faturas fatura(s) principal vinculada(s) ao cartão ID $cartaoId\n";
    }

    if (in_array('faturas_cartao', $tabelas)) {
        $faturas = DB::table('faturas_cartao')
            ->where('user_id', $userId)
            ->update(['cartao_credito_id' => $cartaoId]);

        echo "✅ $faturas fatura(s) de cartão vinculada(s) ao cartão ID $cartaoId\n";
    }

    // Verificar e atualizar faturas_cartao_itens
    if (in_array('faturas_cartao_itens', $tabelas)) {
        $itens = DB::table('faturas_cartao_itens')
            ->whereIn('lancamento_id', function ($query) use ($userId) {
                $query->select('id')
                    ->from('lancamentos')
                    ->where('user_id', $userId);
            })
            ->update(['cartao_credito_id' => $cartaoId]);

        echo "✅ $itens item(ns) de fatura vinculado(s) ao cartão ID $cartaoId\n";
    }

    DB::commit();
    echo "\n✅ Operação concluída com sucesso!\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
