<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Container\ApplicationContainer;
use Application\Controllers\BaseController;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;
use ValueError;

class BaseControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    private TestableBaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        ApplicationContainer::flush();
        Auth::resolveUserUsing(null);

        $this->controller = new TestableBaseController(
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

    public function testConstructorResolvesCoreDependenciesFromContainerWhenAvailable(): void
    {
        $auth = Mockery::mock(Auth::class);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);
        $cache = Mockery::mock(CacheService::class);

        $container = new IlluminateContainer();
        $container->instance(Auth::class, $auth);
        $container->instance(Request::class, $request);
        $container->instance(Response::class, $response);
        $container->instance(CacheService::class, $cache);
        ApplicationContainer::setInstance($container);

        $controller = new TestableBaseController();

        $this->assertSame($auth, $this->readProperty($controller, 'auth'));
        $this->assertSame($request, $this->readProperty($controller, 'request'));
        $this->assertSame($response, $this->readProperty($controller, 'response'));
        $this->assertSame($cache, $this->readProperty($controller, 'cache'));
    }

    public function testParseYearMonthReturnsExpectedPeriod(): void
    {
        $result = $this->controller->callParseYearMonth('2026-03');

        $this->assertSame([
            'month' => '2026-03',
            'year' => 2026,
            'monthNum' => 3,
            'start' => '2026-03-01',
            'end' => '2026-03-31',
        ], $result);
    }

    public function testParseYearMonthRejectsInvalidFormat(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Formato de mês inválido');

        $this->controller->callParseYearMonth('03/2026');
    }

    public function testNormalizeYearMonthFallsBackToProvidedMonth(): void
    {
        $result = $this->controller->callNormalizeYearMonth('invalido', '2026-04');

        $this->assertSame('2026-04', $result['month']);
        $this->assertSame('2026-04-01', $result['start']);
        $this->assertSame('2026-04-30', $result['end']);
    }

    public function testRequireUserIdUsesAuthenticatedSession(): void
    {
        $this->seedAuthenticatedUserSession(10, 'Helena');

        $this->assertSame(10, $this->controller->callRequireUserId());
    }

    public function testRequireUserIdThrowsRedirectResponseWhenSessionIsMissing(): void
    {
        try {
            $this->controller->callRequireUserId();
            self::fail('Expected redirect response exception was not thrown.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->getResponse()->getStatusCode());
            $this->assertSame(BASE_URL . 'login', $e->getResponse()->getHeaders()['Location'] ?? null);
        }
    }

    public function testRequireUserReturnsUserLoadedFromDatabase(): void
    {
        $expected = $this->seedAuthenticatedUserSession(20, 'Bruna');

        $user = $this->controller->callRequireUser();

        $this->assertSame($expected, $user);
    }

    public function testRequireAdminUserReturnsAdminLoadedFromDatabase(): void
    {
        $expected = $this->seedAuthenticatedUserSession(30, 'Admin Master', 1);

        $user = $this->controller->callRequireAdminUser();

        $this->assertSame($expected, $user);
        $this->assertSame(1, $user->is_admin);
    }

    public function testRequireAdminUserThrowsRedirectResponseWhenUserIsNotAdmin(): void
    {
        $this->seedAuthenticatedUserSession(31, 'Common User', 0);

        try {
            $this->controller->callRequireAdminUser();
            self::fail('Expected redirect response exception was not thrown.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->getResponse()->getStatusCode());
            $this->assertSame(BASE_URL . 'login', $e->getResponse()->getHeaders()['Location'] ?? null);
        }
    }

    public function testRequireApiUserIdOrFailReturnsCurrentAuthenticatedId(): void
    {
        $this->seedAuthenticatedUserSession(41, 'API User OrFail');

        $this->assertSame(41, $this->controller->callRequireApiUserIdOrFail());
    }

    public function testRequireApiUserAndReleaseSessionOrFailReturnsResolvedUser(): void
    {
        $expected = $this->seedAuthenticatedUserSession(50, 'API Helena');

        $user = $this->controller->callRequireApiUserAndReleaseSessionOrFail();

        $this->assertSame($expected, $user);
    }

    public function testRequireApiUserIdAndReleaseSessionOrFailReturnsAuthenticatedId(): void
    {
        $this->seedAuthenticatedUserSession(51, 'API Helena OrFail');

        $this->assertSame(51, $this->controller->callRequireApiUserIdAndReleaseSessionOrFail());
    }

    public function testRequireApiAdminUserAndReleaseSessionOrFailReturnsAdmin(): void
    {
        $expected = $this->seedAuthenticatedUserSession(60, 'API Admin', 1);

        $user = $this->controller->callRequireApiAdminUserAndReleaseSessionOrFail();

        $this->assertSame($expected, $user);
        $this->assertSame(60, $user->id);
    }

    public function testRequireApiAdminUserOrFailReturnsAdmin(): void
    {
        $expected = $this->seedAuthenticatedUserSession(61, 'API Admin OrFail', 1);

        $user = $this->controller->callRequireApiAdminUserOrFail();

        $this->assertSame($expected, $user);
        $this->assertSame(61, $user->id);
    }

    public function testRequireApiUserIdOrFailThrowsWhenSessionIsMissing(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionMessageMatches('/N[ãa]o autenticado/u');

        $this->controller->callRequireApiUserIdOrFail();
    }

    public function testRequireApiAdminUserOrFailThrowsWhenUserIsNotAdmin(): void
    {
        $this->seedAuthenticatedUserSession(62, 'API User', 0);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Acesso negado');

        $this->controller->callRequireApiAdminUserOrFail();
    }

    public function testOkReturnsStructuredSuccessResponse(): void
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

    public function testFailReturnsStructuredErrorResponse(): void
    {
        $response = $this->controller->callFail('Falhou', 422, ['email' => 'inválido']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Falhou',
            'errors' => ['email' => 'inválido'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRenderResponseReturnsHtmlResponse(): void
    {
        $response = $this->controller->callRenderResponse('admin/auth/verify-email', [
            'email' => 'teste@lukrato.com',
            'message' => 'Verifique seu email',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertStringContainsString('teste@lukrato.com', $response->getContent());
        $this->assertStringContainsString('Verifique seu email', $response->getContent());
    }

    public function testGetQueryUsesRequestQueryBagOnly(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('query')->once()->with('month', '2026-03')->andReturn('2026-04');
        $request->shouldNotReceive('get');

        $controller = new TestableBaseController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );

        $this->assertSame('2026-04', $controller->callGetQuery('month', '2026-03'));
    }

    public function testGetTypedQueryHelpersDelegateToBagSpecificRequestMethods(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('queryString')->once()->with('month', '2026-03')->andReturn('2026-04');
        $request->shouldReceive('queryInt')->once()->with('page', 1)->andReturn(4);
        $request->shouldReceive('queryBool')->once()->with('archived', false)->andReturnTrue();
        $request->shouldReceive('queryArray')->once()->with('tags', [])->andReturn(['a', 'b']);
        $request->shouldNotReceive('query');
        $request->shouldNotReceive('get');

        $controller = new TestableBaseController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );

        $this->assertSame('2026-04', $controller->callGetStringQuery('month', '2026-03'));
        $this->assertSame(4, $controller->callGetIntQuery('page', 1));
        $this->assertTrue($controller->callGetBoolQuery('archived'));
        $this->assertSame(['a', 'b'], $controller->callGetArrayQuery('tags'));
    }

    public function testGetJsonUsesRequestJsonCache(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasJsonError')->once()->andReturnFalse();
        $request->shouldReceive('json')->once()->andReturn(['name' => 'Lukrato']);
        $request->shouldNotReceive('post');

        $controller = new TestableBaseController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );

        $this->assertSame('Lukrato', $controller->callGetJson('name'));
    }

    public function testGetRequestPayloadFallsBackToParsedBody(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('json')->once()->andReturn([]);
        $request->shouldReceive('post')->once()->withNoArgs()->andReturn(['email' => 'ok@lukrato.com']);
        $request->shouldReceive('hasJsonError')->once()->andReturnFalse();

        $controller = new TestableBaseController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );

        $this->assertSame(['email' => 'ok@lukrato.com'], $controller->callGetRequestPayload());
    }

    public function testGetJsonThrowsValidationExceptionWhenRequestContainsInvalidJson(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasJsonError')->once()->andReturnTrue();
        $request->shouldReceive('jsonError')->once()->andReturn('JSON invalido na requisicao.');

        $controller = new TestableBaseController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $controller->callGetJson();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name, int $isAdmin = 0): Usuario
    {
        $this->startSession();

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin;

        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();

        Auth::resolveUserUsing(static fn(int $id): ?Usuario => $id === $userId ? $user : null);

        return $user;
    }

    private function startSession(): void
    {
        $this->startIsolatedSession('base-controller-test');
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}

final class TestableBaseController extends BaseController
{
    public function callOk(array $payload = [], int $status = 200): Response
    {
        return $this->ok($payload, $status);
    }

    public function callFail(string $message, int $status = 400, array $extra = []): Response
    {
        return $this->fail($message, $status, $extra);
    }

    public function callRenderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
    {
        return $this->renderResponse($viewPath, $data, $header, $footer);
    }

    public function callRequireUserId(): int
    {
        return $this->requireUserId();
    }

    public function callGetQuery(string $key, mixed $default = null): mixed
    {
        return $this->getQuery($key, $default);
    }

    public function callGetStringQuery(string $key, string $default = ''): string
    {
        return $this->getStringQuery($key, $default);
    }

    public function callGetIntQuery(string $key, int $default = 0): int
    {
        return $this->getIntQuery($key, $default);
    }

    public function callGetBoolQuery(string $key, bool $default = false): bool
    {
        return $this->getBoolQuery($key, $default);
    }

    public function callGetArrayQuery(string $key, array $default = []): array
    {
        return $this->getArrayQuery($key, $default);
    }

    public function callGetJson(?string $key = null, mixed $default = null): mixed
    {
        return $this->getJson($key, $default);
    }

    public function callGetRequestPayload(): array
    {
        return $this->getRequestPayload();
    }

    public function callRequireUser(): Usuario
    {
        return $this->requireUser();
    }

    public function callRequireAdminUser(): Usuario
    {
        return $this->requireAdminUser();
    }

    public function callRequireApiUserIdOrFail(): int
    {
        return $this->requireApiUserIdOrFail();
    }

    public function callRequireApiUserIdAndReleaseSessionOrFail(): int
    {
        return $this->requireApiUserIdAndReleaseSessionOrFail();
    }

    public function callRequireApiUserAndReleaseSessionOrFail(): Usuario
    {
        return $this->requireApiUserAndReleaseSessionOrFail();
    }

    public function callRequireApiAdminUserAndReleaseSessionOrFail(): Usuario
    {
        return $this->requireApiAdminUserAndReleaseSessionOrFail();
    }

    public function callRequireApiAdminUserOrFail(): Usuario
    {
        return $this->requireApiAdminUserOrFail();
    }

    public function callParseYearMonth(string $month): array
    {
        return $this->parseYearMonth($month);
    }

    public function callNormalizeYearMonth(string $month, ?string $fallbackMonth = null): array
    {
        return $this->normalizeYearMonth($month, $fallbackMonth);
    }
}
