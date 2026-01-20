<?php

/**
 * Corrigir pagamentos de fatura - criar lançamentos retroativos
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Categoria;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n🔧 CORREÇÃO: CRIAR LANÇAMENTOS RETROATIVOS DE PAGAMENTO\n";
echo "════════════════════════════════════════════════════════\n\n";

// Buscar todos os itens pagos
$itensPagos = FaturaCartaoItem::where('pago', true)->get();

echo "Total de itens pagos: {$itensPagos->count()}\n\n";

// Agrupar por cartão, data e usuário
$gruposPagamento = [];

foreach ($itensPagos as $item) {
    $key = "{$item->cartao_credito_id}|{$item->user_id}|" . date('Y-m-d', strtotime($item->data_pagamento));

    if (!isset($gruposPagamento[$key])) {
        $gruposPagamento[$key] = [
            'cartao_id' => $item->cartao_credito_id,
            'user_id' => $item->user_id,
            'data_pagamento' => date('Y-m-d', strtotime($item->data_pagamento)),
            'itens' => [],
            'total' => 0,
        ];
    }

    $gruposPagamento[$key]['itens'][] = $item;
    $gruposPagamento[$key]['total'] += $item->valor;
}

echo "Grupos de pagamento identificados: " . count($gruposPagamento) . "\n\n";

$lancamentosCriados = 0;
$lancamentosJaExistentes = 0;
$erros = 0;

foreach ($gruposPagamento as $grupo) {
    $cartao = CartaoCredito::find($grupo['cartao_id']);

    if (!$cartao) {
        echo "⚠️  Cartão ID {$grupo['cartao_id']} não encontrado\n";
        $erros++;
        continue;
    }

    if (!$cartao->conta_id) {
        echo "⚠️  Cartão '{$cartao->nome_cartao}' não está vinculado a uma conta - pulando\n";
        $erros++;
        continue;
    }

    // Verificar se já existe lançamento
    $lancamentoExistente = Lancamento::where('user_id', $grupo['user_id'])
        ->where('conta_id', $cartao->conta_id)
        ->whereDate('data', $grupo['data_pagamento'])
        ->where('descricao', 'like', "%Pagamento Fatura%{$cartao->nome_cartao}%")
        ->first();

    if ($lancamentoExistente) {
        echo "ℹ️  Lançamento já existe para {$cartao->nome_cartao} em {$grupo['data_pagamento']}\n";
        $lancamentosJaExistentes++;
        continue;
    }

    // Buscar ou criar categoria "Pagamento de Cartão"
    $categoria = Categoria::where('user_id', $grupo['user_id'])
        ->where('nome', 'Pagamento de Cartão')
        ->first();

    if (!$categoria) {
        $categoria = Categoria::create([
            'user_id' => $grupo['user_id'],
            'nome' => 'Pagamento de Cartão',
            'tipo' => 'despesa',
            'cor' => '#e67e22',
            'icone' => 'credit-card',
        ]);
    }

    // Criar lançamento retroativo
    try {
        // Extrair mês/ano do primeiro item
        $primeiroItem = $grupo['itens'][0];
        $mes = $primeiroItem->mes_referencia;
        $ano = $primeiroItem->ano_referencia;

        $lancamento = Lancamento::create([
            'user_id' => $grupo['user_id'],
            'conta_id' => $cartao->conta_id,
            'categoria_id' => $categoria->id,
            'tipo' => 'despesa',
            'valor' => $grupo['total'],
            'descricao' => sprintf(
                'Pagamento Fatura %s •••• %s - %02d/%04d',
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $mes,
                $ano
            ),
            'data' => $grupo['data_pagamento'],
            'observacao' => sprintf(
                'Pagamento retroativo: %d item(s) - Valor total: R$ %.2f',
                count($grupo['itens']),
                $grupo['total']
            ),
            'pago' => true,
            'data_pagamento' => $grupo['data_pagamento'],
            'created_at' => $grupo['data_pagamento'], // Data retroativa
            'updated_at' => now(),
        ]);

        echo "✅ Criado lançamento ID {$lancamento->id}: {$cartao->nome_cartao} - R$ {$grupo['total']} ({$grupo['data_pagamento']})\n";
        $lancamentosCriados++;
    } catch (\Exception $e) {
        echo "❌ Erro ao criar lançamento para {$cartao->nome_cartao}: {$e->getMessage()}\n";
        $erros++;
    }
}

echo "\n════════════════════════════════════════════════════════\n";
echo "📊 RESULTADO:\n";
echo "   ✅ Lançamentos criados: {$lancamentosCriados}\n";
echo "   ℹ️  Já existentes: {$lancamentosJaExistentes}\n";
if ($erros > 0) {
    echo "   ❌ Erros: {$erros}\n";
}
echo "════════════════════════════════════════════════════════\n\n";

echo "🎉 Correção concluída!\n";
echo "   Agora os pagamentos devem aparecer na página de lançamentos.\n\n";
