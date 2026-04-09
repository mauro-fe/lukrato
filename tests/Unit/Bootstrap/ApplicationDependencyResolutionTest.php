<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Application\Bootstrap\Application;
use Application\Bootstrap\ErrorHandler;
use Application\Bootstrap\RequestHandler;
use Application\Bootstrap\SecurityHeaders;
use Application\Bootstrap\SessionManager;
use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\ResponseEmitter;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ApplicationDependencyResolutionTest extends TestCase
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

    public function testApplicationResolvesBootstrapDependenciesFromContainerWhenAvailable(): void
    {
        $errorHandler = Mockery::mock(ErrorHandler::class);
        $sessionManager = Mockery::mock(SessionManager::class);
        $securityHeaders = Mockery::mock(SecurityHeaders::class);
        $requestHandler = Mockery::mock(RequestHandler::class);
        $responseEmitter = Mockery::mock(ResponseEmitter::class);

        $container = new IlluminateContainer();
        $container->instance(ErrorHandler::class, $errorHandler);
        $container->instance(SessionManager::class, $sessionManager);
        $container->instance(SecurityHeaders::class, $securityHeaders);
        $container->instance(RequestHandler::class, $requestHandler);
        $container->instance(ResponseEmitter::class, $responseEmitter);
        ApplicationContainer::setInstance($container);

        $application = new Application();

        $this->assertSame($errorHandler, $this->readProperty($application, 'errorHandler'));
        $this->assertSame($sessionManager, $this->readProperty($application, 'sessionManager'));
        $this->assertSame($securityHeaders, $this->readProperty($application, 'securityHeaders'));
        $this->assertSame($requestHandler, $this->readProperty($application, 'requestHandler'));
        $this->assertSame($responseEmitter, $this->readProperty($application, 'responseEmitter'));
    }

    public function testErrorHandlerResolvesResponseEmitterFromContainerWhenAvailable(): void
    {
        $responseEmitter = Mockery::mock(ResponseEmitter::class);

        $container = new IlluminateContainer();
        $container->instance(ResponseEmitter::class, $responseEmitter);
        ApplicationContainer::setInstance($container);

        $handler = new ErrorHandler();

        $this->assertSame($responseEmitter, $this->readProperty($handler, 'responseEmitter'));
    }

    public function testBootstrapComponentsResolveInfrastructureRuntimeConfigFromContainerWhenAvailable(): void
    {
        $runtimeConfig = new InfrastructureRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(InfrastructureRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $sessionManager = new SessionManager();
        $securityHeaders = new SecurityHeaders();
        $errorHandler = new ErrorHandler();

        $this->assertSame($runtimeConfig, $this->readProperty($sessionManager, 'runtimeConfig'));
        $this->assertSame($runtimeConfig, $this->readProperty($securityHeaders, 'runtimeConfig'));
        $this->assertSame($runtimeConfig, $this->readProperty($errorHandler, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
