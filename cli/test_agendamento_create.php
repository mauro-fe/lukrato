<?php
require __DIR__ . '/../bootstrap.php';

use Application\DTO\CreateAgendamentoDTO;
use Application\Repositories\AgendamentoRepository;
use Application\Models\Agendamento;

echo "=== Testando Criação de Agendamento ===" . PHP_EOL . PHP_EOL;

$userId = 26;

// Dados que o JS envia
$data = [
    'titulo' => 'Teste de Agendamento',
    'tipo' => 'despesa',
    'valor' => '150.00',
    'data_pagamento' => '2026-01-20 10:00:00',
    'categoria_id' => 1,
    'conta_id' => null,
    'descricao' => 'Observação de teste',
    'recorrente' => false,
    'canal_inapp' => true
];

try {
    $dto = CreateAgendamentoDTO::fromRequest($userId, $data);
    echo "DTO criado com sucesso:" . PHP_EOL;
    print_r($dto->toArray());

    $repo = new AgendamentoRepository();
    $agendamento = $repo->create($dto->toArray());

    echo PHP_EOL . "=== Agendamento Criado ===" . PHP_EOL;
    print_r($agendamento->toArray());

    // Verificar se aparece na lista
    echo PHP_EOL . "=== Verificando lista de agendamentos do usuário ===" . PHP_EOL;
    $lista = Agendamento::where('user_id', $userId)->get();
    echo "Total: " . $lista->count() . PHP_EOL;
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
