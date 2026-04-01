<?php

declare(strict_types=1);

namespace {
    if (!function_exists('vite_scripts')) {
        function vite_scripts(string ...$entries): string
        {
            return '';
        }
    }
}

namespace Tests\Unit\Controllers\Site {

    use Application\Controllers\Site\AprendaController;
    use Application\Repositories\BlogPostRepository;
    use Mockery;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use PHPUnit\Framework\TestCase;

    class AprendaControllerTest extends TestCase
    {
        use MockeryPHPUnitIntegration;

        public function testShowReturns404WhenPostDoesNotExist(): void
        {
            $repo = Mockery::mock(BlogPostRepository::class);
            $repo
                ->shouldReceive('findPublishedBySlug')
                ->once()
                ->with('slug-invalido')
                ->andReturnNull();
            $repo->shouldNotReceive('findRelated');

            $controller = new AprendaController($repo);

            $response = $controller->show('slug-invalido');

            $this->assertSame(404, $response->getStatusCode());
            $this->assertStringContainsString('404', $response->getContent());
        }
    }
}
