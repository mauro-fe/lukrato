<?php

/**
 * Demo completo de todas as funcionalidades de cartões
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\Parcelamento;
use Application\Models\Categoria;

$userId = isset($argv[1]) ? (int) $argv[1] : 1;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  DEMO COMPLETO - FUNCIONALIDADES DE CARTÕES DE CRÉDITO     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    // Buscar ou criar conta
    $conta = Conta::where('user_id', $userId)->first();

    if (!$conta) {
        echo "❌ Nenhuma conta encontrada. Criando conta padrão...\n";
        $conta = Conta::create([
            'user_id' => $userId,
            'nome' => 'Conta Corrente',
            'tipo' => 'corrente',
            'saldo_inicial' => 5000.00,
            'cor' => '#3498db',
            'instituicao_financeira_id' => 1
        ]);
        echo "✅ Conta criada: {$conta->nome}\n\n";
    }

    echo "🏦 Usando conta: {$conta->nome} (ID: {$conta->id})\n\n";

    // Limpar cartões existentes do usuário (opcional)
    echo "🧹 Limpando cartões antigos...\n";
    CartaoCredito::where('user_id', $userId)->delete();
    echo "✅ Limpo\n\n";

    // Criar 3 cartões com diferentes cenários
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "1️⃣  CARTÃO 1: Limite Crítico (<10%)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $cartao1 = CartaoCredito::create([
        'user_id' => $userId,
        'conta_id' => $conta->id,
        'nome_cartao' => 'Nubank Visa',
        'bandeira' => 'visa',
        'limite_total' => 5000.00,
        'limite_disponivel' => 300.00, // 6% disponível - alerta crítico
        'dia_vencimento' => 10,
        'dia_fechamento' => 3,
        'ultimos_digitos' => '1234',
        'ativo' => true
    ]);

    echo "✅ Cartão criado: {$cartao1->nome_cartao}\n";
    echo "   Limite Total: R$ 5.000,00\n";
    echo "   Limite Disponível: R$ 300,00 (6%) 🔴 CRÍTICO\n\n";

    // Criar lançamentos não pagos que vencem em 2 dias
    $dataVencimento = new DateTime();
    $dataVencimento->setDate(2026, 1, 10); // Vence dia 10

    echo "📝 Criando lançamentos não pagos (fatura vence em 7 dias)...\n";

    Lancamento::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao1->id,
        'descricao' => 'Supermercado Extra',
        'valor' => 450.00,
        'data' => '2026-01-02',
        'tipo' => 'despesa',
        'pago' => false,
        'eh_parcelado' => false,
    ]);

    Lancamento::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao1->id,
        'descricao' => 'Posto de Gasolina',
        'valor' => 200.00,
        'data' => '2026-01-03',
        'tipo' => 'despesa',
        'pago' => false,
        'eh_parcelado' => false,
    ]);

    echo "   ✅ 2 lançamentos criados (Total: R$ 650,00)\n\n";

    // Criar lançamentos pagos (para histórico)
    echo "📜 Criando histórico (lançamentos pagos de dezembro)...\n";

    Lancamento::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao1->id,
        'descricao' => 'Netflix - Dezembro',
        'valor' => 55.90,
        'data' => '2025-12-15',
        'tipo' => 'despesa',
        'pago' => true,
        'data_pagamento' => '2025-12-20',
        'eh_parcelado' => false,
    ]);

    Lancamento::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao1->id,
        'descricao' => 'Amazon - Dezembro',
        'valor' => 234.50,
        'data' => '2025-12-10',
        'tipo' => 'despesa',
        'pago' => true,
        'data_pagamento' => '2025-12-20',
        'eh_parcelado' => false,
    ]);

    echo "   ✅ 2 lançamentos pagos criados (Histórico de dez/2025)\n\n";

    // Cartão 2: Limite de atenção
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "2️⃣  CARTÃO 2: Limite Baixo (15%)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $cartao2 = CartaoCredito::create([
        'user_id' => $userId,
        'conta_id' => $conta->id,
        'nome_cartao' => 'Itaú Mastercard',
        'bandeira' => 'mastercard',
        'limite_total' => 10000.00,
        'limite_disponivel' => 1500.00, // 15% disponível - alerta atenção
        'dia_vencimento' => 5,
        'dia_fechamento' => 28,
        'ultimos_digitos' => '5678',
        'ativo' => true
    ]);

    echo "✅ Cartão criado: {$cartao2->nome_cartao}\n";
    echo "   Limite Total: R$ 10.000,00\n";
    echo "   Limite Disponível: R$ 1.500,00 (15%) 🟠 ATENÇÃO\n\n";

    // Lançamentos com parcelamento
    echo "📝 Criando parcelamento (3x)...\n";

    // Buscar ou criar categoria
    $categoria = \Application\Models\Categoria::where('user_id', $userId)
        ->where('tipo', 'despesa')
        ->first();

    if (!$categoria) {
        $categoria = \Application\Models\Categoria::create([
            'user_id' => $userId,
            'nome' => 'Eletrônicos',
            'tipo' => 'despesa',
            'cor' => '#3498db',
            'icone' => 'laptop'
        ]);
    }

    // Primeiro cria o registro de parcelamento
    $parcelamento = Parcelamento::create([
        'user_id' => $userId,
        'descricao' => 'Notebook Dell',
        'valor_total' => 1800.00,
        'numero_parcelas' => 3,
        'data_inicio' => '2025-12-05', // Começa em dezembro
        'tipo' => 'despesa',
        'categoria_id' => $categoria->id
    ]);

    // Depois cria os lançamentos vinculados COM MESES DIFERENTES
    $dataBase = new DateTime('2025-12-05'); // Dia de vencimento do cartão

    for ($i = 1; $i <= 3; $i++) {
        $dataParcela = clone $dataBase;
        $dataParcela->modify('+' . ($i - 1) . ' month'); // +0, +1, +2 meses

        Lancamento::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao2->id,
            'descricao' => 'Notebook Dell',
            'valor' => 600.00,
            'data' => $dataParcela->format('Y-m-d'),
            'tipo' => 'despesa',
            'pago' => $i == 1 ? true : false, // Primeira paga (dezembro)
            'data_pagamento' => $i == 1 ? '2025-12-10' : null,
            'eh_parcelado' => true,
            'parcelamento_id' => $parcelamento->id,
            'parcela_atual' => $i,
            'total_parcelas' => 3,
            'categoria_id' => $categoria->id
        ]);
    }

    echo "   ✅ Parcelamento criado (3x R$ 600,00)\n";
    echo "      • 1ª parcela (dez/2025) - PAGA ✓\n";
    echo "      • 2ª parcela (jan/2026) - Fatura atual\n";
    echo "      • 3ª parcela (fev/2026) - Próxima fatura\n\n";

    // Cartão 3: Tudo OK
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "3️⃣  CARTÃO 3: Situação Normal\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $cartao3 = CartaoCredito::create([
        'user_id' => $userId,
        'conta_id' => $conta->id,
        'nome_cartao' => 'Bradesco Elo',
        'bandeira' => 'elo',
        'limite_total' => 3000.00,
        'limite_disponivel' => 2200.00, // 73% disponível - OK
        'dia_vencimento' => 25,
        'dia_fechamento' => 18,
        'ultimos_digitos' => '9012',
        'ativo' => true
    ]);

    echo "✅ Cartão criado: {$cartao3->nome_cartao}\n";
    echo "   Limite Total: R$ 3.000,00\n";
    echo "   Limite Disponível: R$ 2.200,00 (73%) 🟢 OK\n\n";

    Lancamento::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao3->id,
        'descricao' => 'Restaurante',
        'valor' => 150.00,
        'data' => '2026-01-03',
        'tipo' => 'despesa',
        'pago' => false,
        'eh_parcelado' => false,
    ]);

    echo "   ✅ 1 lançamento criado\n\n";

    // Resumo final
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✅ DEMO CRIADO COM SUCESSO!\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "📊 RESUMO:\n";
    echo "   • 3 cartões criados\n";
    echo "   • 2 cartões com alertas (1 crítico, 1 atenção)\n";
    echo "   • 6 lançamentos não pagos (faturas pendentes)\n";
    echo "   • 2 lançamentos pagos (histórico)\n";
    echo "   • 1 parcelamento ativo (3x)\n\n";

    echo "🎯 FUNCIONALIDADES PARA TESTAR:\n\n";
    echo "1. ALERTAS\n";
    echo "   → Acesse: /admin/cartoes\n";
    echo "   → Verá 3 alertas no topo:\n";
    echo "     • Vencimento próximo (7 dias)\n";
    echo "     • Limite crítico (Nubank 6%)\n";
    echo "     • Limite baixo (Itaú 15%)\n\n";

    echo "2. HISTÓRICO DE FATURAS\n";
    echo "   → Clique em 'Ver Fatura' no Nubank\n";
    echo "   → Clique no ícone de histórico (relógio)\n";
    echo "   → Verá fatura paga de dez/2025 (R$ 290,40)\n\n";

    echo "3. PARCELAMENTOS NO MODAL\n";
    echo "   → Clique em 'Ver Fatura' no Itaú\n";
    echo "   → Verá seção 'Parcelamentos Ativos'\n";
    echo "   → Notebook Dell - 1/3 parcelas pagas\n\n";

    echo "4. NAVEGAÇÃO ENTRE MESES\n";
    echo "   → No modal de fatura, use as setas\n";
    echo "   → Modal atualiza sem fechar\n\n";

    echo "5. LOADING STATE\n";
    echo "   → Clique em 'Pagar Fatura'\n";
    echo "   → Botão mostra spinner durante processamento\n\n";

    echo "6. VALIDAÇÃO DE INTEGRIDADE\n";
    echo "   → Execute: php cli/validar_integridade_cartoes.php {$userId}\n\n";

    echo "7. ESTATÍSTICAS\n";
    echo "   → Página mostra totais calculados corretamente\n";
    echo "   → Limite Total: R$ 18.000,00\n";
    echo "   → Limite Disponível: R$ 4.000,00\n";
    echo "   → Limite Utilizado: R$ 14.000,00\n\n";

    echo "════════════════════════════════════════════════════════════════\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
