<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Core\Router;
use Application\Core\Routing\ErrorResponseFactory as RoutingErrorResponseFactory;
use Application\Core\Routing\HttpExceptionHandler;
use Application\Core\Routing\MiddlewareResolver as RoutingMiddlewareResolver;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CoreDependencyResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
        Router::reset();
    }

    protected function tearDown(): void
    {
        Router::reset();
        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testCoreResolversUseContainerInstancesWhenAvailable(): void
    {
        $middlewareResolver = Mockery::mock(RoutingMiddlewareResolver::class);
        $errorResponseFactory = Mockery::mock(RoutingErrorResponseFactory::class);
        $exceptionHandler = Mockery::mock(HttpExceptionHandler::class);
        $runtimeConfig = new InfrastructureRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(InfrastructureRuntimeConfig::class, $runtimeConfig);
        $container->instance(RoutingMiddlewareResolver::class, $middlewareResolver);
        $container->instance(RoutingErrorResponseFactory::class, $errorResponseFactory);
        $container->instance(HttpExceptionHandler::class, $exceptionHandler);
        ApplicationContainer::setInstance($container);

        $resolvedHandler = new HttpExceptionHandler();
        $request = new Request(['REQUEST_METHOD' => 'GET']);

        $this->assertSame($errorResponseFactory, $this->readProperty($resolvedHandler, 'errorResponseFactory'));
        $this->assertSame($runtimeConfig, $this->readProperty($resolvedHandler, 'runtimeConfig'));
        $this->assertSame($runtimeConfig, $this->readProperty($request, 'runtimeConfig'));
        $this->assertSame($middlewareResolver, $this->invokePrivateStaticMethod(Router::class, 'middlewareResolver'));
        $this->assertSame($errorResponseFactory, $this->invokePrivateStaticMethod(Router::class, 'errorResponseFactory'));
        $this->assertSame($exceptionHandler, $this->invokePrivateStaticMethod(Router::class, 'exceptionHandler'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function invokePrivateStaticMethod(string $className, string $method): mixed
    {
        $reflection = new \ReflectionMethod($className, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null);
    }
}
