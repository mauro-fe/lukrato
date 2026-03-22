<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Middlewares\AIQuotaMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AIQuotaMiddlewareTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testHandleThrowsUnauthorizedResponseWhenUserIsMissing(): void
    {
        try {
            AIQuotaMiddleware::handle();
            $this->fail('Era esperado HttpResponseException.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();

            $this->assertSame(401, $response->getStatusCode());
            $this->assertSame([
                'success' => false,
                'message' => 'Não autenticado',
            ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        }
    }
}
