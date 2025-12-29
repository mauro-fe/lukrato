<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== PARCELAMENTOS EXISTENTES ===\n\n";

$parcelamentos = DB::table('parcelamentos')->get();

echo "Total: " . count($parcelamentos) . " parcelamentos\n\n";

foreach ($parcelamentos as $p) {
    $valorParcela = $p->numero_parcelas > 0 ? $p->valor_total / $p->numero_parcelas : 0;
    echo "- ID: {$p->id}\n";
    echo "  Descrição: {$p->descricao}\n";
    echo "  Total: R$ " . number_format($p->valor_total, 2, ',', '.') . "\n";
    echo "  Parcelas: {$p->numero_parcelas}x de R$ " . number_format($valorParcela, 2, ',', '.') . "\n";
    echo "  Status: {$p->status}\n";
    echo "  Cartão: {$p->cartao_credito_id}\n\n";
}

echo "✅ Consulta concluída!\n";
