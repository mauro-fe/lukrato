<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== CORREÇÃO DE COMPETÊNCIA - APLICANDO ===\n\n";
echo "Regra: mes_referencia = mês do vencimento - 1\n";
echo "(Competência é o mês ANTERIOR ao vencimento)\n\n";

// Buscar todos os itens onde mes_referencia == mês do vencimento (incorretos)
$itens = DB::table('faturas_cartao_itens')
    ->select('id', 'descricao', 'data_vencimento', 'mes_referencia', 'ano_referencia')
    ->get();

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($itens as $item) {
    $mesVenc = (int) date('n', strtotime($item->data_vencimento));
    $anoVenc = (int) date('Y', strtotime($item->data_vencimento));

    // Calcular competência correta (1 mês antes do vencimento)
    $mesRefCorreto = $mesVenc - 1;
    $anoRefCorreto = $anoVenc;

    if ($mesRefCorreto < 1) {
        $mesRefCorreto = 12;
        $anoRefCorreto--;
    }

    // Verificar se já está correto
    if ((int)$item->mes_referencia === $mesRefCorreto && (int)$item->ano_referencia === $anoRefCorreto) {
        $jaCorretos++;
        continue;
    }

    // Aplicar correção
    try {
        DB::table('faturas_cartao_itens')
            ->where('id', $item->id)
            ->update([
                'mes_referencia' => $mesRefCorreto,
                'ano_referencia' => $anoRefCorreto,
            ]);

        echo "✅ Item {$item->id}: {$item->mes_referencia}/{$item->ano_referencia} → {$mesRefCorreto}/{$anoRefCorreto} | {$item->descricao}\n";
        $corrigidos++;
    } catch (Exception $e) {
        echo "❌ Erro no item {$item->id}: {$e->getMessage()}\n";
        $erros++;
    }
}

echo "\n=== RESUMO ===\n";
echo "✅ Corrigidos: $corrigidos\n";
echo "✓ Já estavam corretos: $jaCorretos\n";
echo "❌ Erros: $erros\n";
echo "Total processados: " . ($corrigidos + $jaCorretos + $erros) . "\n";
