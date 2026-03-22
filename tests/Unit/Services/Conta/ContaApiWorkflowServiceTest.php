<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Conta;

use Application\DTO\UpdateContaDTO;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Conta\ContaService;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ContaApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListAccountsParsesQueryFlagsBeforeDelegatingToService(): void
    {
        $contaService = Mockery::mock(ContaService::class);
        $contaService
            ->shouldReceive('listarContas')
            ->once()
            ->with(7, true, false, true, '2026-03')
            ->andReturn([
                ['id' => 1, 'nome' => 'Carteira'],
            ]);

        $service = new ContaApiWorkflowService($contaService, Mockery::mock(PlanLimitService::class));

        $result = $service->listAccounts(7, [
            'archived' => '1',
            'only_active' => '0',
            'with_balances' => '1',
            'month' => ' 2026-03 ',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([
            ['id' => 1, 'nome' => 'Carteira'],
        ], $result['data']);
    }

    public function testCreateAccountReturnsForbiddenPayloadWhenPlanLimitIsReached(): void
    {
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canCreateConta')
            ->once()
            ->with(10)
            ->andReturn([
                'allowed' => false,
                'message' => 'Limite atingido',
                'upgrade_url' => '/upgrade',
                'limit' => 2,
                'used' => 2,
                'remaining' => 0,
            ]);

        $service = new ContaApiWorkflowService(Mockery::mock(ContaService::class), $planLimitService);

        $result = $service->createAccount(10, [
            'nome' => 'Conta Teste',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(403, $result['status']);
        $this->assertSame('Limite atingido', $result['message']);
        $this->assertSame([
            'limit_reached' => true,
            'upgrade_url' => '/upgrade',
            'limit_info' => [
                'limit' => 2,
                'used' => 2,
                'remaining' => 0,
            ],
        ], $result['errors']);
    }

    public function testUpdateAccountMapsNotFoundMessageTo404(): void
    {
        $contaService = Mockery::mock(ContaService::class);
        $contaService
            ->shouldReceive('atualizarConta')
            ->once()
            ->with(15, 7, Mockery::type(UpdateContaDTO::class))
            ->andReturn([
                'success' => false,
                'message' => 'Conta não encontrada',
                'errors' => [
                    'id' => 'Conta inválida',
                ],
            ]);

        $service = new ContaApiWorkflowService($contaService, Mockery::mock(PlanLimitService::class));

        $result = $service->updateAccount(15, 7, [
            'nome' => 'Conta Atualizada',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Conta não encontrada', $result['message']);
        $this->assertSame([
            'id' => 'Conta inválida',
        ], $result['errors']);
    }

    public function testCreateInstituicaoRequiresName(): void
    {
        $service = new ContaApiWorkflowService(Mockery::mock(ContaService::class), Mockery::mock(PlanLimitService::class));

        $result = $service->createInstituicao([]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Nome da instituição é obrigatório', $result['message']);
    }
}
