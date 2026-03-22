<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;
use Application\Services\Cartao\CartaoApiWorkflowService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CartaoApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateCardReturnsForbiddenWhenPlanLimitIsReached(): void
    {
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canCreateCartao')
            ->once()
            ->with(55)
            ->andReturn([
                'allowed' => false,
                'message' => 'Limite de cartoes atingido',
                'upgrade_url' => '/upgrade',
                'limit' => 3,
                'used' => 3,
                'remaining' => 0,
            ]);

        $service = new CartaoApiWorkflowService(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            $planLimitService
        );

        $result = $service->createCard(55, []);

        $this->assertFalse($result['success']);
        $this->assertSame(403, $result['status']);
        $this->assertSame('Limite de cartoes atingido', $result['message']);
        $this->assertTrue($result['errors']['limit_reached']);
    }

    public function testCreateCardReturnsAchievementsOnSuccess(): void
    {
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canCreateCartao')
            ->once()
            ->with(77)
            ->andReturn([
                'allowed' => true,
            ]);

        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService
            ->shouldReceive('criarCartao')
            ->once()
            ->with(Mockery::type(CreateCartaoCreditoDTO::class))
            ->andReturn([
                'success' => true,
                'id' => 901,
                'data' => ['nome' => 'Visa Black'],
            ]);

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(77, 'card_created')
            ->andReturn([
                ['slug' => 'first_card'],
            ]);

        $service = new CartaoApiWorkflowService(
            $cartaoService,
            Mockery::mock(CartaoFaturaService::class),
            $planLimitService,
            $achievementService
        );

        $result = $service->createCard(77, [
            'nome' => 'Visa Black',
            'bandeira' => 'visa',
            'limite_total' => 5000,
            'dia_vencimento' => 10,
            'dia_fechamento' => 3,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(901, $result['data']['id']);
        $this->assertSame([
            'achievements' => [
                ['slug' => 'first_card'],
            ],
        ], $result['data']['gamification']);
    }

    public function testUpdateCardMapsNotFoundMessagesTo404(): void
    {
        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService
            ->shouldReceive('atualizarCartao')
            ->once()
            ->with(10, 88, Mockery::type(UpdateCartaoCreditoDTO::class))
            ->andReturn([
                'success' => false,
                'message' => 'Cartao nao encontrado',
            ]);

        $service = new CartaoApiWorkflowService(
            $cartaoService,
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class)
        );

        $result = $service->updateCard(10, 88, [
            'nome' => 'Atualizado',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Cartao nao encontrado', $result['message']);
    }

    public function testGetAlertsCombinesAndSortsDueDatesAndLowLimits(): void
    {
        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService
            ->shouldReceive('verificarLimitesBaixos')
            ->once()
            ->with(99)
            ->andReturn([
                [
                    'gravidade' => 'atencao',
                    'tipo' => 'limite',
                ],
            ]);

        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $faturaService
            ->shouldReceive('verificarVencimentosProximos')
            ->once()
            ->with(99, 7)
            ->andReturn([
                [
                    'gravidade' => 'critico',
                    'tipo' => 'vencimento',
                ],
            ]);

        $service = new CartaoApiWorkflowService(
            $cartaoService,
            $faturaService,
            Mockery::mock(PlanLimitService::class)
        );

        $result = $service->getAlerts(99);

        $this->assertSame(2, $result['total']);
        $this->assertSame('critico', $result['alertas'][0]['gravidade']);
        $this->assertSame('atencao', $result['alertas'][1]['gravidade']);
        $this->assertSame([
            'vencimentos' => 1,
            'limites_baixos' => 1,
        ], $result['por_tipo']);
    }
}
