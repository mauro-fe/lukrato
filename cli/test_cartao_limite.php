<?php

/**
 * Teste de limite do cartão de crédito
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;

echo "\n🧪 TESTE DE LIMITE DO CARTÃO DE CRÉDITO\n";
echo "════════════════════════════════════════════════════════\n\n";

// Solicitar ID do cartão
echo "Digite o ID do cartão para testar: ";
$cartaoId = trim(fgets(STDIN));

if (!$cartaoId || !is_numeric($cartaoId)) {
    echo "❌ ID inválido!\n\n";
    exit(1);
}

$cartao = CartaoCredito::find($cartaoId);

if (!$cartao) {
    echo "❌ Cartão não encontrado!\n\n";
    exit(1);
}

echo "📇 INFORMAÇÕES DO CARTÃO\n";
echo "─────────────────────────────────────────────────────────\n";
echo "ID: {$cartao->id}\n";
echo "Nome: {$cartao->nome_cartao}\n";
echo "Bandeira: {$cartao->bandeira}\n";
echo "Últimos dígitos: {$cartao->ultimos_digitos}\n";
echo "Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "Limite Disponível (registrado): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n\n";

// Calcular limite real (somar itens não pagos)
$itensNaoPagos = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
    ->where('pago', false)
    ->get();

$totalNaoPago = $itensNaoPagos->sum('valor');
$limiteCalculado = $cartao->limite_total - $totalNaoPago;

echo "📊 CÁLCULO DO LIMITE\n";
echo "─────────────────────────────────────────────────────────\n";
echo "Total de itens não pagos: {$itensNaoPagos->count()}\n";
echo "Valor total não pago: R$ " . number_format($totalNaoPago, 2, ',', '.') . "\n";
echo "Limite calculado: R$ " . number_format($limiteCalculado, 2, ',', '.') . "\n\n";

// Verificar divergência
$diferenca = abs($cartao->limite_disponivel - $limiteCalculado);

if ($diferenca > 0.01) {
    echo "⚠️  DIVERGÊNCIA DETECTADA!\n";
    echo "Diferença: R$ " . number_format($diferenca, 2, ',', '.') . "\n\n";

    echo "Deseja corrigir o limite? (s/n): ";
    $resposta = trim(fgets(STDIN));

    if (strtolower($resposta) === 's') {
        $cartao->limite_disponivel = $limiteCalculado;
        $cartao->save();
        echo "✅ Limite corrigido!\n";
        echo "Novo limite disponível: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n\n";
    }
} else {
    echo "✅ Limite está correto!\n\n";
}

// Listar últimos itens
if ($itensNaoPagos->count() > 0) {
    echo "📋 ÚLTIMOS ITENS NÃO PAGOS (até 5)\n";
    echo "─────────────────────────────────────────────────────────\n";

    foreach ($itensNaoPagos->take(5) as $item) {
        echo sprintf(
            "• %s - R$ %.2f - Venc: %s\n",
            $item->descricao,
            $item->valor,
            $item->data_vencimento
        );
    }
    echo "\n";
}

echo "✅ Teste concluído!\n\n";
