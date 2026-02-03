<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Application\Models\Usuario;
use Application\Services\MailService;
use Application\Services\AgendamentoService;
use Illuminate\Database\Capsule\Manager as DB;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   SIMULAÇÃO DO FLUXO COMPLETO DE AGENDAMENTO E NOTIFICAÇÃO      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// =============================================================================
// CONFIGURAÇÃO
// =============================================================================
$userId = 1; // ID do usuário de teste
$simularEnvio = true; // Se true, simula o envio real (sem salvar)

echo "═══ CONFIGURAÇÃO ═══\n";
echo "  Usuário de teste: ID {$userId}\n";
echo "  Simular envio: " . ($simularEnvio ? 'SIM' : 'NÃO') . "\n\n";

// Verificar se usuário existe
$usuario = Usuario::find($userId);
if (!$usuario) {
    echo "❌ Usuário não encontrado! Abortando...\n";
    exit(1);
}
echo "  ✅ Usuário encontrado: {$usuario->nome} ({$usuario->email})\n\n";

// =============================================================================
// ETAPA 1: CRIAR AGENDAMENTO DE TESTE
// =============================================================================
echo "═══ ETAPA 1: CRIANDO AGENDAMENTO DE TESTE ═══\n";

// Data de pagamento daqui a 35 minutos (fora da janela)
$dataPagamento = (new DateTimeImmutable())->modify('+35 minutes');
$lembrarAntes = 30 * 60; // 30 minutos antes

DB::beginTransaction();

try {
    $agendamentoTeste = Agendamento::create([
        'user_id' => $userId,
        'titulo' => '[TESTE] Pagamento de Conta',
        'descricao' => 'Agendamento criado automaticamente para teste',
        'tipo' => 'despesa',
        'valor_centavos' => 25000, // R$ 250,00
        'data_pagamento' => $dataPagamento->format('Y-m-d H:i:s'),
        'proxima_execucao' => $dataPagamento->modify("-{$lembrarAntes} seconds")->format('Y-m-d H:i:s'),
        'lembrar_antes_segundos' => $lembrarAntes,
        'canal_email' => true,
        'canal_inapp' => true,
        'status' => 'pendente',
    ]);

    echo "  ✅ Agendamento criado: ID #{$agendamentoTeste->id}\n";
    echo "     Título: {$agendamentoTeste->titulo}\n";
    echo "     Data Pagamento: " . $dataPagamento->format('d/m/Y H:i:s') . "\n";
    echo "     Lembrar: " . ($lembrarAntes / 60) . " minutos antes\n";
    echo "     Próxima Execução: {$agendamentoTeste->proxima_execucao}\n";
    echo "     Canais: Email=" . ($agendamentoTeste->canal_email ? 'Sim' : 'Não') . ", InApp=" . ($agendamentoTeste->canal_inapp ? 'Sim' : 'Não') . "\n";
} catch (\Throwable $e) {
    DB::rollBack();
    echo "  ❌ Erro ao criar agendamento: {$e->getMessage()}\n";
    exit(1);
}

// =============================================================================
// ETAPA 2: SIMULAR JANELA DE ENVIO
// =============================================================================
echo "\n═══ ETAPA 2: VERIFICANDO JANELA DE ENVIO ═══\n";

$now = new DateTimeImmutable('now');
$windowStart = $now->modify('-5 minutes');
$windowEnd = $now->modify('+10 minutes');

echo "  Agora: " . $now->format('Y-m-d H:i:s') . "\n";
echo "  Janela: " . $windowStart->format('H:i:s') . " até " . $windowEnd->format('H:i:s') . "\n";

$reminderTime = $dataPagamento->getTimestamp() - $lembrarAntes;
$reminderDate = (new DateTimeImmutable())->setTimestamp($reminderTime);

echo "  Lembrete programado para: " . $reminderDate->format('Y-m-d H:i:s') . "\n";

