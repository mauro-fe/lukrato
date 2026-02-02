<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\Conta;

$userId = 1;

echo "=== ANÁLISE DE LANÇAMENTOS JANEIRO 2026 ===" . PHP_EOL . PHP_EOL;

// Buscar lançamentos de janeiro
$lancamentos = Lancamento::where('user_id', $userId)
    ->where('data', '>=', '2026-01-01')
    ->where('data', '<=', '2026-01-31')
    ->orderBy('data')
    ->get();

echo "Contas do usuário e seus tipos:" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

$contas = Conta::where('user_id', $userId)->get();
$contasInfo = [];
foreach ($contas as $c) {
    $contasInfo[$c->id] = $c;
    echo sprintf("#%d | %-25s | tipo: %s", $c->id, $c->nome, $c->tipo) . PHP_EOL;
}

echo PHP_EOL . "Lançamentos com cartao_credito_id preenchido mas conta NÃO é cartão:" . PHP_EOL;
echo str_repeat('-', 120) . PHP_EOL;

$errosConta = [];
foreach ($lancamentos as $l) {
    if ($l->cartao_credito_id) {
        $conta = $contasInfo[$l->conta_id] ?? null;
        if ($conta && $conta->tipo !== 'cartao_credito') {
            $errosConta[] = $l;
            echo sprintf(
                "%s | %-8s | R$ %8.2f | Conta: %-20s (tipo: %s) | Cartão: #%d | %s",
                $l->data,
                strtoupper($l->tipo),
                $l->valor,
                $conta->nome ?? 'N/A',
                $conta->tipo ?? 'N/A',
                $l->cartao_credito_id,
                substr($l->descricao, 0, 25)
            ) . PHP_EOL;
        }
    }
}
if (empty($errosConta)) {
    echo "(nenhum encontrado)" . PHP_EOL;
}

echo PHP_EOL . "Lançamentos com afeta_caixa=false mas SEM cartao_credito_id (e não é transferência):" . PHP_EOL;
echo str_repeat('-', 120) . PHP_EOL;

$errosAfetaCaixa = [];
foreach ($lancamentos as $l) {
    if (!$l->afeta_caixa && !$l->cartao_credito_id && !$l->eh_transferencia) {
        $errosAfetaCaixa[] = $l;
        $conta = $contasInfo[$l->conta_id] ?? null;
        echo sprintf(
            "%s | %-8s | R$ %8.2f | Conta: %-20s | pago=%s | %s",
            $l->data,
            strtoupper($l->tipo),
            $l->valor,
            $conta->nome ?? 'N/A',
            $l->pago ? 'SIM' : 'NÃO',
            substr($l->descricao, 0, 30)
        ) . PHP_EOL;
    }
}
if (empty($errosAfetaCaixa)) {
    echo "(nenhum encontrado)" . PHP_EOL;
}

echo PHP_EOL . "Resumo de lançamentos de janeiro por CONTA:" . PHP_EOL;
echo str_repeat('-', 100) . PHP_EOL;

$porConta = [];
foreach ($lancamentos as $l) {
    $contaId = $l->conta_id;
    if (!isset($porConta[$contaId])) {
        $conta = $contasInfo[$contaId] ?? null;
        $porConta[$contaId] = [
            'nome' => $conta->nome ?? 'N/A',
            'tipo' => $conta->tipo ?? 'N/A',
            'total' => 0,
            'afeta_caixa_true' => 0,
            'afeta_caixa_false' => 0,
            'com_cartao' => 0,
            'sem_cartao' => 0
        ];
    }
    $porConta[$contaId]['total']++;
    if ($l->afeta_caixa) {
        $porConta[$contaId]['afeta_caixa_true']++;
    } else {
        $porConta[$contaId]['afeta_caixa_false']++;
    }
    if ($l->cartao_credito_id) {
        $porConta[$contaId]['com_cartao']++;
    } else {
        $porConta[$contaId]['sem_cartao']++;
    }
}

echo sprintf("%-5s %-25s %-15s | %-6s | AfetaCaixa (S/N) | ComCartão (S/N)", "ID", "Nome", "Tipo", "Total") . PHP_EOL;
echo str_repeat('-', 100) . PHP_EOL;

foreach ($porConta as $contaId => $info) {
    echo sprintf(
        "#%-4d %-25s %-15s | %6d | %3d / %-3d        | %3d / %-3d",
        $contaId,
        $info['nome'],
        $info['tipo'],
        $info['total'],
        $info['afeta_caixa_true'],
        $info['afeta_caixa_false'],
        $info['com_cartao'],
        $info['sem_cartao']
    ) . PHP_EOL;
}

// Verificar se conta #32 é realmente um cartão de crédito
echo PHP_EOL . "=== VERIFICAÇÃO DA CONTA #32 ===" . PHP_EOL;
$conta32 = Conta::find(32);
if ($conta32) {
    echo "Conta #32: " . $conta32->nome . " | Tipo: " . $conta32->tipo . PHP_EOL;
} else {
    echo "Conta #32 não encontrada!" . PHP_EOL;
}

// Mostrar lançamentos da conta #32 que NÃO são de cartão de crédito
echo PHP_EOL . "Lançamentos da conta #32 em janeiro:" . PHP_EOL;
echo str_repeat('-', 120) . PHP_EOL;

$lancamentosConta32 = Lancamento::where('user_id', $userId)
    ->where('conta_id', 32)
    ->where('data', '>=', '2026-01-01')
    ->where('data', '<=', '2026-01-31')
    ->orderBy('data')
    ->get();

foreach ($lancamentosConta32 as $l) {
    echo sprintf(
        "#%-5d %s | %-8s | R$ %8.2f | pago=%s | afeta=%s | cartão=%s | %s",
        $l->id,
        $l->data,
        strtoupper($l->tipo),
        $l->valor,
        $l->pago ? 'S' : 'N',
        $l->afeta_caixa ? 'S' : 'N',
        $l->cartao_credito_id ? '#' . $l->cartao_credito_id : '-',
        substr($l->descricao, 0, 30)
    ) . PHP_EOL;
}

echo PHP_EOL . "Total na conta #32: " . count($lancamentosConta32) . " lançamentos" . PHP_EOL;
