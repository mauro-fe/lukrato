<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Enums\AgendamentoStatus;
use Application\Lib\Auth;

// Simular sessão do usuário 26
$_SESSION['user_id'] = 26;

echo "=== Simulando API de Agendamentos ===" . PHP_EOL . PHP_EOL;

$userId = 26;

$agendamentos = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
    ->where('user_id', $userId)
    ->whereIn('status', [AgendamentoStatus::PENDENTE->value, AgendamentoStatus::CANCELADO->value])
    ->orderBy('data_pagamento', 'asc')
    ->limit(100)
    ->get();

echo "Total retornado: " . $agendamentos->count() . PHP_EOL . PHP_EOL;

$response = [
    'status' => 'success',
    'data' => [
        'itens' => $agendamentos->toArray()
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
