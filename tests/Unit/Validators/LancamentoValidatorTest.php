<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use Application\Validators\LancamentoValidator;
use PHPUnit\Framework\TestCase;

class LancamentoValidatorTest extends TestCase
{
    // ─── Helper ────────────────────────────────────────────

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'tipo'        => 'despesa',
            'data'        => '2026-03-06',
            'valor'       => '150.50',
            'descricao'   => 'Supermercado',
            'conta_id'    => 1,
        ], $overrides);
    }

    // ─── Validação completa ─────────────────────────────────

    public function testValidPayloadReturnsNoErrors(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload());
        $this->assertEmpty($errors);
    }

    // ─── Tipo ───────────────────────────────────────────────

    public function testMissingTipoReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['tipo' => '']));
        $this->assertArrayHasKey('tipo', $errors);
    }

    public function testInvalidTipoReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['tipo' => 'investimento']));
        $this->assertArrayHasKey('tipo', $errors);
    }

    public function testValidTipoReceita(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['tipo' => 'receita']));
        $this->assertArrayNotHasKey('tipo', $errors);
    }

    // ─── Data ───────────────────────────────────────────────

    public function testMissingDataReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['data' => '']));
        $this->assertArrayHasKey('data', $errors);
    }

    public function testInvalidDateFormatReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['data' => '06/03/2026']));
        $this->assertArrayHasKey('data', $errors);
    }

    public function testInvalidDateMonthReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['data' => '2026-13-01']));
        $this->assertArrayHasKey('data', $errors);
    }

    public function testInvalidDateDayReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['data' => '2026-01-32']));
        $this->assertArrayHasKey('data', $errors);
    }

    // ─── Valor ──────────────────────────────────────────────

    public function testMissingValorReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['valor' => '']));
        $this->assertArrayHasKey('valor', $errors);
    }

    public function testZeroValorReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['valor' => '0']));
        $this->assertArrayHasKey('valor', $errors);
    }

    public function testNegativeValorReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['valor' => '-50']));
        $this->assertArrayHasKey('valor', $errors);
    }

    public function testBrazilianFormatValor(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['valor' => 'R$ 1.500,50']));
        $this->assertArrayNotHasKey('valor', $errors);
    }

    public function testNonNumericValorReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['valor' => 'abc']));
        $this->assertArrayHasKey('valor', $errors);
    }

    // ─── Descrição ──────────────────────────────────────────

    public function testMissingDescricaoReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['descricao' => '']));
        $this->assertArrayHasKey('descricao', $errors);
    }

    public function testDescricaoTooLongReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'descricao' => str_repeat('A', 191),
        ]));
        $this->assertArrayHasKey('descricao', $errors);
    }

    public function testDescricaoExact190IsValid(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'descricao' => str_repeat('A', 190),
        ]));
        $this->assertArrayNotHasKey('descricao', $errors);
    }

    // ─── Observação ─────────────────────────────────────────

    public function testObservacaoTooLongReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'observacao' => str_repeat('B', 501),
        ]));
        $this->assertArrayHasKey('observacao', $errors);
    }

    public function testObservacaoOptional(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['observacao' => '']));
        $this->assertArrayNotHasKey('observacao', $errors);
    }

    // ─── Conta ──────────────────────────────────────────────

    public function testMissingContaAndCartaoReturnsError(): void
    {
        $payload = $this->validPayload();
        unset($payload['conta_id']);
        $errors = LancamentoValidator::validateCreate($payload);
        $this->assertArrayHasKey('conta_id', $errors);
    }

    public function testCartaoCreditoIdSubstitutesContaId(): void
    {
        $payload = $this->validPayload();
        unset($payload['conta_id']);
        $payload['cartao_credito_id'] = 5;
        $errors = LancamentoValidator::validateCreate($payload);
        $this->assertArrayNotHasKey('conta_id', $errors);
    }

    // ─── Forma de pagamento ─────────────────────────────────

    public function testValidFormaPagamento(): void
    {
        $formas = ['pix', 'cartao_credito', 'cartao_debito', 'dinheiro', 'boleto', 'transferencia'];
        foreach ($formas as $forma) {
            $errors = LancamentoValidator::validateCreate($this->validPayload(['forma_pagamento' => $forma]));
            $this->assertArrayNotHasKey('forma_pagamento', $errors, "Forma '{$forma}' deveria ser válida");
        }
    }

    public function testInvalidFormaPagamentoReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload(['forma_pagamento' => 'bitcoin']));
        $this->assertArrayHasKey('forma_pagamento', $errors);
    }

    // ─── Recorrência ────────────────────────────────────────

    public function testRecorrenciaSemFrequenciaReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente' => true,
        ]));
        $this->assertArrayHasKey('recorrencia_freq', $errors);
    }

    public function testRecorrenciaComFrequenciaInvalidaReturnsError(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente'      => true,
            'recorrencia_freq' => 'diaria',
        ]));
        $this->assertArrayHasKey('recorrencia_freq', $errors);
    }

    public function testRecorrenciaComFrequenciaValidaIsOk(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente'      => true,
            'recorrencia_freq' => 'mensal',
        ]));
        $this->assertArrayNotHasKey('recorrencia_freq', $errors);
    }

    public function testRecorrenciaTotalMinimo(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente'        => true,
            'recorrencia_freq'  => 'mensal',
            'recorrencia_total' => 1,
        ]));
        $this->assertArrayHasKey('recorrencia_total', $errors);
    }

    public function testRecorrenciaTotalMaximo(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente'        => true,
            'recorrencia_freq'  => 'mensal',
            'recorrencia_total' => 121,
        ]));
        $this->assertArrayHasKey('recorrencia_total', $errors);
    }

    public function testRecorrenciaTotalExact120Valid(): void
    {
        $errors = LancamentoValidator::validateCreate($this->validPayload([
            'recorrente'        => true,
            'recorrencia_freq'  => 'mensal',
            'recorrencia_total' => 120,
        ]));
        $this->assertArrayNotHasKey('recorrencia_total', $errors);
    }

    // ─── sanitizeValor ──────────────────────────────────────

    public function testSanitizeValorBrazilianFormat(): void
    {
        $result = LancamentoValidator::sanitizeValor('R$ 1.500,50');
        $this->assertEquals(1500.50, $result);
    }

    public function testSanitizeValorNegativeBecomesPositive(): void
    {
        // sanitizeValor removes dots (thousand separator), so '-100' without dots
        $result = LancamentoValidator::sanitizeValor('-100');
        $this->assertEquals(100.00, $result);
    }

    public function testSanitizeValorNumericInput(): void
    {
        $result = LancamentoValidator::sanitizeValor(250.75);
        $this->assertEquals(250.75, $result);
    }

    public function testSanitizeValorRoundsToTwoDecimals(): void
    {
        // Dots are stripped as thousand separators, comma is decimal
        $result = LancamentoValidator::sanitizeValor('100,999');
        $this->assertEquals(101.00, $result);
    }
}
