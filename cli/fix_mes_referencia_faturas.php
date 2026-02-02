<?php

/**
 * Script para corrigir mes_referencia dos itens de fatura
 * 
 * Lógica tradicional de cartões:
 * - Compra à vista: mes_referencia = mês da COMPRA
 * - Compra parcelada: mes_referencia = mês do VENCIMENTO de cada parcela
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Correção de mes_referencia nos itens de fatura ===\n\n";

// Buscar todos os itens
$itens = DB::table('faturas_cartao_itens')->get();

echo "Total de itens encontrados: " . count($itens) . "\n\n";

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($itens as $item) {
    $isParcelado = ($item->total_parcelas ?? 1) > 1;

    // Determinar data base para mes_referencia
    if ($isParcelado) {
        // Parcelado: usar mês do vencimento da parcela
        $dataBase = $item->data_vencimento;
        $tipo = 'parcelado';
    } else {
        // À vista: usar mês da compra
        $dataBase = $item->data_compra;
        $tipo = 'à vista';
    }

    if (!$dataBase) {
        echo "⚠️  Item #{$item->id}: sem data_" . ($isParcelado ? 'vencimento' : 'compra') . "\n";
        $erros++;
        continue;
    }

    $mesCorreto = (int) date('n', strtotime($dataBase));
    $anoCorreto = (int) date('Y', strtotime($dataBase));

    $mesAtual = (int) $item->mes_referencia;
    $anoAtual = (int) $item->ano_referencia;

    if ($mesAtual === $mesCorreto && $anoAtual === $anoCorreto) {
        $jaCorretos++;
        continue;
    }

    // Precisa corrigir
    echo "📝 Item #{$item->id} ({$tipo}): {$mesAtual}/{$anoAtual} → {$mesCorreto}/{$anoCorreto}\n";
    echo "   Descrição: {$item->descricao}\n";
    echo "   Data compra: {$item->data_compra}, Vencimento: {$item->data_vencimento}\n";

    DB::table('faturas_cartao_itens')
        ->where('id', $item->id)
        ->update([
            'mes_referencia' => $mesCorreto,
            'ano_referencia' => $anoCorreto,
        ]);

    $corrigidos++;
}

echo "\n=== Resumo ===\n";
echo "✅ Já estavam corretos: {$jaCorretos}\n";
echo "📝 Corrigidos: {$corrigidos}\n";
echo "⚠️  Erros: {$erros}\n";

// Agora verificar se as faturas estão consistentes
echo "\n=== Verificando faturas ===\n";

$faturas = DB::table('faturas_cartao')
    ->orderBy('ano')
    ->orderBy('mes')
    ->get();

foreach ($faturas as $fatura) {
    $totalItens = DB::table('faturas_cartao_itens')
        ->where('fatura_id', $fatura->id)
        ->count();

    $itensMesCorreto = DB::table('faturas_cartao_itens')
        ->where('fatura_id', $fatura->id)
        ->where('mes_referencia', $fatura->mes)
        ->where('ano_referencia', $fatura->ano)
        ->count();

    $itensMesDiferente = $totalItens - $itensMesCorreto;

    if ($itensMesDiferente > 0) {
        echo "⚠️  Fatura {$fatura->mes}/{$fatura->ano}: {$itensMesDiferente} itens com mes_referencia diferente do mês da fatura\n";
    } else {
        echo "✅ Fatura {$fatura->mes}/{$fatura->ano}: {$totalItens} itens OK\n";
    }
}

echo "\n✅ Correção concluída!\n";
