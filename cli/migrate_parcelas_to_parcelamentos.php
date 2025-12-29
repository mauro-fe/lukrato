<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== MIGRAÇÃO DE PARCELAS PARA PARCELAMENTOS ===\n\n";

try {
    DB::beginTransaction();

    // Buscar parcelas agrupadas (que têm parcela_atual e total_parcelas mas não têm parcelamento_id)
    $parcelas = DB::table('lancamentos')
        ->where('eh_parcelado', 1)
        ->whereNotNull('parcela_atual')
        ->whereNotNull('total_parcelas')
        ->whereNull('parcelamento_id')
        ->orderBy('lancamento_pai_id')
        ->orderBy('parcela_atual')
        ->get();

    echo "📋 Encontradas: " . count($parcelas) . " parcelas sem parcelamento\n\n";

    // Agrupar por lancamento_pai_id ou por padrão de descrição
    $grupos = [];
    foreach ($parcelas as $p) {
        // Extrair descrição base (sem o número da parcela)
        $descBase = preg_replace('/\s*\(\d+\/\d+\)$/', '', $p->descricao);
        $key = $p->user_id . '_' . $descBase . '_' . $p->total_parcelas . '_' . $p->cartao_credito_id;

        if (!isset($grupos[$key])) {
            $grupos[$key] = [];
        }
        $grupos[$key][] = $p;
    }

    echo "📦 Identificados: " . count($grupos) . " grupos de parcelamentos\n\n";

    foreach ($grupos as $key => $parcelas) {
        $primeira = $parcelas[0];
        $totalParcelas = count($parcelas);
        $valorTotal = array_sum(array_column($parcelas, 'valor'));

        echo "  Criando parcelamento: {$primeira->descricao}\n";
        echo "  └─ {$totalParcelas} parcelas | Total: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";

        // Extrair descrição limpa
        $descricaoLimpa = preg_replace('/\s*\(\d+\/\d+\)$/', '', $primeira->descricao);

        // Buscar categoria válida ou deixar NULL
        $categoriaId = null;
        if ($primeira->categoria_id) {
            $catExists = DB::table('categorias')->where('id', $primeira->categoria_id)->exists();
            if ($catExists) {
                $categoriaId = $primeira->categoria_id;
            }
        }

        // Criar parcelamento
        $parcelamentoId = DB::table('parcelamentos')->insertGetId([
            'user_id' => $primeira->user_id,
            'descricao' => $descricaoLimpa,
            'valor_total' => $valorTotal,
            'numero_parcelas' => $totalParcelas,
            'parcelas_pagas' => 0,
            'categoria_id' => $categoriaId,
            'conta_id' => $primeira->conta_id,
            'cartao_credito_id' => $primeira->cartao_credito_id,
            'tipo' => 'saida',
            'status' => 'ativo',
            'data_criacao' => $primeira->data,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Atualizar parcelas com parcelamento_id
        foreach ($parcelas as $p) {
            DB::table('lancamentos')
                ->where('id', $p->id)
                ->update([
                    'parcelamento_id' => $parcelamentoId,
                    'numero_parcela' => $p->parcela_atual,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    DB::commit();

    echo "\n✅ Migração concluída com sucesso!\n";
    echo "   - " . count($grupos) . " parcelamentos criados\n";
    echo "   - " . count($parcelas) . " parcelas vinculadas\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
}