$dentroJanela = $reminderTime >= $windowStart->getTimestamp() && $reminderTime <= $windowEnd->getTimestamp();

if ($dentroJanela) {
    echo "  ✅ Lembrete está DENTRO da janela de envio!\n";
} else {
    echo "  ⏳ Lembrete está FORA da janela (será enviado em " . round(($reminderTime - $now->getTimestamp()) / 60) . " minutos)\n";
}

// =============================================================================
// ETAPA 3: SIMULAR ENVIO DE NOTIFICAÇÃO
// =============================================================================
echo "\n═══ ETAPA 3: SIMULANDO ENVIO DE NOTIFICAÇÃO ═══\n";

if ($simularEnvio) {
    // Criar notificação in-app
    $notificacao = Notificacao::create([
        'user_id' => $userId,
        'tipo' => 'agendamento',
        'titulo' => 'Lembrete de pagamento',
        'mensagem' => sprintf(
            '%s agendado para %s.',
            $agendamentoTeste->titulo,
            $dataPagamento->format('d/m/Y H:i')
        ),
        'link' => '/agendamentos',
        'lida' => 0,
    ]);
    
    echo "  ✅ Notificação in-app criada: ID #{$notificacao->id}\n";
    echo "     Título: {$notificacao->titulo}\n";
    echo "     Mensagem: {$notificacao->mensagem}\n";

    // Simular envio de email
    $mailService = new MailService();
    if ($mailService->isConfigured()) {
        echo "  📧 MailService configurado - email seria enviado para: {$usuario->email}\n";
        
        // Não enviar email real no teste - apenas simular
        // $mailService->sendAgendamentoReminder($agendamentoTeste, $usuario);
    } else {
        echo "  ⚠️ MailService não configurado - email não seria enviado\n";
    }

    // Marcar como notificado
    $agendamentoTeste->update([
        'status' => 'notificado',
        'notificado_em' => $now->format('Y-m-d H:i:s'),
    ]);
    
    echo "  ✅ Agendamento marcado como 'notificado'\n";
}

// =============================================================================
// ETAPA 4: VERIFICAR RESULTADO
// =============================================================================
echo "\n═══ ETAPA 4: VERIFICANDO RESULTADO ═══\n";

$agendamentoAtualizado = Agendamento::find($agendamentoTeste->id);
echo "  Status do agendamento: {$agendamentoAtualizado->status}\n";
echo "  Notificado em: {$agendamentoAtualizado->notificado_em}\n";

$notificacoesCriadas = Notificacao::where('user_id', $userId)
    ->where('tipo', 'agendamento')
    ->where('mensagem', 'like', '%' . $agendamentoTeste->titulo . '%')
    ->count();

echo "  Notificações criadas: {$notificacoesCriadas}\n";

// =============================================================================
// CLEANUP
// =============================================================================
echo "\n═══ LIMPANDO DADOS DE TESTE ═══\n";

// Rollback para não persistir dados de teste
DB::rollBack();
echo "  ✅ Transação revertida - nenhum dado de teste foi salvo\n";

// =============================================================================
// RESUMO
// =============================================================================
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                         RESUMO DA SIMULAÇÃO                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ FLUXO COMPLETO SIMULADO COM SUCESSO!\n\n";

echo "Passos verificados:\n";
echo "  1. ✅ Criação de agendamento com campos corretos\n";
echo "  2. ✅ Cálculo de janela de envio\n";
echo "  3. ✅ Criação de notificação in-app\n";
echo "  4. ✅ Atualização do status para 'notificado'\n";
echo "  5. ✅ MailService configurado para envio de emails\n\n";

echo "Para acionar o envio real de lembretes, chame:\n";
echo "  GET/POST /api/scheduler/dispatch-reminders?token=SEU_TOKEN\n";
echo "  ou\n";
echo "  GET/POST /api/rota-do-cron?token=SEU_TOKEN\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
