<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Application\Models\UserSubscription;
use Illuminate\Database\Capsule\Manager as DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE LEMBRETES E NOTIFICAÇÕES ===\n";
echo "Data/Hora atual: " . date('Y-m-d H:i:s') . "\n";
echo "Timezone: " . date_default_timezone_get() . "\n\n";

// ============================================
// 1. AGENDAMENTOS
// ============================================
echo "=== 1. AGENDAMENTOS ===\n\n";

$agendamentos = Agendamento::with(['usuario:id,nome,email'])
    ->whereIn('status', ['pendente', 'notificado'])
    ->whereNull('notificado_em')
    ->orderBy('data_pagamento', 'asc')
    ->limit(30)
    ->get();

echo "Total de agendamentos pendentes/notificados sem notificado_em: " . count($agendamentos) . "\n\n";

$now = new \DateTimeImmutable('now');
$windowStart = $now->modify('-5 minutes');
$windowEnd = $now->modify('+10 minutes');

echo "Janela de notificação:\n";
echo "  - Início: " . $windowStart->format('Y-m-d H:i:s') . "\n";
echo "  - Fim: " . $windowEnd->format('Y-m-d H:i:s') . "\n\n";

echo "Detalhes dos agendamentos:\n";
echo str_repeat('-', 120) . "\n";
printf("%-5s %-30s %-20s %-12s %-20s %-15s %-10s\n", 
    "ID", "Título", "Data Pagamento", "Lembrar(s)", "Horário Lembrete", "Status Janela", "Canais");
echo str_repeat('-', 120) . "\n";

foreach ($agendamentos as $ag) {
    $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
        ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
        : new \DateTimeImmutable((string) $ag->data_pagamento);
    
    $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
    $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;
    $reminderTime = (new \DateTimeImmutable())->setTimestamp($reminderTimestamp);
    
    // Verifica se está na janela
    $inWindow = $reminderTimestamp >= $windowStart->getTimestamp() && $reminderTimestamp <= $windowEnd->getTimestamp();
    $isPast = $reminderTimestamp < $windowStart->getTimestamp();
    $isFuture = $reminderTimestamp > $windowEnd->getTimestamp();
    
    if ($inWindow) {
        $windowStatus = "✅ NA JANELA";
    } elseif ($isPast) {
        $windowStatus = "⏪ PASSADO";
    } else {
        $windowStatus = "⏩ FUTURO";
    }
    
    $canais = [];
    if ($ag->canal_inapp) $canais[] = 'app';
    if ($ag->canal_email) $canais[] = 'email';
    $canaisStr = implode(',', $canais) ?: 'nenhum';
    
    printf("%-5d %-30s %-20s %-12d %-20s %-15s %-10s\n",
        $ag->id,
        mb_substr($ag->titulo, 0, 28),
        $pagamento->format('Y-m-d H:i'),
        $leadSeconds,
        $reminderTime->format('Y-m-d H:i'),
        $windowStatus,
        $canaisStr
    );
}

echo str_repeat('-', 120) . "\n\n";

// ============================================
// 2. NOTIFICAÇÕES RECENTES
// ============================================
echo "=== 2. ÚLTIMAS 20 NOTIFICAÇÕES CRIADAS ===\n\n";

$notificacoes = Notificacao::orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

foreach ($notificacoes as $n) {
    $status = $n->lida ? '📖' : '🔔';
    echo "  {$status} [{$n->created_at}] ({$n->tipo}) {$n->titulo}\n";
    echo "     User: {$n->user_id} | Mensagem: " . mb_substr($n->mensagem ?? '', 0, 60) . "\n\n";
}

// ============================================
// 3. ASSINATURAS PRO
// ============================================
echo "=== 3. ASSINATURAS PRO (verificando vencimentos) ===\n\n";

try {
    $subscriptions = UserSubscription::with('user:id,nome,email')
        ->where('status', 'active')
        ->orderBy('ends_at', 'asc')
        ->limit(20)
        ->get();

    echo "Total de assinaturas ativas: " . count($subscriptions) . "\n\n";

    foreach ($subscriptions as $sub) {
        $endsAt = $sub->ends_at instanceof \DateTimeInterface
            ? $sub->ends_at->format('Y-m-d')
            : $sub->ends_at;
        
        $daysLeft = (int) ((strtotime($endsAt) - time()) / 86400);
        $userName = $sub->user->nome ?? 'N/A';
        
        $status = $daysLeft <= 0 ? '❌ VENCIDA' : ($daysLeft <= 7 ? '⚠️ VENCENDO' : '✅ OK');
        
        echo "  {$status} User: {$userName} (ID: {$sub->user_id}) | Plano: {$sub->plan_id} | Expira: {$endsAt} ({$daysLeft} dias)\n";
    }
} catch (\Throwable $e) {
    echo "  Erro ao buscar assinaturas: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================
// 4. VERIFICAR CONFIGURAÇÃO DE E-MAIL
// ============================================
echo "=== 4. CONFIGURAÇÃO DE E-MAIL ===\n\n";

try {
    $mailService = new \Application\Services\MailService();
    echo "  Mail Service configurado: " . ($mailService->isConfigured() ? 'SIM ✅' : 'NÃO ❌') . "\n";
} catch (\Throwable $e) {
    echo "  Erro ao verificar serviço de email: " . $e->getMessage() . "\n";
}

// ============================================
// 5. VERIFICAR TOKEN DO SCHEDULER
// ============================================
echo "\n=== 5. CONFIGURAÇÃO DO SCHEDULER ===\n\n";

$schedulerToken = $_ENV['SCHEDULER_TOKEN'] ?? getenv('SCHEDULER_TOKEN') ?: null;
if ($schedulerToken) {
    echo "  SCHEDULER_TOKEN: configurado ✅ (" . substr($schedulerToken, 0, 6) . "...)\n";
} else {
    echo "  SCHEDULER_TOKEN: NÃO CONFIGURADO ❌\n";
}

// ============================================
// 6. RESUMO E RECOMENDAÇÕES
// ============================================
echo "\n=== 6. RESUMO E RECOMENDAÇÕES ===\n\n";

$agendasNaJanela = $agendamentos->filter(function($ag) use ($windowStart, $windowEnd) {
    $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
        ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
        : new \DateTimeImmutable((string) $ag->data_pagamento);
    
    $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
    $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;
    
    return $reminderTimestamp >= $windowStart->getTimestamp() && $reminderTimestamp <= $windowEnd->getTimestamp();
});

$agendasPassadas = $agendamentos->filter(function($ag) use ($windowStart) {
    $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
        ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
        : new \DateTimeImmutable((string) $ag->data_pagamento);
    
    $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
    $reminderTimestamp = $pagamento->getTimestamp() - $leadSeconds;
    
    return $reminderTimestamp < $windowStart->getTimestamp();
});

echo "Agendamentos na janela atual: " . count($agendasNaJanela) . "\n";
echo "Agendamentos com lembrete no passado (perdidos): " . count($agendasPassadas) . "\n";

if (count($agendasPassadas) > 0) {
    echo "\n⚠️  PROBLEMA: Existem " . count($agendasPassadas) . " agendamentos cujo horário de lembrete já passou.\n";
    echo "   Isso pode significar que:\n";
    echo "   1. O cron não estava rodando no horário correto\n";
    echo "   2. Os agendamentos foram criados com data_pagamento no passado\n";
    echo "   3. O lembrar_antes_segundos está muito alto\n\n";
    
    echo "   Sugestão: Você pode querer notificar esses agendamentos manualmente ou\n";
    echo "   ajustar a lógica para enviar notificações mesmo para lembretes passados.\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
