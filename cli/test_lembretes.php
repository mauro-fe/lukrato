<?php
/**
 * Script para testar o sistema de lembretes/notificações de agendamentos
 */

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/config/config.php';

use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTE DO SISTEMA DE LEMBRETES ===\n\n";

// Buscar um usuário para teste
$userId = DB::table('usuarios')->value('id');
if (!$userId) {
    die("❌ Nenhum usuário encontrado para teste.\n");
}
echo "✅ Usando usuário ID: {$userId}\n\n";

// =============================================================================
// 1. TESTE: Criar agendamento com lembrete de 1 hora
// =============================================================================
echo "1. CRIAR AGENDAMENTO COM LEMBRETE DE 1 HORA\n";
echo str_repeat('-', 60) . "\n";

$dataPagamento = (new DateTimeImmutable())->modify('+2 hours');
$lembrarSegundos = 3600; // 1 hora = 3600 segundos

$agendamento = Agendamento::create([
    'user_id' => $userId,
    'titulo' => 'Teste Lembrete ' . date('H:i:s'),
    'tipo' => 'despesa',
    'valor_centavos' => 10000,
    'data_pagamento' => $dataPagamento->format('Y-m-d H:i:s'),
    'proxima_execucao' => $dataPagamento->modify("-{$lembrarSegundos} seconds")->format('Y-m-d H:i:s'),
    'lembrar_antes_segundos' => $lembrarSegundos,
    'canal_inapp' => true,
    'canal_email' => false,
    'status' => 'pendente',
    'eh_parcelado' => false,
    'recorrente' => false,
]);

echo "✅ Agendamento criado: ID {$agendamento->id}\n";
echo "   Data pagamento: {$agendamento->data_pagamento}\n";
echo "   Próxima execução: {$agendamento->proxima_execucao}\n";
echo "   Lembrar antes: {$agendamento->lembrar_antes_segundos} segundos (" . ($agendamento->lembrar_antes_segundos / 60) . " minutos)\n";

// Verificar se a diferença está correta
$diffSeconds = $dataPagamento->getTimestamp() - (new DateTimeImmutable($agendamento->proxima_execucao))->getTimestamp();
echo "   Diferença calculada: {$diffSeconds} segundos\n";

if ($diffSeconds == $lembrarSegundos) {
    echo "   ✅ Diferença está CORRETA!\n\n";
} else {
    echo "   ❌ ERRO: Diferença deveria ser {$lembrarSegundos} segundos!\n\n";
}

// =============================================================================
// 2. TESTE: Simular execução de agendamento recorrente
// =============================================================================
echo "2. SIMULAR EXECUÇÃO DE AGENDAMENTO RECORRENTE\n";
echo str_repeat('-', 60) . "\n";

$agendamentoRecorrente = Agendamento::create([
    'user_id' => $userId,
    'titulo' => 'Conta Recorrente Teste ' . date('H:i:s'),
    'tipo' => 'despesa',
    'valor_centavos' => 20000,
    'data_pagamento' => date('Y-m-d H:i:s'),
    'proxima_execucao' => (new DateTimeImmutable())->modify("-{$lembrarSegundos} seconds")->format('Y-m-d H:i:s'),
    'lembrar_antes_segundos' => $lembrarSegundos,
    'canal_inapp' => true,
    'canal_email' => false,
    'status' => 'pendente',
    'recorrente' => true,
    'recorrencia_freq' => 'mensal',
    'recorrencia_intervalo' => 1,
]);

echo "   Agendamento recorrente criado: ID {$agendamentoRecorrente->id}\n";
echo "   Lembrar antes: {$agendamentoRecorrente->lembrar_antes_segundos} segundos\n";

// Executar (marcar como pago)
$service = new AgendamentoService();
$resultado = $service->executarAgendamento($agendamentoRecorrente);

$agendamentoRecorrente->refresh();

echo "   ✅ Executado! Nova data de pagamento: {$agendamentoRecorrente->data_pagamento}\n";
echo "   Nova próxima execução: {$agendamentoRecorrente->proxima_execucao}\n";

