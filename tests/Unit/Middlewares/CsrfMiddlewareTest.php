<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Middlewares\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CsrfMiddlewareTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->startIsolatedSession('csrf-middleware-test');
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testHandleAcceptsTokenFromHeader(): void
    {
        $token = CsrfMiddleware::generateToken('test_form');

        $request = new Request(
            server: ['REQUEST_METHOD' => 'POST'],
            headers: ['X-CSRF-Token' => $token]
        );

        CsrfMiddleware::handle($request, 'test_form');

        $this->assertTrue(true);
    }

    public function testHandleAcceptsTokenFromPostBody(): void
    {
        $token = CsrfMiddleware::generateToken('test_form');

        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            post: ['csrf_token' => $token]
        );

        CsrfMiddleware::handle($request, 'test_form');

        $this->assertTrue(true);
    }

    public function testHandleAcceptsTokenFromJsonBody(): void
    {
        $token = CsrfMiddleware::generateToken('test_form');

        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            rawInput: json_encode(['csrf_token' => $token], JSON_THROW_ON_ERROR)
        );

        CsrfMiddleware::handle($request, 'test_form');

        $this->assertTrue(true);
    }

    public function testHandleRejectsTokenFromQueryString(): void
    {
        $token = CsrfMiddleware::generateToken('test_form');

        $request = new Request(
            server: ['REQUEST_METHOD' => 'POST'],
            query: ['csrf_token' => $token]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(419);

        CsrfMiddleware::handle($request, 'test_form');
    }
}
