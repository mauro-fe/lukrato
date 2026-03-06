<?php

declare(strict_types=1);

namespace Tests\Integration;

use Application\DTO\ServiceResultDTO;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\Validators\LancamentoValidator;
use PHPUnit\Framework\TestCase;

/**
 * Testes de integração do fluxo de lançamentos.
 *
 * Estes testes verificam a integração entre Validators, DTOs e Services
 * sem depender de banco de dados. Os métodos que exigem DB são testados
 * apenas pela lógica de validação pré-DB.
 */
class LancamentoFlowTest extends TestCase
{
    // ─── Fluxo: Validação → Resultado ───────────────────────

    public function testValidPayloadPassesValidation(): void
    {
        $payload = [
            'tipo'      => 'despesa',
            'data'      => '2026-03-06',
            'valor'     => '250.00',
            'descricao' => 'Supermercado mensal',
            'conta_id'  => 1,
        ];

        $errors = LancamentoValidator::validateCreate($payload);
        $this->assertEmpty($errors, 'Payload válido não deveria retornar erros');
    }

    public function testInvalidPayloadIsRejectedBeforeService(): void
    {
        $payload = [
            'tipo'      => 'investimento', // inválido
            'data'      => '06/03/2026',   // formato errado
            'valor'     => '-50',          // negativo
            'descricao' => '',             // vazio
        ];

        $errors = LancamentoValidator::validateCreate($payload);

        $this->assertArrayHasKey('tipo', $errors);
        $this->assertArrayHasKey('data', $errors);
        $this->assertArrayHasKey('valor', $errors);
        $this->assertArrayHasKey('descricao', $errors);
    }

    // ─── Fluxo: Recorrência completa ────────────────────────

    public function testRecurrencePayloadValidation(): void
    {
        $payload = [
            'tipo'              => 'despesa',
            'data'              => '2026-03-06',
            'valor'             => '100.00',
            'descricao'         => 'Aluguel',
            'conta_id'          => 1,
            'recorrente'        => true,
            'recorrencia_freq'  => 'mensal',
            'recorrencia_total' => 12,
        ];

        $errors = LancamentoValidator::validateCreate($payload);
        $this->assertEmpty($errors);
    }

    public function testRecurrenceWithInvalidEndDate(): void
    {
        $payload = [
            'tipo'              => 'despesa',
            'data'              => '2026-06-01',
            'valor'             => '100.00',
            'descricao'         => 'Aluguel',
            'conta_id'          => 1,
            'recorrente'        => true,
            'recorrencia_freq'  => 'mensal',
            'recorrencia_fim'   => '2026-01-01', // antes da data do lançamento
        ];

        $errors = LancamentoValidator::validateCreate($payload);
        $this->assertArrayHasKey('recorrencia_fim', $errors);
    }

    // ─── Fluxo: sanitização de valor ────────────────────────

    public function testValorSanitizationInPipeline(): void
    {
        $rawValues = [
            'R$ 1.500,50' => 1500.50,
            '2500'        => 2500.00,
            '0,99'        => 0.99,
            '-100'        => 100.00,
        ];

        foreach ($rawValues as $input => $expected) {
            $sanitized = LancamentoValidator::sanitizeValor($input);
            $this->assertEquals($expected, $sanitized, "sanitizeValor('{$input}') deveria retornar {$expected}");
        }
    }

    // ─── ServiceResultDTO factory ───────────────────────────

    public function testServiceResultDTOOkFactory(): void
    {
        $result = ServiceResultDTO::ok('Criado com sucesso', ['id' => 123]);

        $this->assertTrue($result->success);
        $this->assertEquals(201, $result->httpCode);
        $this->assertEquals('Criado com sucesso', $result->message);
        $this->assertEquals(123, $result->data['id']);
    }

    public function testServiceResultDTOFailFactory(): void
    {
        $result = ServiceResultDTO::fail('Algo deu errado', 500);

        $this->assertFalse($result->success);
        $this->assertEquals(500, $result->httpCode);
        $this->assertEmpty($result->data);
    }

    public function testServiceResultDTOValidationFailFactory(): void
    {
        $errors = ['tipo' => 'Tipo inválido', 'valor' => 'Valor obrigatório'];
        $result = ServiceResultDTO::validationFail($errors);

        $this->assertFalse($result->success);
        $this->assertEquals(422, $result->httpCode);
        $this->assertArrayHasKey('errors', $result->data);
        $this->assertCount(2, $result->data['errors']);
    }

    // ─── Status Service: regras de domínio ──────────────────

    public function testStatusServiceDomainRulesExist(): void
    {
        $service = new LancamentoStatusService();

        // buildPagoPayload deve retornar array consistente com o estado
        $payloadPago = $service->buildPagoPayload(true);
        $this->assertEquals(1, $payloadPago['pago']);
        $this->assertNotNull($payloadPago['data_pagamento']);

        $payloadPendente = $service->buildPagoPayload(false);
        $this->assertEquals(0, $payloadPendente['pago']);
        $this->assertNull($payloadPendente['data_pagamento']);
    }
}
