<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\ExceptionHandler;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Core\Routing\HttpExceptionHandler;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    private ?string $originalAppEnv = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAppEnv = $_ENV['APP_ENV'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalAppEnv === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $this->originalAppEnv;
        }

        parent::tearDown();
    }

    public function testHandleMapsAuthExceptionToHttpResponse(): void
    {
        $handler = new HttpExceptionHandler();

        $response = $handler->handle(new AuthException('Acesso negado', 403));

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Acesso negado', $payload['message']);
        $this->assertIsString($payload['request_id'] ?? null);
    }

    public function testHandleMapsValidationExceptionToValidationResponse(): void
    {
        $handler = new HttpExceptionHandler();

        $response = $handler->handle(new ValidationException([
            'email' => 'E-mail inválido',
        ]));

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Validation failed', $payload['message']);
        $this->assertSame([
            'email' => 'E-mail inválido',
        ], $payload['errors'] ?? null);
        $this->assertIsString($payload['request_id'] ?? null);
    }

    public function testHandleReturnsJsonInternalServerErrorForApiRequestInProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';

        $handler = new HttpExceptionHandler();
        $request = new Request([
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $handler->handle(new \RuntimeException('boom'), ['path' => '/api/test'], $request);

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Erro interno no servidor.', $payload['message']);
        $this->assertIsString($payload['error_id'] ?? null);
        $this->assertIsString($payload['request_id'] ?? null);
    }

    public function testLegacyCoreExceptionHandlerRemainsCompatible(): void
    {
        $legacyHandler = new ExceptionHandler();

        $this->assertInstanceOf(HttpExceptionHandler::class, $legacyHandler);
    }
}
