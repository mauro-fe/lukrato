<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

$cartaoId = $argv[1] ?? null;
if (!$cartaoId) {
    echo "Uso: php cli/list_lancamentos_cartao.php <cartao_credito_id>\n";
    exit;
}

$userId = getenv('USER_ID') ?: 1;
$cartaoId = (int)$cartaoId;

$lancamentos = Lancamento::where('user_id', $userId)
    ->where('cartao_credito_id', $cartaoId)
    ->orderBy('created_at')
    ->get();

if ($lancamentos->isEmpty()) {
    echo "Nenhum lançamento encontrado para cartao_id={$cartaoId}\n";
    exit;
}

echo "Lançamentos para cartao_id={$cartaoId}:\n";
foreach ($lancamentos as $l) {
    printf(
        "- id:%d | criado:%s | data:%s | valor:%s | pago:%d | eh_parcelado:%d | parcela_atual:%s | total_parcelas:%s | parcelamento_id:%s | descricao:%s\n",
        $l->id,
        $l->created_at?->format('Y-m-d H:i:s') ?? 'NULL',
        $l->data?->format('Y-m-d') ?? 'NULL',
        number_format($l->valor, 2, ',', '.'),
        $l->pago ? 1 : 0,
        $l->eh_parcelado ? 1 : 0,
        $l->parcela_atual ?? 'NULL',
        $l->total_parcelas ?? 'NULL',
        $l->parcelamento_id ?? 'NULL',
        $l->descricao
    );
}

echo "\nTotal registros: " . $lancamentos->count() . "\n";
