<?php

/**
 * Script para testar novo sistema de saldo inicial
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Services\ContaService;
use Application\Services\ContaBalanceService;
use Application\DTO\CreateContaDTO;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n=== Teste do Novo Sistema de Saldo Inicial ===\n\n";

try {
    $userId = 1; // MAURO

    // Teste 1: Verificar contas existentes
    echo "📊 Teste 1: Verificar contas com saldo inicial\n";
    $contas = Conta::where('user_id', $userId)
        ->where('saldo_inicial', '!=', 0)
        ->get(['id', 'nome', 'saldo_inicial']);

    echo "Encontradas {$contas->count()} contas com saldo inicial:\n";
    foreach ($contas as $conta) {
        echo "  - {$conta->nome}: R$ " . number_format($conta->saldo_inicial, 2, ',', '.') . "\n";
    }

    // Teste 2: Verificar se ainda existem lançamentos de saldo inicial
    echo "\n📋 Teste 2: Verificar lançamentos de saldo inicial\n";
    $lancamentosSaldo = Lancamento::where('user_id', $userId)
        ->where('eh_saldo_inicial', 1)
        ->count();

    if ($lancamentosSaldo > 0) {
        echo "⚠️  ATENÇÃO: Ainda existem {$lancamentosSaldo} lançamentos de saldo inicial!\n";
        echo "Execute: php cli/cleanup_saldo_inicial.php para removê-los\n";
    } else {
        echo "✅ Nenhum lançamento de saldo inicial encontrado (correto!)\n";
    }

    // Teste 3: Criar conta nova com saldo inicial
    echo "\n➕ Teste 3: Criar nova conta com saldo inicial\n";

    DB::beginTransaction();

    $service = new ContaService();
    $dto = CreateContaDTO::fromArray([
        'nome' => 'Conta Teste Saldo ' . date('His'),
        'saldo_inicial' => 1500.50,
        'cor' => '#00FF00',
        'tipo_conta' => 'conta_corrente',
        'moeda' => 'BRL',
        'ativo' => true
    ], $userId);

    $resultado = $service->criarConta($dto);

    if ($resultado['success']) {
        $contaId = $resultado['id'];
        echo "✅ Conta criada: ID {$contaId}\n";

        // Verificar se o campo foi salvo
        $contaNova = Conta::find($contaId);
        echo "   Saldo inicial salvo: R$ " . number_format($contaNova->saldo_inicial, 2, ',', '.') . "\n";

        // Verificar se NÃO foi criado lançamento
        $temLancamento = Lancamento::where('conta_id', $contaId)
            ->where('eh_saldo_inicial', 1)
            ->exists();

        if ($temLancamento) {
            echo "❌ ERRO: Foi criado lançamento de saldo inicial!\n";
        } else {
            echo "✅ Correto: Não foi criado lançamento de saldo inicial\n";
        }
    } else {
        echo "❌ Erro ao criar conta: {$resultado['message']}\n";
    }

    DB::rollBack(); // Desfazer criação de teste
    echo "   (Criação desfeita - era só teste)\n";

    // Teste 4: Calcular saldos
    if ($contas->count() > 0) {
        echo "\n💰 Teste 4: Calcular saldos com ContaBalanceService\n";

        $contaIds = $contas->pluck('id')->toArray();
        $mes = date('Y-m');

        $balanceService = new ContaBalanceService($userId, $contaIds, $mes);
        $saldosIniciais = $balanceService->getInitialBalances();
        $saldosFinais = $balanceService->calculateFinalBalances($saldosIniciais);

        foreach ($contas->take(3) as $conta) {
            $saldo = $saldosFinais[$conta->id] ?? null;
            if ($saldo) {
                echo "  - {$conta->nome}:\n";
                echo "    Saldo Inicial: R$ " . number_format($saldo['saldoInicial'], 2, ',', '.') . "\n";
                echo "    Saldo Atual:   R$ " . number_format($saldo['saldoAtual'], 2, ',', '.') . "\n";
            }
        }
    }

    echo "\n✅ Todos os testes concluídos!\n";
    echo "\n📝 PRÓXIMOS PASSOS:\n";
    echo "1. Se ainda há lançamentos de saldo inicial, execute: php cli/cleanup_saldo_inicial.php\n";
    echo "2. Teste criar contas no frontend e verifique se aparecem corretamente\n";
    echo "3. Verifique se os relatórios não incluem mais 'Saldo inicial' como lançamento\n\n";
} catch (\Throwable $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    exit(1);
}
