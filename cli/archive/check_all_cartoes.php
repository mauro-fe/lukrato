<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Verificando Todos os Cartões ===" . PHP_EOL . PHP_EOL;

$cartoes = \Application\Models\CartaoCredito::all();

echo "Total de cartões no sistema: " . $cartoes->count() . PHP_EOL . PHP_EOL;

foreach ($cartoes as $cartao) {
    echo "ID: {$cartao->id}" . PHP_EOL;
    echo "User ID: {$cartao->user_id}" . PHP_EOL;
    echo "Nome: " . ($cartao->nome_cartao ?? 'N/A') . PHP_EOL;
    echo "Bandeira: " . ($cartao->bandeira ?? 'N/A') . PHP_EOL;
    echo "Limite Total: " . ($cartao->limite_total ?? 0) . PHP_EOL;
    echo "Ativo: " . var_export($cartao->ativo, true) . PHP_EOL;
    echo "Created: " . ($cartao->created_at ?? 'N/A') . PHP_EOL;
    echo "---" . PHP_EOL . PHP_EOL;
}

if ($cartoes->count() > 0) {
    $primeiroCartao = $cartoes->first();
    $userId = $primeiroCartao->user_id;

    echo "=== Testando relatório para user_id: {$userId} ===" . PHP_EOL . PHP_EOL;

    $params = new \Application\DTO\ReportParameters(
        \Carbon\Carbon::now()->startOfMonth(),
        \Carbon\Carbon::now()->endOfMonth(),
        null,
        $userId,
        false
    );

    $service = new \Application\Services\ReportService();

    try {
        $result = $service->generateReport(\Application\Enums\ReportType::CARTOES_CREDITO, $params);
        echo "Sucesso! Resultado:" . PHP_EOL;
        print_r($result);
    } catch (\Exception $e) {
        echo "ERRO: " . $e->getMessage() . PHP_EOL;
        echo "Linha: " . $e->getLine() . PHP_EOL;
        echo "Arquivo: " . $e->getFile() . PHP_EOL;
    }
}