// Verificar se a próxima execução foi calculada corretamente
$novaDataPagamento = new DateTimeImmutable($agendamentoRecorrente->data_pagamento);
$novaProximaExecucao = new DateTimeImmutable($agendamentoRecorrente->proxima_execucao);
$diffNovo = $novaDataPagamento->getTimestamp() - $novaProximaExecucao->getTimestamp();

echo "   Diferença nova: {$diffNovo} segundos\n";

if ($diffNovo == $lembrarSegundos) {
    echo "   ✅ Próxima execução recalculada CORRETAMENTE!\n\n";
} else {
    echo "   ❌ ERRO: Diferença deveria ser {$lembrarSegundos} segundos!\n\n";
}

// =============================================================================
// 3. TESTE: Simular execução de agendamento parcelado
// =============================================================================
echo "3. SIMULAR EXECUÇÃO DE AGENDAMENTO PARCELADO\n";
echo str_repeat('-', 60) . "\n";

$agendamentoParcelado = Agendamento::create([
    'user_id' => $userId,
    'titulo' => 'Compra Parcelada Teste ' . date('H:i:s'),
    'tipo' => 'despesa',
    'valor_centavos' => 30000,
    'data_pagamento' => date('Y-m-d H:i:s'),
    'proxima_execucao' => (new DateTimeImmutable())->modify("-{$lembrarSegundos} seconds")->format('Y-m-d H:i:s'),
    'lembrar_antes_segundos' => $lembrarSegundos,
    'canal_inapp' => true,
    'canal_email' => false,
    'status' => 'pendente',
    'eh_parcelado' => true,
    'numero_parcelas' => 3,
    'parcela_atual' => 1,
]);

echo "   Agendamento parcelado criado: ID {$agendamentoParcelado->id}\n";
echo "   Parcela: {$agendamentoParcelado->parcela_atual}/{$agendamentoParcelado->numero_parcelas}\n";
echo "   Lembrar antes: {$agendamentoParcelado->lembrar_antes_segundos} segundos\n";

// Executar (pagar parcela 1)
$resultadoParc = $service->executarAgendamento($agendamentoParcelado);

$agendamentoParcelado->refresh();

echo "   ✅ Parcela 1 paga! Nova data de pagamento: {$agendamentoParcelado->data_pagamento}\n";
echo "   Nova próxima execução: {$agendamentoParcelado->proxima_execucao}\n";
echo "   Parcela atual: {$agendamentoParcelado->parcela_atual}\n";

// Verificar
$novaDataPagParc = new DateTimeImmutable($agendamentoParcelado->data_pagamento);
$novaProxExecParc = new DateTimeImmutable($agendamentoParcelado->proxima_execucao);
$diffParc = $novaDataPagParc->getTimestamp() - $novaProxExecParc->getTimestamp();

echo "   Diferença nova: {$diffParc} segundos\n";

if ($diffParc == $lembrarSegundos) {
    echo "   ✅ Próxima execução recalculada CORRETAMENTE!\n\n";
} else {
    echo "   ❌ ERRO: Diferença deveria ser {$lembrarSegundos} segundos!\n\n";
}

// =============================================================================
// 4. LIMPEZA
// =============================================================================
echo "4. LIMPEZA\n";
echo str_repeat('-', 60) . "\n";

// Deletar lançamentos criados
$lancamentosDeleted = DB::table('lancamentos')
    ->where('descricao', 'LIKE', '%Teste Lembrete%')
    ->orWhere('descricao', 'LIKE', '%Conta Recorrente Teste%')
    ->orWhere('descricao', 'LIKE', '%Compra Parcelada Teste%')
    ->delete();
echo "✅ {$lancamentosDeleted} lançamento(s) de teste deletado(s)\n";

// Deletar agendamentos
Agendamento::destroy([$agendamento->id, $agendamentoRecorrente->id, $agendamentoParcelado->id]);
echo "✅ Agendamentos de teste deletados\n";

echo "\n" . str_repeat('=', 60) . "\n";
echo "✅ TESTE DE LEMBRETES CONCLUÍDO!\n";
echo str_repeat('=', 60) . "\n";
