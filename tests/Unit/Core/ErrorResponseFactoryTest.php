<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\ErrorResponseFactory;
use Application\Core\Request;
use Application\Core\Routing\ErrorResponseFactory as RoutingErrorResponseFactory;
use PHPUnit\Framework\TestCase;

class ErrorResponseFactoryTest extends TestCase
{
    public function testNotFoundReturnsJsonResponseForApiRequest(): void
    {
        $factory = new RoutingErrorResponseFactory();
        $request = new Request([
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $factory->notFound($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Recurso não encontrado',
            'code' => 'RESOURCE_NOT_FOUND',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMethodNotAllowedAddsAllowHeader(): void
    {
        $factory = new RoutingErrorResponseFactory();
        $request = new Request([
            'REQUEST_METHOD' => 'DELETE',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $factory->methodNotAllowed($request, ['GET', 'POST']);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->getHeaders()['Allow'] ?? null);
    }

    public function testLegacyCoreErrorResponseFactoryRemainsCompatible(): void
    {
        $legacyFactory = new ErrorResponseFactory();

        $this->assertInstanceOf(RoutingErrorResponseFactory::class, $legacyFactory);
    }
}
