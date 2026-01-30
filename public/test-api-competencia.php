<?php

/**
 * Teste de API HTTP - Competência vs Caixa
 * Acesse via navegador: /test-api-competencia.php
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Lib\Auth;
use Application\Models\Usuario;

// Simular autenticação para teste (usar primeiro usuário)
$user = Usuario::first();
if (!$user) {
    die('Nenhum usuário encontrado');
}

// Simular sessão
$_SESSION['user_id'] = $user->id;

header('Content-Type: application/json');

$month = $_GET['month'] ?? date('Y-m');
$view = $_GET['view'] ?? 'caixa';

$repo = new \Application\Repositories\LancamentoRepository();

// Calcular início e fim do mês
$start = "{$month}-01";
$end = date('Y-m-t', strtotime($start));

if ($view === 'competencia') {
    $receitas = $repo->sumReceitasCompetencia($user->id, $start, $end);
    $despesas = $repo->sumDespesasCompetencia($user->id, $start, $end);
} else {
    $receitas = $repo->sumReceitasCaixa($user->id, $start, $end);
    $despesas = $repo->sumDespesasCaixa($user->id, $start, $end);
}

$resultado = $receitas - $despesas;

// Obter comparativo
$comparativo = $repo->getResumoCompetenciaVsCaixa($user->id, $month);

echo json_encode([
    'status' => 'ok',
    'month' => $month,
    'view' => $view,
    'user_id' => $user->id,
    'metrics' => [
        'receitas' => $receitas,
        'despesas' => $despesas,
        'resultado' => $resultado,
    ],
    'comparativo' => [
        'competencia' => [
            'receitas' => $comparativo['competencia']['receitas'],
            'despesas' => $comparativo['competencia']['despesas'],
            'resultado' => $comparativo['competencia']['receitas'] - $comparativo['competencia']['despesas'],
        ],
        'caixa' => [
            'receitas' => $comparativo['caixa']['receitas'],
            'despesas' => $comparativo['caixa']['despesas'],
            'resultado' => $comparativo['caixa']['receitas'] - $comparativo['caixa']['despesas'],
        ],
        'diferenca' => [
            'receitas' => $comparativo['competencia']['receitas'] - $comparativo['caixa']['receitas'],
            'despesas' => $comparativo['competencia']['despesas'] - $comparativo['caixa']['despesas'],
        ],
    ],
], JSON_PRETTY_PRINT);
