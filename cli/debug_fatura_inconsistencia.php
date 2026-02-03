<?php

/**
 * Debug: Verificar inconsistência entre descrição da fatura e data_vencimento dos itens
 */
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;

$userId = 1;

echo "=== Faturas e seus itens ===\n\n";

$faturas = Fatura::where('user_id', $userId)
    ->with(['cartaoCredito', 'itens'])
    ->orderBy('id')
    ->get();

foreach ($faturas as $fatura) {
    echo "FATURA ID: {$fatura->id}\n";
    echo "  Descrição: {$fatura->descricao}\n";
    echo "  Cartão: " . ($fatura->cartaoCredito->nome_cartao ?? 'N/A') . "\n";

    // Extrair mês/ano da descrição
    if (preg_match('/(\d+)\/(\d+)/', $fatura->descricao, $matches)) {
        $mesDescricao = (int)$matches[1];
        $anoDescricao = (int)$matches[2];
        echo "  Mês/Ano (descrição): {$mesDescricao}/{$anoDescricao}\n";
    }

    echo "  Itens:\n";

    $inconsistente = false;
    foreach ($fatura->itens as $item) {
        $mesVenc = $item->data_vencimento ? $item->data_vencimento->month : null;
        $anoVenc = $item->data_vencimento ? $item->data_vencimento->year : null;

        $status = $item->pago ? 'PAGO' : 'PENDENTE';

        // Verificar inconsistência
        $flag = '';
        if (isset($mesDescricao) && $mesVenc && $mesVenc != $mesDescricao) {
            $flag = ' ⚠️ INCONSISTENTE!';
            $inconsistente = true;
        }

        echo "    ID:{$item->id} | {$item->descricao} | mes_ref:{$item->mes_referencia} | venc:{$item->data_vencimento?->format('Y-m-d')} (mês {$mesVenc}) | {$status}{$flag}\n";
    }

    if ($inconsistente) {
        echo "  ⚠️ ESTA FATURA TEM INCONSISTÊNCIA entre descrição e data_vencimento!\n";
    }

    echo "\n" . str_repeat('-', 80) . "\n\n";
}

echo "=== Análise do problema ===\n\n";
echo "Se a descrição da fatura é 'Fatura 9/2026' mas os itens têm data_vencimento em OUTUBRO:\n";
echo "  1. Frontend extrai mês=9 da descrição\n";
echo "  2. Backend busca itens com whereMonth(data_vencimento, 9)\n";
echo "  3. Como itens vencem em outubro (mês 10), não encontra nada!\n\n";

echo "SOLUÇÃO: O mês usado na descrição deve corresponder ao mês de VENCIMENTO, não ao mes_referencia\n";
