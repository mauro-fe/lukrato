<?php

require __DIR__ . '/../bootstrap.php';

echo "=== Debug Relatório de Cartões ===" . PHP_EOL . PHP_EOL;

try {
    $userId = 1; // Ajuste se necessário

    // Buscar cartões
    $cartoes = \Application\Models\CartaoCredito::where('user_id', $userId)
        ->where('ativo', 1)
        ->get();

    echo "Total de cartões ativos: " . $cartoes->count() . PHP_EOL . PHP_EOL;

    foreach ($cartoes as $cartao) {
        echo "=== Cartão ID: {$cartao->id} ===" . PHP_EOL;
        echo "Nome: " . ($cartao->nome_cartao ?? 'N/A') . PHP_EOL;
        echo "Bandeira: " . ($cartao->bandeira ?? 'N/A') . PHP_EOL;
        echo "Limite Total: " . ($cartao->limite_total ?? 0) . PHP_EOL;
        echo "Dia Vencimento: " . ($cartao->dia_vencimento ?? 'N/A') . PHP_EOL;
        echo "Ativo: " . ($cartao->ativo ? 'Sim' : 'Não') . PHP_EOL;

        // Verificar lançamentos
        $lancamentos = \Application\Models\Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartao->id)
            ->where('tipo', 'despesa')
            ->get();

        echo "Total de lançamentos: " . $lancamentos->count() . PHP_EOL;

        if ($lancamentos->count() > 0) {
            echo "Lançamentos:" . PHP_EOL;
            foreach ($lancamentos as $lanc) {
                echo "  - ID: {$lanc->id}, Data: {$lanc->data}, Valor: {$lanc->valor}" . PHP_EOL;
            }
        }

        // Verificar parcelamentos
        $parcelamentos = \Application\Models\Parcelamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartao->id)
            ->where('ativo', 1)
            ->get();

        echo "Total de parcelamentos ativos: " . $parcelamentos->count() . PHP_EOL;

        if ($parcelamentos->count() > 0) {
            echo "Parcelamentos:" . PHP_EOL;
            foreach ($parcelamentos as $parc) {
                echo "  - ID: {$parc->id}, Descrição: " . ($parc->descricao ?? 'N/A') . ", Valor Total: " . ($parc->valor_total ?? 0) . PHP_EOL;
            }
        }

        echo PHP_EOL;
    }

    // Testar a API
    echo PHP_EOL . "=== Testando ReportService ===" . PHP_EOL;

    $params = new \Application\DTO\ReportParameters(
        \Carbon\Carbon::now()->startOfMonth(),
        \Carbon\Carbon::now()->endOfMonth(),
        null,
        $userId,
        false
    );

    $service = new \Application\Services\ReportService();
    $result = $service->generateReport(\Application\Enums\ReportType::CARTOES_CREDITO, $params);

    echo "Resultado da API:" . PHP_EOL;
    print_r($result);
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
