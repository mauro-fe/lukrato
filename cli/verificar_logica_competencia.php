<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICAÇÃO DA LÓGICA DE COMPETÊNCIA ===\n\n";
echo "Regra esperada: data_vencimento = mes_referencia + 1 mês\n";
echo "(Competência fevereiro → Vence março)\n\n";

$itens = DB::table('faturas_cartao_itens')
    ->select('id', 'descricao', 'data_compra', 'data_vencimento', 'mes_referencia', 'ano_referencia', 'parcela_atual', 'total_parcelas', 'eh_parcelado', 'fatura_id')
    ->orderBy('id')
    ->get();

$corretos = 0;
$incorretos = [];

foreach ($itens as $item) {
    $mesVenc = (int) date('n', strtotime($item->data_vencimento));
    $anoVenc = (int) date('Y', strtotime($item->data_vencimento));

    // Calcular qual deveria ser o mês/ano de vencimento baseado na competência
    // Competência + 1 mês = Vencimento esperado
    $mesRefInt = (int) $item->mes_referencia;
    $anoRefInt = (int) $item->ano_referencia;

    // Adicionar 1 mês à competência para obter vencimento esperado
    $mesVencEsperado = $mesRefInt + 1;
    $anoVencEsperado = $anoRefInt;

    if ($mesVencEsperado > 12) {
        $mesVencEsperado = 1;
        $anoVencEsperado++;
    }

    // Verificar se bate
    if ($mesVenc === $mesVencEsperado && $anoVenc === $anoVencEsperado) {
        $corretos++;
    } else {
        $incorretos[] = [
            'item' => $item,
            'esperado_mes' => $mesVencEsperado,
            'esperado_ano' => $anoVencEsperado,
            'atual_mes' => $mesVenc,
            'atual_ano' => $anoVenc,
        ];
    }
}

echo "✅ Itens CORRETOS (vencimento = competência + 1 mês): $corretos\n";
echo "⚠️ Itens INCORRETOS: " . count($incorretos) . "\n\n";

if (count($incorretos) > 0) {
    echo "--- ITENS COM PROBLEMA ---\n\n";

    foreach ($incorretos as $p) {
        $item = $p['item'];
        $parcela = $item->parcela_atual && $item->total_parcelas ? "{$item->parcela_atual}/{$item->total_parcelas}" : '-';

        echo "ID: {$item->id} | Fatura: {$item->fatura_id}\n";
        echo "  Descrição: {$item->descricao}\n";
        echo "  Competência atual: {$item->mes_referencia}/{$item->ano_referencia}\n";
        echo "  Vencimento atual: {$p['atual_mes']}/{$p['atual_ano']} ({$item->data_vencimento})\n";
        echo "  Vencimento ESPERADO: {$p['esperado_mes']}/{$p['esperado_ano']}\n";
        echo "  Parcela: {$parcela}\n";
        echo "  ---\n";
    }

    echo "\n--- ANÁLISE DOS PROBLEMAS ---\n";

    // Agrupar por tipo de diferença
    $mesmoMes = 0;
    $doisMesesDiff = 0;
    $outros = 0;

    foreach ($incorretos as $p) {
        $item = $p['item'];
        $mesVenc = (int) date('n', strtotime($item->data_vencimento));
        $mesRef = (int) $item->mes_referencia;

        $diff = $mesVenc - $mesRef;
        if ($diff < 0) $diff += 12; // Ajustar para virada de ano

        if ($diff === 0) {
            $mesmoMes++;
        } elseif ($diff === 2) {
            $doisMesesDiff++;
        } else {
            $outros++;
        }
    }

    echo "  - Vencimento no MESMO mês da competência: $mesmoMes\n";
    echo "  - Vencimento 2 meses após competência: $doisMesesDiff\n";
    echo "  - Outras diferenças: $outros\n";
}

echo "\n\n--- O QUE PRECISA SER CORRIGIDO ---\n";
if (count($incorretos) > 0) {
    echo "Para cada item incorreto, precisamos ajustar o mes_referencia\n";
    echo "para ser 1 mês ANTES do vencimento.\n\n";

    echo "Exemplo de correção:\n";
    foreach (array_slice($incorretos, 0, 3) as $p) {
        $item = $p['item'];
        $mesVenc = (int) date('n', strtotime($item->data_vencimento));
        $anoVenc = (int) date('Y', strtotime($item->data_vencimento));

        // Competência correta = vencimento - 1 mês
        $mesRefCorreto = $mesVenc - 1;
        $anoRefCorreto = $anoVenc;
        if ($mesRefCorreto < 1) {
            $mesRefCorreto = 12;
            $anoRefCorreto--;
        }

        echo "  Item {$item->id}: {$item->mes_referencia}/{$item->ano_referencia} → {$mesRefCorreto}/{$anoRefCorreto}\n";
    }
} else {
    echo "✅ Todos os itens estão corretos! Nenhuma correção necessária.\n";
}
