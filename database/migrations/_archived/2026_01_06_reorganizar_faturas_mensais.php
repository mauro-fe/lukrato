<?php

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;

return new class
{
    public function up(): void
    {
        echo "🔄 Reorganizando faturas para estrutura mensal...\n";

        try {
            DB::beginTransaction();

            // 1. Buscar todos os itens
            $itens = FaturaCartaoItem::with('fatura')->get();
            echo "📦 Encontrados {$itens->count()} itens.\n";

            // 2. Agrupar por (user_id, cartao_id, mes_referencia, ano_referencia)
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

            $faturasAntigas = [];
            $faturaCriadas = 0;

            // 3. Para cada grupo, criar fatura mensal
            foreach ($grupos as $chave => $grupoItens) {
                $primeiroItem = $grupoItens->first();
                $mes = $primeiroItem->mes_referencia;
                $ano = $primeiroItem->ano_referencia;

                // Buscar fatura mensal existente
                $faturaMensal = Fatura::where('user_id', $primeiroItem->user_id)
                    ->where('cartao_credito_id', $primeiroItem->cartao_credito_id)
                    ->where('descricao', "Fatura {$mes}/{$ano}")
                    ->first();

                // Se não existe, criar
                if (!$faturaMensal) {
                    $faturaMensal = Fatura::create([
                        'user_id' => $primeiroItem->user_id,
                        'cartao_credito_id' => $primeiroItem->cartao_credito_id,
                        'descricao' => "Fatura {$mes}/{$ano}",
                        'valor_total' => 0,
                        'numero_parcelas' => 0,
                        'data_compra' => $primeiroItem->data_compra,
                    ]);
                    $faturaCriadas++;
                }

                // Calcular valor total e atualizar itens
                $valorTotal = 0;
                foreach ($grupoItens as $item) {
                    // Guardar ID da fatura antiga para deletar depois
                    if ($item->fatura_id && !in_array($item->fatura_id, $faturasAntigas)) {
                        $faturasAntigas[] = $item->fatura_id;
                    }

                    // Vincular item à fatura mensal
                    $item->fatura_id = $faturaMensal->id;
                    $item->save();

                    $valorTotal += $item->valor;
                }

                // Atualizar valor total da fatura mensal
                $faturaMensal->valor_total = $valorTotal;
                $faturaMensal->numero_parcelas = $grupoItens->count();
                $faturaMensal->save();

                echo "✅ Fatura {$mes}/{$ano}: {$grupoItens->count()} itens, Total: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
            }

            // 4. Remover faturas antigas que ficaram órfãs
            if (!empty($faturasAntigas)) {
                // Verificar se realmente não têm itens
                $faturasParaDeletar = Fatura::whereIn('id', $faturasAntigas)
                    ->whereDoesntHave('itens')
                    ->get();

                foreach ($faturasParaDeletar as $faturaAntiga) {
                    $faturaAntiga->delete();
                }

                echo "\n🗑️  {$faturasParaDeletar->count()} faturas antigas removidas.\n";
            }

            DB::commit();

            echo "\n✅ Migração concluída! {$faturaCriadas} faturas mensais criadas.\n";
        } catch (\Throwable $e) {
            DB::rollBack();
            echo "\n❌ Erro: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        echo "⚠️  Reversão não implementada. Execute a migração anterior se necessário.\n";
    }
};
