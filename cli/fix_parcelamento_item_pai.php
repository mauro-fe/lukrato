<?php

declare(strict_types=1);

/**
 * Script para corrigir parcelamentos sem item_pai_id
 * Vincula parcelas órfãs pela descrição base + data_compra + cartao + total_parcelas
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== CORREÇÃO DE PARCELAMENTOS SEM ITEM_PAI_ID ===\n\n";

// Encontrar parcelas 1 que são pais em potencial (sem filhos e sem item_pai_id)
$parcelas1OrfasSql = "
    SELECT fci.id, fci.user_id, fci.cartao_credito_id, fci.descricao, 
           fci.data_compra, fci.total_parcelas
    FROM faturas_cartao_itens fci
    WHERE fci.item_pai_id IS NULL
      AND fci.parcela_atual = 1
      AND fci.total_parcelas > 1
      AND NOT EXISTS (
          SELECT 1 FROM faturas_cartao_itens filho 
          WHERE filho.item_pai_id = fci.id
      )
    ORDER BY fci.id
";

$pais = DB::select($parcelas1OrfasSql);

echo "Encontradas " . count($pais) . " parcelas 1 sem filhos vinculados.\n\n";

$totalCorrigidos = 0;
$totalGrupos = 0;

foreach ($pais as $pai) {
    // Extrair descrição base (remover número da parcela)
    $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $pai->descricao);
    
    // Buscar parcelas órfãs (2+) que podem ser deste grupo
    $parcelasOrfas = FaturaCartaoItem::where('user_id', $pai->user_id)
        ->where('cartao_credito_id', $pai->cartao_credito_id)
        ->where('data_compra', $pai->data_compra)
        ->where('total_parcelas', $pai->total_parcelas)
        ->where('parcela_atual', '>', 1)
        ->whereNull('item_pai_id')
        ->where('descricao', 'LIKE', $descricaoBase . ' (%/%)')
        ->get();
    
    if ($parcelasOrfas->count() > 0) {
        $totalGrupos++;
        echo "Grupo {$totalGrupos}: \"{$descricaoBase}\" - {$pai->total_parcelas}x\n";
        echo "  Pai ID: {$pai->id}\n";
        echo "  Filhos encontrados: {$parcelasOrfas->count()}\n";
        
        // Atualizar filhos
        foreach ($parcelasOrfas as $filho) {
            $filho->item_pai_id = $pai->id;
            $filho->save();
            $totalCorrigidos++;
            echo "    -> Parcela {$filho->parcela_atual} (ID {$filho->id}) vinculada\n";
        }
        echo "\n";
    }
}

echo "=== RESUMO ===\n";
echo "Grupos corrigidos: {$totalGrupos}\n";
echo "Parcelas vinculadas: {$totalCorrigidos}\n";

// Verificar resultados
echo "\n=== VERIFICAÇÃO PÓS-CORREÇÃO ===\n";
$orfas = FaturaCartaoItem::whereNull('item_pai_id')
    ->where('total_parcelas', '>', 1)
    ->where('parcela_atual', '>', 1)
    ->count();
echo "Parcelas 2+ ainda órfãs: {$orfas}\n";
