<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "🔄 Reorganizando faturas com mes/ano corretos...\n\n";

DB::beginTransaction();

try {
    // 1. Deletar todas as faturas
    echo "🗑️  Deletando faturas antigas...\n";
    Fatura::truncate();

    // 2. Resetar fatura_id dos itens
    echo "🔄 Resetando fatura_id dos itens...\n";
    FaturaCartaoItem::query()->update(['fatura_id' => null]);

    // 3. Buscar todos os itens
    $itens = FaturaCartaoItem::all();
    echo "📦 Encontrados {$itens->count()} itens.\n\n";

    // 4. Agrupar por (user_id, cartao_id, mes_referencia, ano_referencia)
    $grupos = $itens->groupBy(function ($item) {
        return sprintf(
            '%d_%d_%d_%d',
            $item->user_id,
            $item->cartao_credito_id,
            $item->mes_referencia,
            $item->ano_referencia
        );
    });

    echo "📊 Agrupados em {$grupos->count()} faturas mensais.\n\n";

    $faturaCriadas = 0;

    // 5. Para cada grupo, criar fatura mensal
    foreach ($grupos as $chave => $grupoItens) {
        $primeiroItem = $grupoItens->first();
        $mes = $primeiroItem->mes_referencia;
        $ano = $primeiroItem->ano_referencia;

        // Criar fatura mensal
        $faturaMensal = Fatura::create([
            'user_id' => $primeiroItem->user_id,
            'cartao_credito_id' => $primeiroItem->cartao_credito_id,
            'descricao' => "Fatura {$mes}/{$ano}",
            'valor_total' => $grupoItens->sum('valor'),
            'numero_parcelas' => $grupoItens->count(),
            'data_compra' => $primeiroItem->data_compra,
        ]);

        // Vincular itens à fatura
        foreach ($grupoItens as $item) {
            $item->fatura_id = $faturaMensal->id;
            $item->save();
        }

        $faturaCriadas++;

        $valorTotal = $grupoItens->sum('valor');
        echo "✅ Fatura {$mes}/{$ano}: {$grupoItens->count()} itens, Total: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
    }

    DB::commit();

    echo "\n✅ Reorganização concluída! {$faturaCriadas} faturas mensais criadas.\n";
} catch (\Throwable $e) {
    DB::rollBack();
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
    throw $e;
}
