<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Categoria;

use Application\Container\ApplicationContainer;
use Application\Repositories\CategoriaRepository;
use Application\Services\Categoria\SubcategoriaService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SubcategoriaServiceDependencyResolutionTest extends TestCase
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

    public function testSubcategoriaServiceResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);

        $container = new IlluminateContainer();
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(PlanLimitService::class, $planLimitService);
        ApplicationContainer::setInstance($container);

        $service = new SubcategoriaService();

        $this->assertSame($categoriaRepository, $this->readProperty($service, 'categoriaRepo'));
        $this->assertSame($planLimitService, $this->readProperty($service, 'planLimitService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
