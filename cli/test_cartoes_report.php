<?php

require __DIR__ . '/../bootstrap.php';

use Application\Lib\Auth;

// Simula um usuário logado
$_SESSION['user_id'] = 1; // Ajuste conforme necessário

$userId = 1;

echo "=== Testando Relatório de Cartões ===\n\n";

// Verifica cartões
$cartoes = \Application\Models\CartaoCredito::where('user_id', $userId)->get();
echo "Total de cartões: " . $cartoes->count() . "\n\n";

foreach ($cartoes as $c) {
    echo "ID: " . $c->id . "\n";
    echo "Nome: " . ($c->nome_cartao ?? $c->nome ?? 'N/A') . "\n";
    echo "Bandeira: " . ($c->bandeira ?? 'N/A') . "\n";
    echo "Limite Total: " . ($c->limite_total ?? 0) . "\n";
    echo "Dia Vencimento: " . ($c->dia_vencimento ?? 'N/A') . "\n";
    echo "Ativo: " . ($c->ativo ? 'Sim' : 'Não') . "\n";
    echo "---\n";
}

// Testa a API
echo "\n=== Testando API de Relatórios ===\n\n";

use Application\Services\ReportService;
use Application\DTO\ReportParameters;
use Application\Enums\ReportType;
use Carbon\Carbon;

$reportService = new ReportService();
$params = new ReportParameters(
    Carbon::now()->startOfMonth(),
    Carbon::now()->endOfMonth(),
    null,
    $userId,
    false
);

try {
    $result = $reportService->generateReport(ReportType::CARTOES_CREDITO, $params);
    echo "Resultado da API:\n";
    print_r($result);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
