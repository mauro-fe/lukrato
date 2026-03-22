<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fatura;

use Application\Services\Fatura\FaturaApiWorkflowService;
use Application\Services\Fatura\FaturaService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FaturaApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListInvoicesRejectsInvalidMonth(): void
    {
        $service = new FaturaApiWorkflowService(Mockery::mock(FaturaService::class));

        $result = $service->listInvoices(15, [
            'mes' => '13',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Mês inválido. Deve estar entre 1 e 12', $result['message']);
    }

    public function testCreateInvoiceReturnsMissingFieldsPayload(): void
    {
        $service = new FaturaApiWorkflowService(Mockery::mock(FaturaService::class));

        $result = $service->createInvoice(22, [
            'descricao' => 'Fatura parcial',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Campos obrigatórios ausentes', $result['message']);
        $this->assertSame([
            'missing_fields' => ['cartao_id', 'valor_total', 'data_vencimento'],
        ], $result['errors']);
    }

    public function testShowInvoiceReturnsNotFoundWhenOwnershipCheckFails(): void
    {
        $faturaService = Mockery::mock(FaturaService::class);
        $faturaService
            ->shouldReceive('buscar')
            ->once()
            ->with(10, 30)
            ->andReturn(null);

        $service = new FaturaApiWorkflowService($faturaService);
        $result = $service->showInvoice(10, 30);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Fatura não encontrada', $result['message']);
    }

    public function testDeleteInstallmentReturnsDeletedItemsCount(): void
    {
        $faturaService = Mockery::mock(FaturaService::class);
        $faturaService
            ->shouldReceive('buscar')
            ->once()
            ->with(8, 41)
            ->andReturn([
                'id' => 8,
                'status' => 'pendente',
            ]);
        $faturaService
            ->shouldReceive('excluirParcelamento')
            ->once()
            ->with(55, 41)
            ->andReturn([
                'success' => true,
                'message' => 'Parcelamento excluído com sucesso',
                'itens_excluidos' => 4,
            ]);

        $service = new FaturaApiWorkflowService($faturaService);
        $result = $service->deleteInstallment(8, 55, 41);

        $this->assertTrue($result['success']);
        $this->assertSame('Parcelamento excluído com sucesso', $result['message']);
        $this->assertSame([
            'itens_excluidos' => 4,
        ], $result['data']);
    }
}
