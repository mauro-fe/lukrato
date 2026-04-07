<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financas;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Financas\MetricsController;
use Application\Core\Exceptions\AuthException;
use Application\UseCases\Financas\GetFinanceiroMetricsUseCase;
use Application\UseCases\Financas\GetFinanceiroOptionsUseCase;
use Application\UseCases\Financas\GetFinanceiroTransactionsUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MetricsControllerTest extends TestCase
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

    public function testMetricsThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new MetricsController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->metrics();
    }

    public function testConstructorResolvesUseCasesFromContainerWhenAvailable(): void
    {
        $metricsUseCase = Mockery::mock(GetFinanceiroMetricsUseCase::class);
        $transactionsUseCase = Mockery::mock(GetFinanceiroTransactionsUseCase::class);
        $optionsUseCase = Mockery::mock(GetFinanceiroOptionsUseCase::class);

        $container = new IlluminateContainer();
        $container->instance(GetFinanceiroMetricsUseCase::class, $metricsUseCase);
        $container->instance(GetFinanceiroTransactionsUseCase::class, $transactionsUseCase);
        $container->instance(GetFinanceiroOptionsUseCase::class, $optionsUseCase);
        ApplicationContainer::setInstance($container);

        $controller = new MetricsController();

        $this->assertSame($metricsUseCase, $this->readProperty($controller, 'getMetricsUseCase'));
        $this->assertSame($transactionsUseCase, $this->readProperty($controller, 'getTransactionsUseCase'));
        $this->assertSame($optionsUseCase, $this->readProperty($controller, 'getOptionsUseCase'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
