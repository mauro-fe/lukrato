<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;

echo "=== Debug Fatura 19 ===\n\n";

$fatura = Fatura::find(19);
if ($fatura) {
    echo "Fatura ID: {$fatura->id}\n";
    echo "Descrição: {$fatura->descricao}\n";
    echo "Status: {$fatura->status}\n";
    echo "Cartão ID: {$fatura->cartao_credito_id}\n";

    // Extrair mês/ano da descrição
    $match = [];
    if (preg_match('/(\d+)\/(\d+)/', $fatura->descricao, $match)) {
        echo "\nMês/Ano extraído da descrição: {$match[1]}/{$match[2]}\n";
    }

    echo "\n--- Itens desta fatura ---\n";
    $itens = FaturaCartaoItem::where('fatura_id', 19)->get();
    foreach ($itens as $i) {
        $pago = $i->pago ? 'PAGO' : 'NAO_PAGO';
        $mesVenc = date('n', strtotime($i->data_vencimento));
        $anoVenc = date('Y', strtotime($i->data_vencimento));
        echo "ID:{$i->id} | {$pago}\n";
        echo "  data_vencimento: {$i->data_vencimento} (mês:{$mesVenc})\n";
        echo "  mes_referencia: {$i->mes_referencia}/{$i->ano_referencia}\n";
        echo "  descricao: {$i->descricao}\n";
    }
}

echo "\n\n=== Todos os lançamentos de pagamento ===\n";
$pagamentos = Lancamento::where('origem_tipo', 'pagamento_fatura')->get();
foreach ($pagamentos as $p) {
    echo "ID:{$p->id} | {$p->descricao}\n";
    echo "  obs: {$p->observacao}\n";
}
