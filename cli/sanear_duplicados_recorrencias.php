<?php

/**
 * Saneamento de duplicados de recorrencias (lancamentos e cartao).
 *
 * Uso:
 *   php cli/sanear_duplicados_recorrencias.php
 *   php cli/sanear_duplicados_recorrencias.php --apply
 *
 * Modo padrao: dry-run (somente relatorio, sem alterar dados).
 * Modo --apply: aplica estrategia segura de "detach" nos itens excedentes:
 *   - Mantem o registro mais antigo da serie como canonico
 *   - Remove os excedentes da recorrencia (recorrencia_pai_id = null, recorrente = 0)
 *   - Mantem historico financeiro (nao deleta registros)
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);
$agora = date('Y-m-d H:i:s');

$stats = [
    'lancamentos' => ['grupos' => 0, 'linhas_excedentes' => 0, 'atualizados' => 0],
    'cartao' => ['grupos' => 0, 'linhas_excedentes' => 0, 'atualizados' => 0],
];

echo "=== Saneamento de duplicados de recorrencias ===\n";
echo $apply
    ? "Modo: APPLY (aplicando alteracoes)\n\n"
    : "Modo: DRY-RUN (nenhuma alteracao sera aplicada)\n\n";

try {
    DB::beginTransaction();

    // ---------------------------------------------------------------------
    // 1) Lancamentos: duplicados por (user_id, recorrencia_pai_id, data)
    // ---------------------------------------------------------------------
    $gruposLancamentos = DB::select(
        "SELECT user_id, recorrencia_pai_id, data, COUNT(*) AS qtd
         FROM lancamentos
         WHERE recorrencia_pai_id IS NOT NULL
         GROUP BY user_id, recorrencia_pai_id, data
         HAVING COUNT(*) > 1
         ORDER BY user_id, recorrencia_pai_id, data"
    );

    $stats['lancamentos']['grupos'] = count($gruposLancamentos);

    foreach ($gruposLancamentos as $grupo) {
        $linhas = DB::table('lancamentos')
            ->where('user_id', (int) $grupo->user_id)
            ->where('recorrencia_pai_id', (int) $grupo->recorrencia_pai_id)
            ->where('data', (string) $grupo->data)
            ->orderBy('id', 'asc')
            ->get();

        if ($linhas->count() <= 1) {
            continue;
        }

        $manter = $linhas->first();
        $excedentes = $linhas->slice(1);
        $stats['lancamentos']['linhas_excedentes'] += $excedentes->count();

        echo sprintf(
            "[LANCAMENTOS] user=%d pai=%d data=%s qtd=%d manter_id=%d\n",
            (int) $grupo->user_id,
            (int) $grupo->recorrencia_pai_id,
            (string) $grupo->data,
            (int) $grupo->qtd,
            (int) $manter->id
        );

        foreach ($excedentes as $row) {
            echo sprintf("  - excedente id=%d (pago=%d) -> detach\n", (int) $row->id, (int) $row->pago);

            if ($apply) {
                $updated = DB::table('lancamentos')
                    ->where('id', (int) $row->id)
                    ->update([
                        'recorrente' => 0,
                        'recorrencia_freq' => null,
                        'recorrencia_fim' => null,
                        'recorrencia_total' => null,
                        'recorrencia_pai_id' => null,
                        'updated_at' => $agora,
                    ]);

                $stats['lancamentos']['atualizados'] += (int) $updated;
            }
        }
    }

    // ---------------------------------------------------------------------
    // 2) Cartao: duplicados por
    //    (user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia)
    // ---------------------------------------------------------------------
    $gruposCartao = DB::select(
        "SELECT user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia, COUNT(*) AS qtd
         FROM faturas_cartao_itens
         WHERE recorrencia_pai_id IS NOT NULL
         GROUP BY user_id, cartao_credito_id, recorrencia_pai_id, mes_referencia, ano_referencia
         HAVING COUNT(*) > 1
         ORDER BY user_id, cartao_credito_id, recorrencia_pai_id, ano_referencia, mes_referencia"
    );

    $stats['cartao']['grupos'] = count($gruposCartao);

    foreach ($gruposCartao as $grupo) {
        $linhas = DB::table('faturas_cartao_itens')
            ->where('user_id', (int) $grupo->user_id)
            ->where('cartao_credito_id', (int) $grupo->cartao_credito_id)
            ->where('recorrencia_pai_id', (int) $grupo->recorrencia_pai_id)
            ->where('mes_referencia', (int) $grupo->mes_referencia)
            ->where('ano_referencia', (int) $grupo->ano_referencia)
            ->orderBy('id', 'asc')
            ->get();

        if ($linhas->count() <= 1) {
            continue;
        }

        $manter = $linhas->first();
        $excedentes = $linhas->slice(1);
        $stats['cartao']['linhas_excedentes'] += $excedentes->count();

        echo sprintf(
            "[CARTAO] user=%d cartao=%d pai=%d competencia=%02d/%04d qtd=%d manter_id=%d\n",
            (int) $grupo->user_id,
            (int) $grupo->cartao_credito_id,
            (int) $grupo->recorrencia_pai_id,
            (int) $grupo->mes_referencia,
            (int) $grupo->ano_referencia,
            (int) $grupo->qtd,
            (int) $manter->id
        );

        foreach ($excedentes as $row) {
            echo sprintf("  - excedente id=%d (pago=%d) -> detach\n", (int) $row->id, (int) $row->pago);

            if ($apply) {
                $updated = DB::table('faturas_cartao_itens')
                    ->where('id', (int) $row->id)
                    ->update([
                        'recorrente' => 0,
                        'recorrencia_freq' => null,
                        'recorrencia_fim' => null,
                        'recorrencia_pai_id' => null,
                        'updated_at' => $agora,
                    ]);

                $stats['cartao']['atualizados'] += (int) $updated;
            }
        }
    }

    if ($apply) {
        DB::commit();
    } else {
        DB::rollBack();
    }
} catch (\Throwable $e) {
    DB::rollBack();
    echo "\n❌ Erro no saneamento: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Resumo ---\n";
echo sprintf(
    "Lancamentos: grupos=%d, excedentes=%d, atualizados=%d\n",
    $stats['lancamentos']['grupos'],
    $stats['lancamentos']['linhas_excedentes'],
    $stats['lancamentos']['atualizados']
);
echo sprintf(
    "Cartao: grupos=%d, excedentes=%d, atualizados=%d\n",
    $stats['cartao']['grupos'],
    $stats['cartao']['linhas_excedentes'],
    $stats['cartao']['atualizados']
);

echo "\n✅ Finalizado.\n";
