<?php
require __DIR__ . '/../bootstrap.php';

use Application\Services\ReportService;

$reportService = new ReportService();

$userId = 26;
$cardId = 36;

echo "=== TESTE RELATÓRIO JANEIRO/2026 ===\n\n";
try {
    $relatorio = $reportService->getCardDetailedReport($userId, $cardId, '01', '2026');

    echo "Cartão: {$relatorio['cartao']['nome']}\n";
    echo "Limite: R$ " . number_format($relatorio['cartao']['limite'], 2, ',', '.') . "\n";
    echo "Disponível: R$ " . number_format($relatorio['cartao']['limite_disponivel'], 2, ',', '.') . "\n";
    echo "Utilização: {$relatorio['cartao']['percentual_utilizacao_geral']}%\n\n";

    echo "FATURA JANEIRO:\n";
    echo "Total: R$ " . number_format($relatorio['fatura_mes']['total'], 2, ',', '.') . "\n";
    echo "À Vista: R$ " . number_format($relatorio['fatura_mes']['a_vista'], 2, ',', '.') . "\n";
    echo "Parcelado: R$ " . number_format($relatorio['fatura_mes']['parcelado'], 2, ',', '.') . "\n";
    echo "Lançamentos: " . count($relatorio['fatura_mes']['lancamentos']) . "\n\n";

    if (count($relatorio['fatura_mes']['lancamentos']) > 0) {
        foreach ($relatorio['fatura_mes']['lancamentos'] as $lanc) {
            echo "- {$lanc['descricao']} | R$ {$lanc['valor']} | {$lanc['categoria']}\n";
        }
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE RELATÓRIO FEVEREIRO/2026 ===\n\n";
try {
    $relatorio = $reportService->getCardDetailedReport($userId, $cardId, '02', '2026');

    echo "FATURA FEVEREIRO:\n";
    echo "Total: R$ " . number_format($relatorio['fatura_mes']['total'], 2, ',', '.') . "\n";
    echo "À Vista: R$ " . number_format($relatorio['fatura_mes']['a_vista'], 2, ',', '.') . "\n";
    echo "Parcelado: R$ " . number_format($relatorio['fatura_mes']['parcelado'], 2, ',', '.') . "\n";
    echo "Lançamentos: " . count($relatorio['fatura_mes']['lancamentos']) . "\n\n";

    if (count($relatorio['fatura_mes']['lancamentos']) > 0) {
        foreach ($relatorio['fatura_mes']['lancamentos'] as $lanc) {
            echo "- {$lanc['descricao']} | R$ {$lanc['valor']} | {$lanc['categoria']}\n";
        }
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
