<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Container\ApplicationContainer;
use Application\Controllers\ApiController;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Core\Response;
use Application\DTO\ServiceResultDTO;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ApiControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    private TestableApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
        $this->resetSessionState();
        Auth::resolveUserUsing(null);

        $this->controller = new TestableApiController(
            Mockery::mock(Auth::class),
            Mockery::mock(Request::class),
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        Auth::resolveUserUsing(null);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testRequireUserIdThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionCode(401);

        $this->controller->callRequireUserId();
    }

    public function testRequireUserIdReturnsAuthenticatedSessionId(): void
    {
        $this->seedAuthenticatedUserSession(101, 'Api User');

        $this->assertSame(101, $this->controller->callRequireUserId());
    }

    public function testRequireAdminUserThrowsAuthExceptionWhenUserIsNotAdmin(): void
    {
        $this->seedAuthenticatedUserSession(102, 'Common User', 0);

        $this->expectException(AuthException::class);
        $this->expectExceptionCode(403);

        $this->controller->callRequireAdminUser();
    }

    public function testRequireAdminUserReturnsAdminUser(): void
    {
        $expected = $this->seedAuthenticatedUserSession(103, 'Admin User', 1);

        $user = $this->controller->callRequireAdminUser();

        $this->assertSame($expected, $user);
        $this->assertSame(1, $user->is_admin);
    }

    public function testOkReturnsStructuredSuccessPayload(): void
    {
        $response = $this->controller->callOk([
            'message' => 'Tudo certo',
            'redirect' => 'dashboard',
        ], 202);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Tudo certo',
            'data' => ['redirect' => 'dashboard'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testFailReturnsStructuredErrorPayload(): void
    {
        $response = $this->controller->callFail('Falhou', 422, ['email' => 'inválido']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Falhou',
            'errors' => ['email' => 'inválido'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGetJsonCachesPayloadAndReturnsDefaultForMissingKeys(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasJsonError')->twice()->andReturnFalse();
        $request->shouldReceive('json')->once()->andReturn(['foo' => 'bar']);

        $controller = $this->buildControllerWithRequest($request);

        $this->assertSame('bar', $controller->callGetJson('foo'));
        $this->assertSame('fallback', $controller->callGetJson('missing', 'fallback'));
    }

    public function testGetRequestPayloadFallsBackToPostWhenJsonIsEmpty(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasJsonError')->once()->andReturnFalse();
        $request->shouldReceive('json')->once()->andReturn([]);
        $request->shouldReceive('post')->once()->withNoArgs()->andReturn(['email' => 'teste@example.com']);

        $controller = $this->buildControllerWithRequest($request);

        $this->assertSame(['email' => 'teste@example.com'], $controller->callGetRequestPayload());
    }

    public function testGetJsonThrowsValidationExceptionWhenPayloadIsInvalid(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasJsonError')->once()->andReturnTrue();
        $request->shouldReceive('jsonError')->once()->andReturn('JSON inválido na requisição.');

        $controller = $this->buildControllerWithRequest($request);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $controller->callGetJson();
    }

    public function testDomainErrorResponseUsesThrowableMessageForSafeExceptions(): void
    {
        $response = $this->controller->callDomainErrorResponse(
            new \DomainException('Regra de negócio inválida'),
            'Mensagem fallback',
            422
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Regra de negócio inválida',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDomainErrorResponseUsesFallbackWhenMessageLooksSensitive(): void
    {
        $response = $this->controller->callDomainErrorResponse(
            new \DomainException('SQLSTATE[23000]: Integrity constraint violation'),
            'Mensagem fallback',
            400
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Mensagem fallback',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testNotFoundFromThrowableReturns404WithResourceCode(): void
    {
        $response = $this->controller->callNotFoundFromThrowable(
            new \RuntimeException('Falha interna'),
            'Não encontrado'
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Não encontrado',
            'code' => 'RESOURCE_NOT_FOUND',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testWorkflowFailureResponseReturnsClientErrorPayloadFor4xx(): void
    {
        $response = $this->controller->callWorkflowFailureResponse([
            'status' => 422,
            'message' => 'Validação falhou',
            'errors' => ['email' => 'obrigatório'],
            'code' => 'VALIDATION_ERROR',
        ], 'Erro interno');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validação falhou',
            'errors' => ['email' => 'obrigatório'],
            'code' => 'VALIDATION_ERROR',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testWorkflowFailureResponsePreservesErrorReferenceFor5xx(): void
    {
        $response = $this->controller->callWorkflowFailureResponse([
            'status' => 500,
            'message' => 'Falha ao processar',
            'errors' => [
                'error_id' => 'abc123',
                'request_id' => 'req123',
            ],
            'code' => 'INTERNAL_ERROR',
        ], 'Erro interno');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Erro interno',
            'error_id' => 'abc123',
            'request_id' => 'req123',
            'code' => 'INTERNAL_ERROR',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondApiWorkflowResultReturnsDefaultSuccessContract(): void
    {
        $response = $this->controller->callRespondApiWorkflowResult([
            'success' => true,
            'data' => ['id' => 10],
            'message' => 'Mensagem customizada',
            'status' => 202,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['id' => 10],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondApiWorkflowResultCanPreserveWorkflowMessageAndStatus(): void
    {
        $response = $this->controller->callRespondApiWorkflowResult([
            'success' => true,
            'data' => ['id' => 11],
            'message' => 'Criado com sucesso',
            'status' => 201,
        ], preserveSuccessMeta: true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Criado com sucesso',
            'data' => ['id' => 11],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondApiWorkflowResultCanUseDirectFailureResponse(): void
    {
        $response = $this->controller->callRespondApiWorkflowResult([
            'success' => false,
            'message' => 'Erro de domínio',
            'status' => 409,
            'errors' => ['conflict' => true],
        ], useWorkflowFailureOnFailure: false);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Erro de domínio',
            'errors' => ['conflict' => true],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondApiWorkflowResultCanMapValidationFailureTo422(): void
    {
        $response = $this->controller->callRespondApiWorkflowResult([
            'success' => false,
            'message' => 'Validation failed',
            'status' => 400,
            'errors' => ['email' => 'obrigatório'],
        ], useWorkflowFailureOnFailure: false, mapValidationFailedTo422: true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => ['email' => 'obrigatório'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondServiceResultMapsValidationErrorTo422(): void
    {
        $response = $this->controller->callRespondServiceResult(
            ServiceResultDTO::validationFail(['descricao' => 'Obrigatória'])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => ['descricao' => 'Obrigatória'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRespondServiceResultSupportsCustomSuccessPayloadMessageAndStatus(): void
    {
        $response = $this->controller->callRespondServiceResult(
            ServiceResultDTO::ok('Criado', ['id' => 77]),
            successData: ['resource_id' => 77],
            successMessage: 'Criado com sucesso',
            successStatus: 201
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Criado com sucesso',
            'data' => ['resource_id' => 77],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    // ... restante dos métodos auxiliares permanecem iguais
}
