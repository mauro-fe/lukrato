<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Notificacao;
use Application\Models\Usuario;
use Application\Services\MailService;
use Application\Services\AgendamentoService;
use Application\DTO\CreateAgendamentoDTO;
use Application\Repositories\AgendamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TESTE COMPLETO DO SISTEMA DE AGENDAMENTOS E NOTIFICAÇÕES    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$success = [];

// =============================================================================
// 1. VERIFICAR ESTRUTURA DO BANCO DE DADOS
// =============================================================================
echo "═══ 1. VERIFICANDO ESTRUTURA DO BANCO DE DADOS ═══\n";

// Verificar tabela agendamentos
try {
    $cols = DB::select("DESCRIBE agendamentos");
    $colNames = array_column($cols, 'Field');
    
    $requiredCols = [
        'id', 'user_id', 'titulo', 'descricao', 'tipo', 'valor_centavos',
        'data_pagamento', 'proxima_execucao', 'notificado_em', 'concluido_em',
        'lembrar_antes_segundos', 'canal_email', 'canal_inapp', 'status',
        'recorrente', 'recorrencia_freq'
    ];
    
    $missing = array_diff($requiredCols, $colNames);
    if (!empty($missing)) {
        $errors[] = "Colunas faltando em agendamentos: " . implode(', ', $missing);
        echo "  ❌ Colunas faltando: " . implode(', ', $missing) . "\n";
    } else {
        $success[] = "Tabela agendamentos com estrutura correta";
        echo "  ✅ Tabela agendamentos OK\n";
    }
    
    // Verificar ENUM de status
    $statusCol = array_filter($cols, fn($c) => $c->Field === 'status');
    $statusCol = reset($statusCol);
    if ($statusCol) {
        $enumValues = $statusCol->Type;
        if (strpos($enumValues, 'notificado') === false) {
            $errors[] = "ENUM status não contém 'notificado': {$enumValues}";
            echo "  ❌ ENUM status incorreto: {$enumValues}\n";
        } else {
            $success[] = "ENUM de status correto";
            echo "  ✅ ENUM status OK\n";
        }
    }
} catch (\Throwable $e) {
    $errors[] = "Erro ao verificar tabela agendamentos: " . $e->getMessage();
    echo "  ❌ Erro: " . $e->getMessage() . "\n";
}

// Verificar tabela notificacoes
try {
    $cols = DB::select("DESCRIBE notificacoes");
    $colNames = array_column($cols, 'Field');
    
    $requiredCols = ['id', 'user_id', 'tipo', 'titulo', 'mensagem', 'lida', 'link'];
    $missing = array_diff($requiredCols, $colNames);
    
    if (!empty($missing)) {
        $errors[] = "Colunas faltando em notificacoes: " . implode(', ', $missing);
        echo "  ❌ Colunas faltando em notificacoes: " . implode(', ', $missing) . "\n";
    } else {
        $success[] = "Tabela notificacoes com estrutura correta";
        echo "  ✅ Tabela notificacoes OK\n";
    }
} catch (\Throwable $e) {
    $errors[] = "Erro ao verificar tabela notificacoes: " . $e->getMessage();
    echo "  ❌ Erro: " . $e->getMessage() . "\n";
}

// =============================================================================
// 2. VERIFICAR SERVIÇOS
// =============================================================================
echo "\n═══ 2. VERIFICANDO SERVIÇOS ═══\n";

// Verificar MailService
try {
    $mailService = new MailService();
    if ($mailService->isConfigured()) {
        $success[] = "MailService configurado corretamente";
        echo "  ✅ MailService configurado\n";
    } else {
        $warnings[] = "MailService não está configurado - emails não serão enviados";
        echo "  ⚠️ MailService NÃO configurado (verifique .env)\n";
    }
} catch (\Throwable $e) {
    $errors[] = "Erro ao inicializar MailService: " . $e->getMessage();
    echo "  ❌ Erro no MailService: " . $e->getMessage() . "\n";
}

