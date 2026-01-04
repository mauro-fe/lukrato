<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Parcelamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTANDO PARCELAMENTO ID 4 ===\n\n";

$parc = Parcelamento::with(['lancamentos', 'categoria', 'conta'])->find(4);

if (!$parc) {
    echo "❌ Parcelamento não encontrado\n";
    exit(1);
}

echo "ID: " . $parc->id . "\n";
echo "Descrição: " . $parc->descricao . "\n";
echo "Valor Total: R$ " . number_format($parc->valor_total, 2, ',', '.') . "\n";
echo "Parcelas: " . $parc->numero_parcelas . "x\n";
echo "Status: " . $parc->status . "\n\n";

echo "=== PARCELAS (Lançamentos) ===\n";
foreach ($parc->lancamentos as $lanc) {
    echo "- ID: " . $lanc->id . " | ";
    echo "Parcela: " . $lanc->numero_parcela . "/" . $parc->numero_parcelas . " | ";
    echo "Valor: R$ " . number_format($lanc->valor, 2, ',', '.') . " | ";
    echo "Data: " . $lanc->data->format('d/m/Y') . " | ";
    echo "Pago: " . ($lanc->pago ? 'Sim' : 'Não') . "\n";
}

echo "\n=== FORMATO JSON (como a API retorna) ===\n";
$formatted = [
    'id' => $parc->id,
    'descricao' => $parc->descricao,
    'valor_total' => $parc->valor_total,
    'numero_parcelas' => $parc->numero_parcelas,
    'parcelas' => $parc->lancamentos->map(function ($l) {
        return [
            'id' => $l->id,
            'numero_parcela' => $l->numero_parcela,
            'valor' => $l->valor,
            'data' => $l->data->format('Y-m-d'),
            'pago' => (bool)$l->pago
        ];
    })->toArray()
];

echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
