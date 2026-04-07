<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financas;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Financas\ResumoController;
use Application\Core\Exceptions\AuthException;
use Application\UseCases\Financas\GetFinancasInsightsUseCase;
use Application\UseCases\Financas\GetFinancasResumoUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ResumoControllerTest extends TestCase
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

    public function testResumoThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ResumoController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->resumo();
    }

    public function testConstructorResolvesUseCasesFromContainerWhenAvailable(): void
    {
        $resumoUseCase = Mockery::mock(GetFinancasResumoUseCase::class);
        $insightsUseCase = Mockery::mock(GetFinancasInsightsUseCase::class);

        $container = new IlluminateContainer();
        $container->instance(GetFinancasResumoUseCase::class, $resumoUseCase);
        $container->instance(GetFinancasInsightsUseCase::class, $insightsUseCase);
        ApplicationContainer::setInstance($container);

        $controller = new ResumoController();

        $this->assertSame($resumoUseCase, $this->readProperty($controller, 'getFinancasResumoUseCase'));
        $this->assertSame($insightsUseCase, $this->readProperty($controller, 'getFinancasInsightsUseCase'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