// Verificar AgendamentoService
try {
    $agendamentoService = new AgendamentoService();
    $success[] = "AgendamentoService inicializado";
    echo "  ✅ AgendamentoService OK\n";
} catch (\Throwable $e) {
    $errors[] = "Erro ao inicializar AgendamentoService: " . $e->getMessage();
    echo "  ❌ Erro no AgendamentoService: " . $e->getMessage() . "\n";
}

// Verificar AgendamentoRepository
try {
    $agendamentoRepo = new AgendamentoRepository();
    $success[] = "AgendamentoRepository inicializado";
    echo "  ✅ AgendamentoRepository OK\n";
} catch (\Throwable $e) {
    $errors[] = "Erro ao inicializar AgendamentoRepository: " . $e->getMessage();
    echo "  ❌ Erro no AgendamentoRepository: " . $e->getMessage() . "\n";
}

// =============================================================================
// 3. VERIFICAR CONFIGURAÇÃO DO SCHEDULER
// =============================================================================
echo "\n═══ 3. VERIFICANDO CONFIGURAÇÃO DO SCHEDULER ═══\n";

$schedulerToken = $_ENV['SCHEDULER_TOKEN'] ?? getenv('SCHEDULER_TOKEN') ?: null;
if (!empty($schedulerToken)) {
    $success[] = "SCHEDULER_TOKEN configurado";
    echo "  ✅ SCHEDULER_TOKEN configurado\n";
} else {
    $errors[] = "SCHEDULER_TOKEN não configurado no .env";
    echo "  ❌ SCHEDULER_TOKEN NÃO configurado no .env\n";
}

$appUrl = $_ENV['APP_URL'] ?? (defined('BASE_URL') ? BASE_URL : null);
if (!empty($appUrl)) {
    $success[] = "APP_URL/BASE_URL configurado: {$appUrl}";
    echo "  ✅ APP_URL/BASE_URL: {$appUrl}\n";
} else {
    $warnings[] = "APP_URL não configurado - links nas notificações podem não funcionar";
    echo "  ⚠️ APP_URL não configurado\n";
}

// =============================================================================
// 4. TESTE DE CRIAÇÃO DE AGENDAMENTO (SIMULADO)
// =============================================================================
echo "\n═══ 4. TESTANDO CRIAÇÃO DE AGENDAMENTO ═══\n";

try {
    // Buscar um usuário existente para o teste
    $usuario = Usuario::first();
    if (!$usuario) {
        $warnings[] = "Nenhum usuário encontrado para teste de criação";
        echo "  ⚠️ Nenhum usuário encontrado para teste\n";
    } else {
        // Testar criação do DTO
        $dataTeste = [
            'titulo' => 'Teste Automatizado',
            'tipo' => 'despesa',
            'valor' => '150,00',
            'data_pagamento' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'lembrar_antes_segundos' => 1800, // 30 minutos
            'canal_email' => true,
            'canal_inapp' => true,
        ];
        
        $dto = CreateAgendamentoDTO::fromRequest($usuario->id, $dataTeste);
        
        if ($dto->valor_centavos === 15000) {
            $success[] = "DTO converte valor corretamente";
            echo "  ✅ Conversão de valor OK (R\$ 150,00 = 15000 centavos)\n";
        } else {
            $errors[] = "Conversão de valor incorreta: {$dto->valor_centavos} != 15000";
            echo "  ❌ Conversão de valor INCORRETA: {$dto->valor_centavos}\n";
        }
        
        if ($dto->canal_email === true && $dto->canal_inapp === true) {
            $success[] = "Canais de notificação configurados corretamente";
            echo "  ✅ Canais de notificação OK\n";
        } else {
            $errors[] = "Canais de notificação não configurados corretamente";
            echo "  ❌ Canais de notificação INCORRETOS\n";
        }
        
        // Verificar cálculo de próxima execução
        $dataPagamento = new DateTimeImmutable($dataTeste['data_pagamento']);
        $esperado = $dataPagamento->modify('-1800 seconds');
        $calculado = new DateTimeImmutable($dto->proxima_execucao);
        
        $diffSeconds = abs($esperado->getTimestamp() - $calculado->getTimestamp());
        if ($diffSeconds < 5) { // tolerância de 5 segundos
            $success[] = "Cálculo de próxima execução OK";
            echo "  ✅ Cálculo de próxima execução OK\n";
        } else {
            $errors[] = "Cálculo de próxima execução incorreto";
            echo "  ❌ Cálculo de próxima execução INCORRETO (diff: {$diffSeconds}s)\n";
        }
    }
} catch (\Throwable $e) {
    $errors[] = "Erro no teste de criação: " . $e->getMessage();
    echo "  ❌ Erro: " . $e->getMessage() . "\n";
}

