<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Debug Lançamentos dos Cartões ===" . PHP_EOL . PHP_EOL;

$userId = 22;
$mesAtual = date('Y-m');

echo "User ID: {$userId}" . PHP_EOL;
echo "Mês Atual: {$mesAtual}" . PHP_EOL . PHP_EOL;

// Buscar cartões
$cartoes = \Application\Models\CartaoCredito::where('user_id', $userId)->get();

echo "=== CARTÕES ===" . PHP_EOL;
foreach ($cartoes as $cartao) {
    echo "Cartão ID {$cartao->id}: {$cartao->nome_cartao}" . PHP_EOL . PHP_EOL;

    // Buscar TODOS os lançamentos deste cartão
    $lancamentos = \Application\Models\Lancamento::where('user_id', $userId)
        ->where('cartao_credito_id', $cartao->id)
        ->orderBy('data', 'desc')
        ->get();

    echo "  Total de lançamentos (geral): " . $lancamentos->count() . PHP_EOL;

    if ($lancamentos->count() > 0) {
        echo "  Listagem:" . PHP_EOL;
        foreach ($lancamentos as $lanc) {
            echo "    - ID: {$lanc->id}, Data: {$lanc->data}, Tipo: {$lanc->tipo}, Valor: R$ {$lanc->valor}, Desc: " . ($lanc->descricao ?? 'N/A') . PHP_EOL;
        }
    }

    // Buscar lançamentos do mês atual
    $lancamentosMes = \Application\Models\Lancamento::where('user_id', $userId)
        ->where('cartao_credito_id', $cartao->id)
        ->where('tipo', 'despesa')
        ->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$mesAtual])
        ->get();

    echo PHP_EOL . "  Lançamentos do mês {$mesAtual}: " . $lancamentosMes->count() . PHP_EOL;

    if ($lancamentosMes->count() > 0) {
        $total = $lancamentosMes->sum('valor');
        echo "  Total do mês: R$ {$total}" . PHP_EOL;
    }

    // Buscar parcelamentos
    $parcelamentos = \Application\Models\Parcelamento::where('user_id', $userId)
        ->where('cartao_credito_id', $cartao->id)
        ->get();

    echo PHP_EOL . "  Parcelamentos vinculados: " . $parcelamentos->count() . PHP_EOL;

    if ($parcelamentos->count() > 0) {
        foreach ($parcelamentos as $parc) {
            echo "    - ID: {$parc->id}, Desc: " . ($parc->descricao ?? 'N/A') . ", Status: {$parc->status}, Valor Total: R$ {$parc->valor_total}, Parcelas: {$parc->numero_parcelas}" . PHP_EOL;

            // Buscar lançamentos deste parcelamento
            $lancsParcela = \Application\Models\Lancamento::where('user_id', $userId)
                ->where('parcelamento_id', $parc->id)
                ->orderBy('data', 'asc')
                ->get();

            echo "      Lançamentos gerados: " . $lancsParcela->count() . PHP_EOL;

            if ($lancsParcela->count() > 0) {
                foreach ($lancsParcela as $lp) {
                    echo "        • ID: {$lp->id}, Data: {$lp->data}, Valor: R$ {$lp->valor}, Cartão ID: " . ($lp->cartao_credito_id ?? 'NULL') . PHP_EOL;
                }
            }
        }
    }

    echo PHP_EOL . "---" . PHP_EOL . PHP_EOL;
}

echo PHP_EOL . "=== VERIFICAÇÃO GERAL ===" . PHP_EOL;

// Buscar lançamentos sem cartão mas com parcelamento
$lancsSemCartao = \Application\Models\Lancamento::where('user_id', $userId)
    ->whereNull('cartao_credito_id')
    ->whereNotNull('parcelamento_id')
    ->get();

echo "Lançamentos com parcelamento mas SEM cartão: " . $lancsSemCartao->count() . PHP_EOL;

if ($lancsSemCartao->count() > 0) {
    foreach ($lancsSemCartao as $l) {
        $parc = \Application\Models\Parcelamento::find($l->parcelamento_id);
        echo "  - Lanc ID {$l->id}, Parc ID {$l->parcelamento_id}, Cartão do Parc: " . ($parc->cartao_credito_id ?? 'NULL') . ", Data: {$l->data}, Valor: R$ {$l->valor}" . PHP_EOL;
    }
}
