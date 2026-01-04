<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\ParcelamentoService;

echo "=== TESTE: API de Parcelamentos ===\n\n";

// Buscar um usuário de teste (assume que existe user_id = 1)
$userId = 1;

$service = new ParcelamentoService();

echo "Buscando parcelamentos do usuário {$userId}...\n";
$resultado = $service->listar($userId, 'ativo');

if (!$resultado['success']) {
    echo "❌ Erro: " . $resultado['message'] . "\n";
    exit(1);
}

$parcelamentos = $resultado['parcelamentos'];
echo "✅ Encontrados: " . $parcelamentos->count() . " parcelamentos\n\n";

foreach ($parcelamentos as $p) {
    $origem = isset($p->is_cartao) && $p->is_cartao ? '💳 CARTÃO' : '📝 NORMAL';
    echo "[$origem] {$p->descricao}\n";
    echo "  • Total: R$ " . number_format($p->valor_total, 2, ',', '.') . "\n";
    echo "  • Parcelas: {$p->parcelas_pagas}/{$p->numero_parcelas}\n";
    echo "  • Status: {$p->status}\n";

    if (isset($p->cartaoCredito)) {
        echo "  • Cartão: {$p->cartaoCredito->nome_cartao}\n";
    }

    echo "\n";
}

echo "\n✅ Teste concluído!\n";
