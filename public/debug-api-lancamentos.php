<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;

header('Content-Type: application/json; charset=utf-8');

// Simular a chamada exata da API
$userId = Auth::id();
if (!$userId) {
    $userId = 32; // Para teste, forçar user 32
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid month format']);
    exit;
}

[$y, $m] = array_map('intval', explode('-', $month));
$from = sprintf('%04d-%02d-01', $y, $m);
$to = date('Y-m-t', strtotime($from));

$accId = (int)($_GET['account_id'] ?? 0) ?: null;
$limit = min((int)($_GET['limit'] ?? 500), 1000);

$tipo = strtolower($_GET['tipo'] ?? '');
$categoria = $_GET['categoria_id'] ?? '';

echo json_encode([
    'debug' => [
        'user_id' => $userId,
        'month' => $month,
        'from' => $from,
        'to' => $to,
        'type' => $tipo,
        'categoria' => $categoria,
        'account_id' => $accId,
        'limit' => $limit,
    ],
    'query_info' => 'Executando query...'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Executar a mesma query do controller
$q = DB::table('lancamentos as l')
    ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
    ->leftJoin('contas as a',     'a.id', '=', 'l.conta_id')
    ->where('l.user_id', $userId)
    ->whereBetween('l.data', [$from, $to])
    ->orderBy('l.data', 'desc')
    ->orderBy('l.id', 'desc')
    ->limit($limit);

$rows = $q->selectRaw('
    l.id, l.data, l.tipo, l.valor, l.descricao, l.observacao, 
    l.categoria_id, l.conta_id, l.conta_id_destino, l.eh_transferencia, l.eh_saldo_inicial,
    l.pago, l.parcelamento_id, l.cartao_credito_id,
    COALESCE(c.nome, "") as categoria,
    COALESCE(a.nome, "") as conta_nome,
    COALESCE(a.instituicao, "") as conta_instituicao,
    COALESCE(a.nome, a.instituicao, "") as conta
')->get();

$out = $rows->map(fn($r) => [
    'id'               => (int)$r->id,
    'data'             => (string)$r->data,
    'tipo'             => (string)$r->tipo,
    'valor'            => (float)$r->valor,
    'descricao'        => (string)($r->descricao ?? ''),
    'observacao'       => (string)($r->observacao ?? ''),
    'categoria_id'     => (int)$r->categoria_id ?: null,
    'conta_id'         => (int)$r->conta_id ?: null,
    'conta_id_destino' => (int)$r->conta_id_destino ?: null,
    'eh_transferencia' => (bool) ($r->eh_transferencia ?? 0),
    'eh_saldo_inicial' => (bool)($r->eh_saldo_inicial ?? 0),
    'pago'             => (bool)($r->pago ?? 0),
    'parcelamento_id'  => (int)$r->parcelamento_id ?: null,
    'cartao_credito_id' => (int)$r->cartao_credito_id ?: null,
    'categoria'        => (string)$r->categoria,
    'conta'            => (string)$r->conta,
    'conta_nome'       => (string)$r->conta_nome,
    'conta_instituicao' => (string)$r->conta_instituicao,
])->values()->all();

http_response_code(200);
echo json_encode([
    'success' => true,
    'status' => 'success',
    'message' => 'success',
    'data' => $out,
    'count' => count($out)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
