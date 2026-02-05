<?php

/**
 * Script para corrigir item_pai_id dos parcelamentos existentes
 * 
 * Agrupa os itens por:
 * - user_id
 * - cartao_credito_id  
 * - descricao base (sem o "(X/Y)")
 * - total_parcelas
 * - data_compra
 * 
 * E define item_pai_id para vincular as parcelas
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Corrigindo item_pai_id de parcelamentos ===\n\n";

// Buscar todos os itens parcelados (total_parcelas > 1) sem item_pai_id
$itens = DB::table('faturas_cartao_itens')
    ->whereNull('item_pai_id')
    ->where('total_parcelas', '>', 1)
    ->orderBy('user_id')
    ->orderBy('cartao_credito_id')
    ->orderBy('data_compra')
    ->orderBy('parcela_atual')
    ->get();

echo "Encontrados {$itens->count()} itens parcelados sem item_pai_id\n\n";

if ($itens->isEmpty()) {
    echo "Nada a corrigir!\n";
    exit(0);
}

// Agrupar por chave única do parcelamento
$grupos = [];

foreach ($itens as $item) {
    // Extrair descrição base removendo o padrão (X/Y)
    $descBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $item->descricao);
    
    // Criar chave única para o grupo
    $chave = implode('|', [
        $item->user_id,
        $item->cartao_credito_id,
        $descBase,
        $item->total_parcelas,
        $item->data_compra
    ]);
    
    if (!isset($grupos[$chave])) {
        $grupos[$chave] = [];
    }
    
    $grupos[$chave][] = $item;
}

echo "Agrupados em " . count($grupos) . " parcelamentos\n\n";

$totalCorrigidos = 0;
$totalGrupos = 0;

foreach ($grupos as $chave => $itensGrupo) {
    // Ordenar por parcela_atual
    usort($itensGrupo, fn($a, $b) => $a->parcela_atual <=> $b->parcela_atual);
    
    // Verificar se tem mais de 1 item
    if (count($itensGrupo) < 2) {
        continue;
    }
    
    // Primeira parcela é o pai
    $itemPai = $itensGrupo[0];
    $itemPaiId = $itemPai->id;
    
    // Extrair info para log
    $descBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $itemPai->descricao);
    
    echo "Parcelamento: {$descBase}\n";
    echo "  User: {$itemPai->user_id}, Cartão: {$itemPai->cartao_credito_id}\n";
    echo "  Data compra: {$itemPai->data_compra}, Total parcelas: {$itemPai->total_parcelas}\n";
    echo "  Itens encontrados: " . count($itensGrupo) . "\n";
    
    // Atualizar parcelas 2+ com item_pai_id
    for ($i = 1; $i < count($itensGrupo); $i++) {
        $itemFilho = $itensGrupo[$i];
        
        DB::table('faturas_cartao_itens')
            ->where('id', $itemFilho->id)
            ->update(['item_pai_id' => $itemPaiId]);
        
        echo "    Parcela {$itemFilho->parcela_atual}: ID {$itemFilho->id} -> item_pai_id = {$itemPaiId}\n";
        $totalCorrigidos++;
    }
    
    echo "\n";
    $totalGrupos++;
}

echo "=== Resumo ===\n";
echo "Grupos processados: {$totalGrupos}\n";
echo "Itens corrigidos: {$totalCorrigidos}\n";
echo "\nConcluído!\n";
