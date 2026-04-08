<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use Application\Container\ApplicationContainer;
use Application\Services\Cartao\CartaoApiWorkflowService;
use Application\Services\Cartao\CartaoBillingDateService;
use Application\Services\Cartao\CartaoCreditoLancamentoService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaPaymentService;
use Application\Services\Cartao\CartaoFaturaReadService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Cartao\CartaoFaturaSupportService;
use Application\Services\Cartao\CartaoLifecycleService;
use Application\Services\Cartao\CartaoLimitUpdaterService;
use Application\Services\Cartao\CartaoMonitoringService;
use Application\Services\Cartao\CartaoPostSaleService;
use Application\Services\Cartao\RecorrenciaCartaoService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Plan\PlanLimitService;
use Application\Validators\CartaoCreditoValidator;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CartaoServicesDependencyResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testCartaoServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $billingDateService = Mockery::mock(CartaoBillingDateService::class);
        $faturaSupportService = Mockery::mock(CartaoFaturaSupportService::class);
        $limitUpdaterService = Mockery::mock(CartaoLimitUpdaterService::class);
        $postSaleService = Mockery::mock(CartaoPostSaleService::class);
        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $recorrenciaService = Mockery::mock(RecorrenciaCartaoService::class);
        $validator = Mockery::mock(CartaoCreditoValidator::class);
        $lifecycleService = Mockery::mock(CartaoLifecycleService::class);
        $monitoringService = Mockery::mock(CartaoMonitoringService::class);
        $readService = Mockery::mock(CartaoFaturaReadService::class);
        $paymentService = Mockery::mock(CartaoFaturaPaymentService::class);

        $container = new IlluminateContainer();
        $container->instance(CartaoBillingDateService::class, $billingDateService);
        $container->instance(CartaoFaturaSupportService::class, $faturaSupportService);
        $container->instance(CartaoLimitUpdaterService::class, $limitUpdaterService);
        $container->instance(CartaoPostSaleService::class, $postSaleService);
        $container->instance(CartaoCreditoService::class, $cartaoService);
        $container->instance(CartaoFaturaService::class, $faturaService);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(RecorrenciaCartaoService::class, $recorrenciaService);
        $container->instance(CartaoCreditoValidator::class, $validator);
        $container->instance(CartaoLifecycleService::class, $lifecycleService);
        $container->instance(CartaoMonitoringService::class, $monitoringService);
        $container->instance(CartaoFaturaReadService::class, $readService);
        $container->instance(CartaoFaturaPaymentService::class, $paymentService);
        ApplicationContainer::setInstance($container);

        $cartaoLancamentoService = new CartaoCreditoLancamentoService();
        $workflowService = new CartaoApiWorkflowService();
        $cartaoCreditoService = new CartaoCreditoService();
        $faturaPaymentService = new CartaoFaturaPaymentService();
        $faturaServiceResolved = new CartaoFaturaService();
        $recorrenciaServiceResolved = new RecorrenciaCartaoService();

        $this->assertSame($billingDateService, $this->readProperty($cartaoLancamentoService, 'billingDateService'));
        $this->assertSame($faturaSupportService, $this->readProperty($cartaoLancamentoService, 'faturaSupportService'));
        $this->assertSame($limitUpdaterService, $this->readProperty($cartaoLancamentoService, 'limitUpdaterService'));
        $this->assertSame($postSaleService, $this->readProperty($cartaoLancamentoService, 'postSaleService'));

        $this->assertSame($cartaoService, $this->readProperty($workflowService, 'cartaoService'));
        $this->assertSame($faturaService, $this->readProperty($workflowService, 'faturaService'));
        $this->assertSame($planLimitService, $this->readProperty($workflowService, 'planLimitService'));
        $this->assertSame($achievementService, $this->readProperty($workflowService, 'achievementService'));
        $this->assertSame($recorrenciaService, $this->readProperty($workflowService, 'recorrenciaService'));

        $this->assertSame($validator, $this->readProperty($cartaoCreditoService, 'validator'));
        $this->assertSame($lifecycleService, $this->readProperty($cartaoCreditoService, 'lifecycleService'));
        $this->assertSame($monitoringService, $this->readProperty($cartaoCreditoService, 'monitoringService'));

        $this->assertSame($readService, $this->readProperty($faturaPaymentService, 'readService'));

        $this->assertSame($readService, $this->readProperty($faturaServiceResolved, 'readService'));
        $this->assertSame($paymentService, $this->readProperty($faturaServiceResolved, 'paymentService'));

        $this->assertSame($billingDateService, $this->readProperty($recorrenciaServiceResolved, 'billingDateService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
