<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use Application\Core\Request;
use Application\Core\Response;
use Application\Services\Auth\RegistrationResponseHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class RegistrationResponseHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
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

    public function testSuccessReturnsJsonResponseForAjax(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isAjax')->once()->andReturn(true);

        $handler = new RegistrationResponseHandler($request);
        $response = $handler->success(['redirect' => 'dashboard'], true);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Conta criada com Google e login realizado com sucesso!',
            'data' => [
                'redirect' => 'dashboard',
                'requires_verification' => false,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testValidationErrorReturnsRedirectResponseForWeb(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isAjax')->once()->andReturn(false);

        $handler = new RegistrationResponseHandler($request);
        $response = $handler->validationError(['email' => 'Email ja cadastrado']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/lukrato/login', $response->getHeaders()['Location']);
        $this->assertSame('Email ja cadastrado', $_SESSION['error']);
        $this->assertSame('register', $_SESSION['auth_active_tab']);
    }

    public function testGeneralErrorReturnsProvidedMessageAndStatusForAjax(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isAjax')->once()->andReturn(true);

        $handler = new RegistrationResponseHandler($request);
        $response = $handler->generalError(
            'Limite de novas contas atingido para sua rede. Tente novamente mais tarde.',
            429
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Limite de novas contas atingido para sua rede. Tente novamente mais tarde.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