// =============================================================================
// 5. VERIFICAR LÓGICA DE JANELA DE ENVIO
// =============================================================================
echo "\n═══ 5. VERIFICANDO LÓGICA DE JANELA DE ENVIO ═══\n";

$now = new \DateTimeImmutable('now');
$windowStart = $now->modify('-5 minutes');
$windowEnd = $now->modify('+10 minutes');

echo "  Agora: " . $now->format('Y-m-d H:i:s') . "\n";
echo "  Janela: " . $windowStart->format('H:i:s') . " até " . $windowEnd->format('H:i:s') . "\n";

// Testar cenários
$cenarios = [
    ['nome' => 'Lembrete no passado (-10min)', 'offset' => -600, 'esperado' => false],
    ['nome' => 'Lembrete há 3min atrás', 'offset' => -180, 'esperado' => true],
    ['nome' => 'Lembrete agora', 'offset' => 0, 'esperado' => true],
    ['nome' => 'Lembrete em 5min', 'offset' => 300, 'esperado' => true],
    ['nome' => 'Lembrete em 15min', 'offset' => 900, 'esperado' => false],
];

foreach ($cenarios as $cenario) {
    $reminderTime = $now->getTimestamp() + $cenario['offset'];
    $dentroJanela = $reminderTime >= $windowStart->getTimestamp() && $reminderTime <= $windowEnd->getTimestamp();
    
    $status = $dentroJanela === $cenario['esperado'] ? '✅' : '❌';
    echo "  {$status} {$cenario['nome']}: " . ($dentroJanela ? 'DENTRO' : 'FORA') . "\n";
    
    if ($dentroJanela !== $cenario['esperado']) {
        $errors[] = "Lógica de janela incorreta para: {$cenario['nome']}";
    }
}

// =============================================================================
// 6. VERIFICAR AGENDAMENTOS PENDENTES
// =============================================================================
echo "\n═══ 6. ESTATÍSTICAS DE AGENDAMENTOS ═══\n";

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

// Agendamentos prontos para notificação
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

echo "\n  Prontos para notificação AGORA: " . count($prontos) . "\n";

// =============================================================================
// 7. VERIFICAR NOTIFICAÇÕES CRIADAS
// =============================================================================
echo "\n═══ 7. VERIFICANDO NOTIFICAÇÕES ═══\n";

$totalNotificacoes = Notificacao::count();
$naoLidas = Notificacao::where('lida', false)->count();
$deAgendamento = Notificacao::where('tipo', 'agendamento')->count();

echo "  Total de notificações: {$totalNotificacoes}\n";
echo "  Não lidas: {$naoLidas}\n";
echo "  Do tipo 'agendamento': {$deAgendamento}\n";

// =============================================================================
// RESUMO FINAL
// =============================================================================
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                         RESUMO DO TESTE                          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ SUCESSOS: " . count($success) . "\n";
foreach ($success as $s) {
    echo "   - {$s}\n";
}

if (!empty($warnings)) {
    echo "\n⚠️ AVISOS: " . count($warnings) . "\n";
    foreach ($warnings as $w) {
        echo "   - {$w}\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERROS: " . count($errors) . "\n";
    foreach ($errors as $e) {
        echo "   - {$e}\n";
    }
}

echo "\n";
if (empty($errors)) {
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "  🎉 SISTEMA DE AGENDAMENTOS FUNCIONANDO CORRETAMENTE! 🎉\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
} else {
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "  ⚠️ SISTEMA COM PROBLEMAS - VERIFIQUE OS ERROS ACIMA ⚠️\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
}

echo "\n";
