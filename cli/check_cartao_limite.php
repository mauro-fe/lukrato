<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

$cartaoId = 36;

echo "=== DADOS DO CARTÃO ===\n\n";
$cartao = CartaoCredito::with('conta')->find($cartaoId);

if (!$cartao) {
    die("Cartão não encontrado!\n");
}

echo "ID: {$cartao->id}\n";
echo "Nome: {$cartao->nome_cartao}\n";
echo "Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "Limite Disponível (campo): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
echo "Ativo: " . ($cartao->ativo ? 'SIM' : 'NÃO') . "\n";

echo "\n=== ITENS DE FATURA NÃO PAGOS ===\n\n";
$itensNaoPagos = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', $cartaoId)
    ->where('pago', false)
    ->select('id', 'descricao', 'valor', 'mes_referencia', 'ano_referencia')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->get();

$totalNaoPago = 0;
foreach ($itensNaoPagos as $item) {
    echo sprintf(
        "ID: %d | %s | R$ %.2f | %02d/%d\n",
        $item->id,
        substr($item->descricao, 0, 40),
        $item->valor,
        $item->mes_referencia,
        $item->ano_referencia
    );
    $totalNaoPago += $item->valor;
}

echo "\nTotal não pago: R$ " . number_format($totalNaoPago, 2, ',', '.') . "\n";
echo "Limite disponível REAL: R$ " . number_format($cartao->limite_total - $totalNaoPago, 2, ',', '.') . "\n";
echo "Utilização REAL: " . number_format(($totalNaoPago / $cartao->limite_total) * 100, 1, ',', '.') . "%\n";

echo "\n=== VERIFICANDO ACCESSORS DO MODEL ===\n\n";
echo "limite_utilizado (accessor): R$ " . number_format($cartao->limite_utilizado, 2, ',', '.') . "\n";
echo "percentual_uso (accessor): " . number_format($cartao->percentual_uso, 1, ',', '.') . "%\n";
