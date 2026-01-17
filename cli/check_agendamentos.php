<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Agendamento;
use Application\Models\Usuario;
use Application\Enums\AgendamentoStatus;
use Application\Services\FeatureGate;

echo "=== Verificando Agendamentos do Usuário 26 ===" . PHP_EOL . PHP_EOL;

// Verificar plano do usuário
$user = Usuario::find(26);
echo "Usuário: {$user->nome}" . PHP_EOL;
echo "isPro: " . ($user->isPro() ? 'Sim' : 'Não') . PHP_EOL;

$plano = $user->planoAtual();
echo "Plano Atual: " . ($plano ? $plano->code : 'nenhum') . PHP_EOL;
echo "podeAcessar('scheduling'): " . ($user->podeAcessar('scheduling') ? 'Sim' : 'Não') . PHP_EOL;
echo PHP_EOL;

// Sem filtro de status
$agendamentos = Agendamento::where('user_id', 26)->orderBy('id', 'desc')->get();

echo "Total de agendamentos (sem filtro): " . $agendamentos->count() . PHP_EOL . PHP_EOL;

foreach ($agendamentos as $a) {
    echo "ID: {$a->id}" . PHP_EOL;
    echo "  Título: {$a->titulo}" . PHP_EOL;
    echo "  Tipo: {$a->tipo}" . PHP_EOL;
    echo "  Valor (centavos): {$a->valor_centavos}" . PHP_EOL;
    echo "  Status: {$a->status}" . PHP_EOL;
    echo "  Data Pagamento: {$a->data_pagamento}" . PHP_EOL;
    echo "  Criado em: {$a->created_at}" . PHP_EOL;
    echo PHP_EOL;
}

// Simular a query do controller
echo "=== Query igual ao Controller (pendente/cancelado) ===" . PHP_EOL;
$filtrado = Agendamento::where('user_id', 26)
    ->whereIn('status', [AgendamentoStatus::PENDENTE->value, AgendamentoStatus::CANCELADO->value])
    ->get();
echo "Total após filtro: " . $filtrado->count() . PHP_EOL;
