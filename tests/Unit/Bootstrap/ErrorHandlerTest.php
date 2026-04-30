<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Application\Bootstrap\ErrorHandler;
use Application\Core\Response;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    private string|false $previousErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousErrorLog = ini_get('error_log');
        $logFile = BASE_PATH . '/tests/.runtime/error-handler-test.log';
        if (file_exists($logFile)) {
            @unlink($logFile);
        }

        ini_set('error_log', $logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);

        parent::tearDown();
    }

    public function testHandleRequestErrorReturnsDevelopmentHtmlResponse(): void
    {
        $handler = new ErrorHandler('development');
        $exception = new \RuntimeException('Falha no parser');

        $response = $handler->handleRequestError($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertStringContainsString('Erro na requisição', $response->getContent());
        $this->assertStringContainsString('Falha no parser', $response->getContent());
    }

    public function testHandleRequestErrorReturnsProductionHtmlResponse(): void
    {
        $handler = new ErrorHandler('production');
        $exception = new \RuntimeException('Falha no bootstrap');

        $response = $handler->handleRequestError($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertNotSame('', $response->getContent());
    }

    public function testHandleRequestErrorSanitizesProductionLogOutput(): void
    {
        $handler = new ErrorHandler('production');
        $exception = new \RuntimeException(
            'Falha token=abc123 email john.doe@gmail.com /reset?selector=sel123&validator=val456'
        );

        $handler->handleRequestError($exception);

        $contents = file_get_contents(BASE_PATH . '/tests/.runtime/error-handler-test.log');

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('abc123', $contents);
        $this->assertStringNotContainsString('john.doe@gmail.com', $contents);
        $this->assertStringContainsString('token=[REDACTED]', $contents);
        $this->assertStringContainsString('j***@gmail.com', $contents);
        $this->assertStringContainsString('selector=%5BREDACTED%5D', $contents);
        $this->assertStringContainsString('validator=%5BREDACTED%5D', $contents);
    }
}
