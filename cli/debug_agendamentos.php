<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== ESTRUTURA DA TABELA agendamentos ===\n";
$cols = DB::select('DESCRIBE agendamentos');
foreach ($cols as $col) {
    echo "  {$col->Field} ({$col->Type})\n";
}

echo "\n=== ESTRUTURA DA TABELA notificacoes ===\n";
$cols = DB::select('DESCRIBE notificacoes');
foreach ($cols as $col) {
    echo "  {$col->Field} ({$col->Type})\n";
}

echo "\n=== ESTATÍSTICAS DE AGENDAMENTOS ===\n";
$total = Agendamento::count();
$pendentes = Agendamento::where('status', 'pendente')->count();
$notificados = Agendamento::where('status', 'notificado')->count();
$concluidos = Agendamento::where('status', 'concluido')->count();
$cancelados = Agendamento::where('status', 'cancelado')->count();

echo "  Total: {$total}\n";
echo "  Pendentes: {$pendentes}\n";
echo "  Notificados: {$notificados}\n";
echo "  Concluídos: {$concluidos}\n";
echo "  Cancelados: {$cancelados}\n";

echo "\n=== AGENDAMENTOS PENDENTES COM NOTIFICAÇÃO HABILITADA ===\n";
$agendamentosPendentes = Agendamento::where('status', 'pendente')
    ->where(function($q) {
        $q->where('canal_email', true)->orWhere('canal_inapp', true);
    })
    ->limit(10)
    ->get();

foreach ($agendamentosPendentes as $ag) {
    echo "  ID: {$ag->id}, Titulo: {$ag->titulo}, Data: {$ag->data_pagamento}, Email: " . ($ag->canal_email ? 'Sim' : 'Não') . ", InApp: " . ($ag->canal_inapp ? 'Sim' : 'Não') . ", Lembrar antes: {$ag->lembrar_antes_segundos}s\n";
}

echo "\n=== NOTIFICAÇÕES RECENTES ===\n";
$notificacoes = Notificacao::where('tipo', 'agendamento')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($notificacoes as $n) {
    echo "  ID: {$n->id}, Titulo: {$n->titulo}, User: {$n->user_id}, Lida: " . ($n->lida ? 'Sim' : 'Não') . ", Criada: {$n->created_at}\n";
}

echo "\n=== TESTE DO SERVIÇO DE E-MAIL ===\n";
try {
    $mailService = new \Application\Services\MailService();
    echo "  Mail configurado: " . ($mailService->isConfigured() ? 'SIM' : 'NÃO') . "\n";
} catch (\Throwable $e) {
    echo "  Erro ao verificar serviço de email: {$e->getMessage()}\n";
}

echo "\n=== VERIFICANDO JANELA DE ENVIO ===\n";
$now = new \DateTimeImmutable('now');
$windowStart = $now->modify('-5 minutes');
$windowEnd = $now->modify('+10 minutes');

echo "  Agora: " . $now->format('Y-m-d H:i:s') . "\n";
echo "  Janela início: " . $windowStart->format('Y-m-d H:i:s') . "\n";
echo "  Janela fim: " . $windowEnd->format('Y-m-d H:i:s') . "\n";

$prontos = Agendamento::with(['usuario:id,nome,email'])
    ->whereIn('status', ['pendente', 'notificado'])
    ->whereNull('notificado_em')
    ->get()
    ->filter(function($ag) use ($windowStart, $windowEnd) {
        $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
            : new \DateTimeImmutable((string) $ag->data_pagamento);
        
        $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
        $reminderTime = $pagamento->getTimestamp() - $leadSeconds;
        
        return $reminderTime >= $windowStart->getTimestamp() && $reminderTime <= $windowEnd->getTimestamp();
    });

echo "  Agendamentos prontos para notificação: " . count($prontos) . "\n";
foreach ($prontos as $ag) {
    $pagamento = $ag->data_pagamento instanceof \DateTimeInterface
        ? \DateTimeImmutable::createFromInterface($ag->data_pagamento)
        : new \DateTimeImmutable((string) $ag->data_pagamento);
    $leadSeconds = (int) ($ag->lembrar_antes_segundos ?? 0);
    $reminderTime = (new \DateTimeImmutable())->setTimestamp($pagamento->getTimestamp() - $leadSeconds);
    
    echo "    - #{$ag->id}: {$ag->titulo} (lembrete em: " . $reminderTime->format('Y-m-d H:i:s') . ")\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
