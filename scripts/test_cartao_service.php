<?php

require __DIR__ . '/../bootstrap.php';

use Application\Services\CartaoCreditoLancamentoService;

try {
    $service = new CartaoCreditoLancamentoService();
    echo "✅ Service OK\n";

    // Testar criação de lançamento
    $data = [
        'cartao_credito_id' => 27,
        'descricao' => 'Teste',
        'valor' => 100.00,
        'categoria_id' => 1,
        'data' => '2026-01-06',
        'eh_parcelado' => false,
    ];

    $resultado = $service->criarLancamentoCartao(1, $data);
    echo "\n📊 Resultado:\n";
    print_r($resultado);
} catch (Throwable $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
}
