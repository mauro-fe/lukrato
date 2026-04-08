<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use Application\Container\ApplicationContainer;
use Application\Models\Categoria;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Validators\LancamentoValidator;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoValidatorDependencyResolutionTest extends TestCase
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

    public function testValidatorResolvesCategoriaRepositoryFromContainer(): void
    {
        $subcategoria = new Categoria();
        $subcategoria->parent_id = 33;
        $subcategoria->parent_categoria_id = 33;

        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $categoriaRepository->shouldReceive('belongsToUser')->once()->with(33, 10)->andReturnTrue();
        $categoriaRepository->shouldReceive('belongsToUser')->once()->with(44, 10)->andReturnTrue();
        $categoriaRepository->shouldReceive('find')->once()->with(44)->andReturn($subcategoria);

        $container = new IlluminateContainer();
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        ApplicationContainer::setInstance($container);

        $errors = [];
        $categoriaId = LancamentoValidator::validateCategoriaOwnership(33, 10, $errors);
        $subcategoriaId = LancamentoValidator::validateSubcategoriaOwnership(44, 33, 10, $errors);

        $this->assertSame(33, $categoriaId);
        $this->assertSame(44, $subcategoriaId);
        $this->assertSame([], $errors);
    }

    public function testValidatorResolvesContaRepositoryFromContainer(): void
    {
        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository->shouldReceive('belongsToUser')->once()->with(22, 10)->andReturnTrue();

        $container = new IlluminateContainer();
        $container->instance(ContaRepository::class, $contaRepository);
        ApplicationContainer::setInstance($container);

        $errors = [];
        $contaId = LancamentoValidator::validateContaOwnership(22, 10, $errors);

        $this->assertSame(22, $contaId);
        $this->assertSame([], $errors);
    }
}
