<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n🔍 VERIFICAÇÃO COMPLETA - TABELA LANÇAMENTOS\n";
echo "════════════════════════════════════════════════════════\n\n";

// 1. Estrutura da tabela
echo "📋 ESTRUTURA DA TABELA:\n";
echo "────────────────────────────────────────────────────────\n";

$columns = DB::select("DESCRIBE lancamentos");
foreach ($columns as $col) {
    echo sprintf(
        "%-25s %-15s %-10s %-10s\n",
        $col->Field,
        $col->Type,
        $col->Null,
        $col->Key
    );
}

echo "\n";

// 2. Verificar lançamentos do user 32
echo "👤 LANÇAMENTOS DO USER 32 (Janeiro 2026):\n";
echo "────────────────────────────────────────────────────────\n\n";

$lancamentos = DB::table('lancamentos')
    ->where('user_id', 32)
    ->where('data', 'like', '2026-01%')
    ->orderBy('data', 'desc')
    ->orderBy('id', 'desc')
    ->get();

echo "Total encontrado: " . $lancamentos->count() . "\n\n";

if ($lancamentos->count() > 0) {
    foreach ($lancamentos as $lanc) {
        echo "ID: {$lanc->id}\n";
        echo "  Data: {$lanc->data}\n";
        echo "  Tipo: {$lanc->tipo}\n";
        echo "  Descrição: {$lanc->descricao}\n";
        echo "  Valor: R$ {$lanc->valor}\n";
        echo "  Categoria ID: {$lanc->categoria_id}\n";
        echo "  Conta ID: {$lanc->conta_id}\n";
        echo "  Pago: " . ($lanc->pago ?? 'NULL') . "\n";
        echo "  Parcelamento ID: " . ($lanc->parcelamento_id ?? 'NULL') . "\n";
        echo "  Cartão ID: " . ($lanc->cartao_credito_id ?? 'NULL') . "\n";
        echo "  Transferência: " . ($lanc->eh_transferencia ?? 'NULL') . "\n";
        echo "  Saldo Inicial: " . ($lanc->eh_saldo_inicial ?? 'NULL') . "\n";
        echo "  Arquivado: " . ($lanc->arquivado ?? 'NULL') . "\n";
        echo "\n";
    }
}

// 3. Simular exatamente a chamada da API
echo "🌐 SIMULANDO CHAMADA DA API (endpoint /api/lancamentos):\n";
echo "────────────────────────────────────────────────────────\n\n";

$month = '2026-01';
[$y, $m] = array_map('intval', explode('-', $month));
$from = sprintf('%04d-%02d-01', $y, $m);
$to = date('Y-m-t', strtotime($from));

echo "Parâmetros:\n";
echo "  User ID: 32\n";
echo "  Month: {$month}\n";
echo "  From: {$from}\n";
echo "  To: {$to}\n\n";

$q = DB::table('lancamentos as l')
    ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
    ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
    ->where('l.user_id', 32)
    ->whereBetween('l.data', [$from, $to])
    ->orderBy('l.data', 'desc')
    ->orderBy('l.id', 'desc');

echo "SQL Completo:\n";
echo $q->toSql() . "\n\n";

$rows = $q->selectRaw('
    l.id, l.data, l.tipo, l.valor, l.descricao, l.observacao, 
    l.categoria_id, l.conta_id, l.conta_id_destino, l.eh_transferencia, l.eh_saldo_inicial,
    l.pago, l.parcelamento_id, l.cartao_credito_id,
    COALESCE(c.nome, "") as categoria,
    COALESCE(a.nome, "") as conta_nome,
    COALESCE(a.instituicao, "") as conta_instituicao,
    COALESCE(a.nome, a.instituicao, "") as conta
')->get();

echo "Resultados da API: " . $rows->count() . " registros\n\n";

if ($rows->count() > 0) {
    echo "JSON de retorno:\n";
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

    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 4. Verificar se há campo 'arquivado' que possa estar filtrando
echo "\n";
echo "🔍 VERIFICANDO POSSÍVEIS FILTROS PROBLEMÁTICOS:\n";
echo "────────────────────────────────────────────────────────\n\n";

$schema = DB::select("SHOW COLUMNS FROM lancamentos");
$hasArquivado = false;
$hasDeleted = false;

foreach ($schema as $col) {
    if ($col->Field === 'arquivado') {
        $hasArquivado = true;
        echo "✓ Campo 'arquivado' existe\n";
    }
    if ($col->Field === 'deleted_at') {
        $hasDeleted = true;
        echo "✓ Campo 'deleted_at' existe (soft delete)\n";
    }
}

if ($hasArquivado) {
    $arquivados = DB::table('lancamentos')
        ->where('user_id', 32)
        ->where('data', 'like', '2026-01%')
        ->where('arquivado', 1)
        ->count();
    echo "  Lançamentos arquivados do user 32 em Jan/2026: {$arquivados}\n";
}

if ($hasDeleted) {
    $deletados = DB::table('lancamentos')
        ->where('user_id', 32)
        ->where('data', 'like', '2026-01%')
        ->whereNotNull('deleted_at')
        ->count();
    echo "  Lançamentos deletados do user 32 em Jan/2026: {$deletados}\n";
}

echo "\n════════════════════════════════════════════════════════\n";
