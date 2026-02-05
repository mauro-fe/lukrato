<?php
/**
 * Script para testar todas as ações de agendamentos
 */

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/config/config.php';

use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTE COMPLETO DE AGENDAMENTOS ===\n\n";

// Buscar um usuário para teste
$userId = DB::table('usuarios')->value('id');
if (!$userId) {
    die("❌ Nenhum usuário encontrado para teste.\n");
}
echo "✅ Usando usuário ID: {$userId}\n\n";

$testAgendamentoId = null;

// =============================================================================
// 1. TESTE DE CRIAÇÃO
// =============================================================================
echo "1. TESTE DE CRIAÇÃO\n";
echo str_repeat('-', 60) . "\n";

try {
    $agendamento = Agendamento::create([
        'user_id' => $userId,
        'titulo' => 'Teste Completo ' . date('H:i:s'),
        'tipo' => 'despesa',
        'valor_centavos' => 15000, // R$ 150,00
        'data_pagamento' => date('Y-m-d H:i:s'),
        'proxima_execucao' => date('Y-m-d H:i:s'),
        'status' => 'pendente',
        'recorrente' => false,
        'eh_parcelado' => false,
    ]);
    
    $testAgendamentoId = $agendamento->id;
    echo "✅ Agendamento criado: ID {$agendamento->id}\n";
    echo "   Título: {$agendamento->titulo}\n";
    echo "   Valor: R$ " . number_format($agendamento->valor_centavos / 100, 2, ',', '.') . "\n";
    echo "   Status: {$agendamento->status}\n\n";
    
} catch (Exception $e) {
    die("❌ Erro ao criar: " . $e->getMessage() . "\n");
}

// =============================================================================
// 2. TESTE DE EDIÇÃO (UPDATE)
// =============================================================================
echo "2. TESTE DE EDIÇÃO\n";
echo str_repeat('-', 60) . "\n";

try {
    $agendamento = Agendamento::find($testAgendamentoId);
    
    $agendamento->update([
        'titulo' => 'Teste Editado ' . date('H:i:s'),
        'valor_centavos' => 20000, // R$ 200,00
        'descricao' => 'Descrição adicionada via edição',
    ]);
    
    $agendamento->refresh();
    
    echo "✅ Agendamento editado!\n";
    echo "   Novo título: {$agendamento->titulo}\n";
    echo "   Novo valor: R$ " . number_format($agendamento->valor_centavos / 100, 2, ',', '.') . "\n";
    echo "   Descrição: {$agendamento->descricao}\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao editar: " . $e->getMessage() . "\n\n";
}

// =============================================================================
// 3. TESTE DE CANCELAMENTO
// =============================================================================
echo "3. TESTE DE CANCELAMENTO\n";
echo str_repeat('-', 60) . "\n";

try {
    $agendamento = Agendamento::find($testAgendamentoId);
    
    if ($agendamento->status !== 'pendente') {
        echo "⚠️ Agendamento não está pendente, ajustando...\n";
        $agendamento->update(['status' => 'pendente']);
    }
    
    $agendamento->update([
        'status' => 'cancelado',
        'concluido_em' => null,
    ]);
    
    $agendamento->refresh();
    
    echo "✅ Agendamento cancelado!\n";
    echo "   Status: {$agendamento->status}\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao cancelar: " . $e->getMessage() . "\n\n";
}

// =============================================================================
// 4. TESTE DE REATIVAÇÃO
// =============================================================================
echo "4. TESTE DE REATIVAÇÃO\n";
echo str_repeat('-', 60) . "\n";

try {
    $agendamento = Agendamento::find($testAgendamentoId);
    
    if ($agendamento->status !== 'cancelado') {
        echo "⚠️ Agendamento não está cancelado, ajustando...\n";
        $agendamento->update(['status' => 'cancelado']);
    }
    
    $agendamento->update([
        'status' => 'pendente',
        'concluido_em' => null,
    ]);
    
    $agendamento->refresh();
    
    echo "✅ Agendamento reativado!\n";
    echo "   Status: {$agendamento->status}\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao reativar: " . $e->getMessage() . "\n\n";
}

// =============================================================================
// 5. TESTE DE MARCAR COMO PAGO (EXECUTAR)
// =============================================================================
echo "5. TESTE DE MARCAR COMO PAGO (EXECUTAR)\n";
echo str_repeat('-', 60) . "\n";

try {
    $agendamento = Agendamento::find($testAgendamentoId);
    
    if ($agendamento->status !== 'pendente') {
        echo "⚠️ Agendamento não está pendente, ajustando...\n";
        $agendamento->update(['status' => 'pendente']);
        $agendamento->refresh();
    }
    
    // Buscar uma conta do usuário
    $contaId = DB::table('contas')->where('user_id', $userId)->value('id');
    
    $service = new AgendamentoService();
    $resultado = $service->executarAgendamento($agendamento, $contaId, 'pix');
    
    $agendamento->refresh();
    
    echo "✅ Agendamento executado (pago)!\n";
    echo "   Lançamento criado: ID " . ($resultado['lancamento']->id ?? 'N/A') . "\n";
    echo "   Conta usada: " . ($contaId ?: 'Nenhuma') . "\n";
    echo "   Forma de pagamento: PIX\n";
    echo "   Status do agendamento: {$agendamento->status}\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao executar: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n\n";
}

// =============================================================================
// 6. LIMPEZA
// =============================================================================
echo "6. LIMPEZA DE DADOS DE TESTE\n";
echo str_repeat('-', 60) . "\n";

try {
    // Deletar lançamentos criados
    $lancamentosDeleted = DB::table('lancamentos')
        ->where('descricao', 'LIKE', '%Teste Completo%')
        ->orWhere('descricao', 'LIKE', '%Teste Editado%')
        ->delete();
    echo "✅ {$lancamentosDeleted} lançamento(s) de teste deletado(s)\n";
    
    // Deletar agendamento
    if ($testAgendamentoId) {
        Agendamento::destroy($testAgendamentoId);
        echo "✅ Agendamento de teste deletado (ID: {$testAgendamentoId})\n";
    }
    
} catch (Exception $e) {
    echo "⚠️ Erro na limpeza: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✅ TODOS OS TESTES CONCLUÍDOS COM SUCESSO!\n";
echo str_repeat('=', 60) . "\n";
