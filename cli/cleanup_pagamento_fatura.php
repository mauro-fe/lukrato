<?php

/**
 * Remover lançamentos antigos de "Pagamento Fatura" que foram criados incorretamente
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n🗑️  LIMPEZA: REMOVER LANÇAMENTOS INCORRETOS\n";
echo "════════════════════════════════════════════════════════\n\n";

// Buscar lançamentos de "Pagamento Fatura"
$lancamentosPagamento = Lancamento::where('descricao', 'like', 'Pagamento Fatura%')
    ->orderBy('id', 'desc')
    ->get();

echo "📋 Lançamentos de 'Pagamento Fatura' encontrados: {$lancamentosPagamento->count()}\n\n";

if ($lancamentosPagamento->isEmpty()) {
    echo "✅ Nenhum lançamento incorreto encontrado!\n\n";
    exit(0);
}

foreach ($lancamentosPagamento as $lanc) {
    echo "• ID {$lanc->id}: {$lanc->descricao} - R$ {$lanc->valor} ({$lanc->data})\n";
}

echo "\n⚠️  ATENÇÃO: Estes lançamentos foram criados incorretamente.\n";
echo "   A lógica correta é: cada ITEM da fatura vira um lançamento separado.\n";
echo "   Estes lançamentos agregados serão removidos.\n\n";

$handle = fopen("php://stdin", "r");
echo "Deseja remover estes lançamentos? [s/N]: ";
$resposta = strtolower(trim(fgets($handle)));

if ($resposta !== 's' && $resposta !== 'sim') {
    echo "\n❌ Operação cancelada.\n\n";
    exit(0);
}

echo "\n🗑️  Removendo lançamentos...\n\n";

$removidos = 0;
$erros = 0;

foreach ($lancamentosPagamento as $lanc) {
    try {
        $lanc->delete();
        echo "✅ Removido lançamento ID {$lanc->id}\n";
        $removidos++;
    } catch (\Exception $e) {
        echo "❌ Erro ao remover ID {$lanc->id}: {$e->getMessage()}\n";
        $erros++;
    }
}

echo "\n════════════════════════════════════════════════════════\n";
echo "📊 RESULTADO:\n";
echo "   ✅ Lançamentos removidos: {$removidos}\n";
if ($erros > 0) {
    echo "   ❌ Erros: {$erros}\n";
}
echo "════════════════════════════════════════════════════════\n\n";

echo "🎉 Limpeza concluída!\n";
echo "   Agora quando você pagar uma fatura, os ITENS individuais aparecerão\n";
echo "   como lançamentos na data do pagamento.\n\n";
