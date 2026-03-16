<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\TransactionDetectorService;
use PHPUnit\Framework\TestCase;

/**
 * Testa TransactionDetectorService com fixtures e casos de borda.
 */
class TransactionDetectorTest extends TestCase
{
    private array $cases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cases = require dirname(__DIR__, 3) . '/Fixtures/AI/transaction_extraction_cases.php';
    }

    /**
     * Benchmark completo de extração de transação.
     */
    public function testExtractionBenchmark(): void
    {
        $total = count($this->cases);
        $correct = 0;
        $failures = [];

        foreach ($this->cases as $i => $case) {
            $input = $case['input'];
            $expected = $case['expected'];
            $tags = $case['tags'];
            $notes = $case['notes'];

            $result = TransactionDetectorService::extract($input);

            if ($expected === null) {
                // Caso negativo: não deve extrair
                if ($result === null) {
                    $correct++;
                } else {
                    $failures[] = "  [{$i}] \"{$input}\" → NEG, got val={$result['valor']} [{$notes}]";
                }
                continue;
            }

            // Caso positivo: deve extrair com campos corretos
            if ($result === null) {
                $failures[] = "  [{$i}] \"{$input}\" → NULL (expected val={$expected['valor']}) [{$notes}]";
                continue;
            }

            $ok = true;
            foreach ($expected as $field => $value) {
                if ($field === 'valor') {
                    if (abs($result[$field] - $value) > 0.01) {
                        $failures[] = "  [{$i}] \"{$input}\" → valor={$result[$field]}, expected={$value} [{$notes}]";
                        $ok = false;
                    }
                } elseif ($field === 'descricao') {
                    if (mb_strtolower($result[$field]) !== mb_strtolower($value)) {
                        $failures[] = "  [{$i}] \"{$input}\" → desc=\"{$result[$field]}\", expected=\"{$value}\" [{$notes}]";
                        $ok = false;
                    }
                } elseif (isset($result[$field]) && $result[$field] !== $value) {
                    $failures[] = "  [{$i}] \"{$input}\" → {$field}={$result[$field]}, expected={$value} [{$notes}]";
                    $ok = false;
                }
            }

            if ($ok) {
                $correct++;
            }
        }

        $rate = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

        $report = "\n╔═════════════════════════════════════════════════╗\n";
        $report .= "║       TRANSACTION EXTRACTION BENCHMARK          ║\n";
        $report .= "╠═════════════════════════════════════════════════╣\n";
        $report .= sprintf("║  Total: %-3d  Correct: %-3d  Rate: %s%%  ║\n", $total, $correct, str_pad((string) $rate, 5));
        $report .= "╚═════════════════════════════════════════════════╝\n";

        if (!empty($failures)) {
            $report .= "\nFAILURES:\n" . implode("\n", $failures) . "\n";
        }

        fwrite(STDERR, $report);

        $this->assertGreaterThanOrEqual(75.0, $rate, "Extraction accuracy below 75%: {$rate}%");
    }

    // ─── Testes unitários específicos ────────────────────────────

    public function testDetectsValueBasic(): void
    {
        $this->assertTrue(TransactionDetectorService::detectsValue('gastei 50'));
        $this->assertTrue(TransactionDetectorService::detectsValue('uber 32.50'));
        $this->assertTrue(TransactionDetectorService::detectsValue('R$ 100'));
    }

    public function testDetectsValueNegative(): void
    {
        $this->assertFalse(TransactionDetectorService::detectsValue('olá tudo bem'));
        $this->assertFalse(TransactionDetectorService::detectsValue('bom dia'));
    }

    public function testExtractReturnsNullForShortMessage(): void
    {
        $this->assertNull(TransactionDetectorService::extract('ab'));
        $this->assertNull(TransactionDetectorService::extract(''));
    }

    public function testExtractBasicExpense(): void
    {
        $result = TransactionDetectorService::extract('gastei 40 no uber');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(40.0, $result['valor'], 0.01);
        $this->assertEquals('despesa', $result['tipo']);
        $this->assertNotEmpty($result['descricao']);
    }

    public function testExtractBasicIncome(): void
    {
        $result = TransactionDetectorService::extract('recebi 5000 de salário');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(5000.0, $result['valor'], 0.01);
        $this->assertEquals('receita', $result['tipo']);
    }

    public function testExtractPix(): void
    {
        $result = TransactionDetectorService::extract('mandei pix 200 pro joão');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(200.0, $result['valor'], 0.01);
        $this->assertEquals('pix', $result['forma_pagamento']);
    }

    public function testExtractInstallment(): void
    {
        $result = TransactionDetectorService::extract('parcelei 3000 em 12x no inter');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3000.0, $result['valor'], 0.01);
        $this->assertTrue($result['eh_parcelado']);
        $this->assertEquals(12, $result['total_parcelas']);
    }

    public function testExtractCardPurchase(): void
    {
        $result = TransactionDetectorService::extract('comprei geladeira no cartão por 1500');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(1500.0, $result['valor'], 0.01);
        $this->assertEquals('cartao_credito', $result['forma_pagamento']);
    }

    public function testExtractWithBRCurrency(): void
    {
        $result = TransactionDetectorService::extract('paguei 1.500 de aluguel');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(1500.0, $result['valor'], 0.01);
    }

    public function testExtractCompactFormat(): void
    {
        $result = TransactionDetectorService::extract('uber 32');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(32.0, $result['valor'], 0.01);
    }

    public function testExtractReversedFormat(): void
    {
        $result = TransactionDetectorService::extract('40 uber');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(40.0, $result['valor'], 0.01);
    }

    public function testExtractColloquialValue(): void
    {
        $result = TransactionDetectorService::extract('uns 30 de uber');
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(30.0, $result['valor'], 0.01);
    }

    public function testIncomeKeywordsDetected(): void
    {
        $incomeMessages = [
            'recebi 1000 de freela',
            'ganhei 500 de mesada',
            'entrou 3000 de salário',
        ];

        foreach ($incomeMessages as $msg) {
            $result = TransactionDetectorService::extract($msg);
            $this->assertNotNull($result, "Failed to extract: {$msg}");
            $this->assertEquals('receita', $result['tipo'], "Expected receita for: {$msg}");
        }
    }

    public function testCardNameDetection(): void
    {
        $result = TransactionDetectorService::extract('gastei 200 no nubank');
        $this->assertNotNull($result);
        $this->assertEquals('Nubank', $result['nome_cartao'] ?? null);
    }

    public function testPaymentMethodDetection(): void
    {
        $pixResult = TransactionDetectorService::extract('mandei pix 100 pro joao');
        $this->assertEquals('pix', $pixResult['forma_pagamento'] ?? null);

        $cardResult = TransactionDetectorService::extract('comprei sapato no cartão por 200');
        $this->assertEquals('cartao_credito', $cardResult['forma_pagamento'] ?? null);
    }

    public function testDataFieldAlwaysPresent(): void
    {
        $result = TransactionDetectorService::extract('gastei 50 no uber');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $result['data']);
    }

    public function testExtractRemovesMerchantContextFromDescription(): void
    {
        $result = TransactionDetectorService::extract('gastei 30 com produto de limpeza no mercado');

        $this->assertNotNull($result);
        $this->assertEquals('Produto De Limpeza', $result['descricao'] ?? null);
        $this->assertEquals('Mercado', $result['categoria_contexto'] ?? null);
    }

    public function testExtractKeepsMercadoWhenItIsTheActualDescription(): void
    {
        $result = TransactionDetectorService::extract('gastei 30 no mercado');

        $this->assertNotNull($result);
        $this->assertEquals('Mercado', $result['descricao'] ?? null);
        $this->assertArrayNotHasKey('categoria_contexto', $result);
    }

    public function testExtractStructuredCommaSeparatedTransaction(): void
    {
        $result = TransactionDetectorService::extract('Receita, comida, 30, hoje');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(30.0, $result['valor'], 0.01);
        $this->assertEquals('receita', $result['tipo']);
        $this->assertEquals('Comida', $result['descricao'] ?? null);
        $this->assertEquals(date('Y-m-d'), $result['data'] ?? null);
    }
}
