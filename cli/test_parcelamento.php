<?php
/**
 * Script para testar o fluxo completo de parcelamento de agendamentos
 */

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/config/config.php';

use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTE DE PARCELAMENTO DE AGENDAMENTOS ===\n\n";

// Buscar um usuário para teste
$userId = DB::table('usuarios')->value('id');
if (!$userId) {
    die("❌ Nenhum usuário encontrado para teste.\n");
}
echo "✅ Usando usuário ID: {$userId}\n\n";

// 1. Testar criação de agendamento parcelado
echo "1. CRIAÇÃO DE AGENDAMENTO PARCELADO\n";
echo str_repeat('-', 50) . "\n";

try {
    $agendamento = Agendamento::create([
        'user_id' => $userId,
        'titulo' => 'Teste Parcelamento ' . date('H:i:s'),
        'tipo' => 'despesa',
        'valor_centavos' => 50000, // R$ 500,00
        'data_pagamento' => date('Y-m-d H:i:s'),
        'proxima_execucao' => date('Y-m-d H:i:s'),
        'eh_parcelado' => true,
        'numero_parcelas' => 3,
        'parcela_atual' => 1,
        'status' => 'pendente',
    ]);
    
    echo "✅ Agendamento criado: ID {$agendamento->id}\n";
    echo "   Título: {$agendamento->titulo}\n";
    echo "   Parcelado: " . ($agendamento->eh_parcelado ? 'Sim' : 'Não') . "\n";
    echo "   Parcelas: {$agendamento->parcela_atual}/{$agendamento->numero_parcelas}\n\n";
    
} catch (Exception $e) {
    die("❌ Erro ao criar agendamento: " . $e->getMessage() . "\n");
}

// 2. Testar execução (simular pagamento de parcela)
echo "2. SIMULAÇÃO DE EXECUÇÃO (PAGAMENTO DE PARCELA)\n";
echo str_repeat('-', 50) . "\n";

try {
    $service = new AgendamentoService();
    
    // Primeira parcela
    echo "Executando parcela 1...\n";
    $resultado1 = $service->executarAgendamento($agendamento);
    
    $agendamento->refresh();
    echo "✅ Parcela 1 paga!\n";
    echo "   Status: {$agendamento->status}\n";
    echo "   Parcela atual: {$agendamento->parcela_atual}\n";
    echo "   Próxima data: {$agendamento->data_pagamento}\n";
    echo "   Lançamento criado: ID " . ($resultado1['lancamento']->id ?? 'N/A') . "\n\n";
    
    // Segunda parcela
    echo "Executando parcela 2...\n";
    $agendamento->status = 'pendente'; // Resetar status para simular
    $agendamento->save();
    
    $resultado2 = $service->executarAgendamento($agendamento);
    
    $agendamento->refresh();
    echo "✅ Parcela 2 paga!\n";
    echo "   Status: {$agendamento->status}\n";
    echo "   Parcela atual: {$agendamento->parcela_atual}\n";
    echo "   Próxima data: {$agendamento->data_pagamento}\n\n";
    
    // Terceira parcela (última)
    echo "Executando parcela 3 (última)...\n";
    $agendamento->status = 'pendente';
    $agendamento->save();
    
    $resultado3 = $service->executarAgendamento($agendamento);
    
    $agendamento->refresh();
    echo "✅ Parcela 3 paga (última)!\n";
    echo "   Status: {$agendamento->status}\n";
    echo "   Finalizado: " . (($resultado3['finalizado'] ?? false) ? 'Sim' : 'Não') . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro na execução: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n\n";
}

// 3. Limpar dados de teste
echo "3. LIMPEZA\n";
echo str_repeat('-', 50) . "\n";

try {
    // Deletar lançamentos criados
    $lancamentosDeleted = DB::table('lancamentos')
        ->where('descricao', 'LIKE', '%Teste Parcelamento%')
        ->delete();
    echo "✅ {$lancamentosDeleted} lançamento(s) de teste deletado(s)\n";
    
    // Deletar agendamento
    if (isset($agendamento)) {
        $agendamento->delete();
        echo "✅ Agendamento de teste deletado\n";
    }
    
} catch (Exception $e) {
    echo "⚠️ Erro na limpeza: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
