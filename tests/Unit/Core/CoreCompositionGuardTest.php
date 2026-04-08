<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

class CoreCompositionGuardTest extends TestCase
{
    public function testBootstrapAndCoreHotspotsDoNotInstantiateDependenciesInline(): void
    {
        $application = (string) file_get_contents('Application/Bootstrap/Application.php');
        $middlewareResolver = (string) file_get_contents('Application/Core/Routing/MiddlewareResolver.php');
        $httpExceptionHandler = (string) file_get_contents('Application/Core/Routing/HttpExceptionHandler.php');
        $router = (string) file_get_contents('Application/Core/Router.php');

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->sessionManager\s*=\s*new\s+SessionManager\s*\(/',
            $application,
            'Application não deve instanciar SessionManager diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->securityHeaders\s*=\s*new\s+SecurityHeaders\s*\(/',
            $application,
            'Application não deve instanciar SecurityHeaders diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->requestHandler\s*=\s*new\s+RequestHandler\s*\(/',
            $application,
            'Application não deve instanciar RequestHandler diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->responseEmitter\s*=\s*new\s+ResponseEmitter\s*\(/',
            $application,
            'Application não deve instanciar ResponseEmitter diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CacheService\s*\(/',
            $middlewareResolver,
            'MiddlewareResolver não deve instanciar CacheService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+ErrorResponseFactory\s*\(/',
            $httpExceptionHandler,
            'HttpExceptionHandler não deve instanciar ErrorResponseFactory diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+RoutingMiddlewareResolver\s*\(/',
            $router,
            'Router não deve instanciar RoutingMiddlewareResolver diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/return\s+new\s+RoutingErrorResponseFactory\s*\(/',
            $router,
            'Router não deve instanciar RoutingErrorResponseFactory diretamente.'
        );
    }
}
