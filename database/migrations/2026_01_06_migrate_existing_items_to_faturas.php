<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;

return new class {
    public function up(): void
    {
        echo "🔄 Agrupando itens avulsos em faturas...\n";

        // Buscar todos os itens sem fatura_id
        $itens = FaturaCartaoItem::whereNull('fatura_id')
            ->orderBy('user_id')
            ->orderBy('cartao_credito_id')
            ->orderBy('descricao')
            ->orderBy('data_compra')
            ->get();

        if ($itens->isEmpty()) {
            echo "✅ Nenhum item avulso encontrado.\n";
            return;
        }

        echo "📦 Encontrados {$itens->count()} itens avulsos.\n";

        // Agrupar por características comuns
        $grupos = $itens->groupBy(function ($item) {
            // Remover sufixo (X/Y) da descrição
            $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)$/', '', $item->descricao);

            return sprintf(
                '%d_%d_%s_%s_%d',
                $item->user_id,
                $item->cartao_credito_id,
                $descricaoBase,
                $item->data_compra->format('Y-m-d'),
                $item->total_parcelas
            );
        });

        $faturascriadas = 0;

        foreach ($grupos as $chave => $grupoItens) {
            try {
                $primeiro = $grupoItens->first();

                // Remover sufixo da descrição
                $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)$/', '', $primeiro->descricao);

                // Criar fatura
                $fatura = Fatura::create([
                    'user_id' => $primeiro->user_id,
                    'cartao_credito_id' => $primeiro->cartao_credito_id,
                    'descricao' => $descricaoBase,
                    'valor_total' => $grupoItens->sum('valor'),
                    'numero_parcelas' => $grupoItens->count(),
                    'data_compra' => $primeiro->data_compra,
                ]);

                // Atualizar itens para referenciar a fatura
                foreach ($grupoItens as $item) {
                    $item->fatura_id = $fatura->id;
                    $item->save();
                }

                $faturasCreadas++;

                echo "✅ Fatura #{$fatura->id}: {$descricaoBase} ({$grupoItens->count()} parcelas)\n";
            } catch (Exception $e) {
                echo "❌ Erro ao criar fatura: {$e->getMessage()}\n";
            }
        }

        echo "\n✅ Migração concluída! {$faturasCreadas} faturas criadas.\n";
    }

    public function down(): void
    {
        echo "🔄 Revertendo: removendo faturas criadas e desvinculando itens...\n";

        // Desvincular todos os itens
        DB::table('faturas_cartao_itens')->update(['fatura_id' => null]);

        // Remover todas as faturas
        DB::table('faturas')->truncate();

        echo "✅ Reversão concluída.\n";
    }
};
