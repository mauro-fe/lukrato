<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Controllers\WebController;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Infrastructure\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class WebControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    private TestableWebController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        Auth::resolveUserUsing(null);

        $this->controller = new TestableWebController(
            Mockery::mock(Auth::class),
            Mockery::mock(Request::class),
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );
    }

    protected function tearDown(): void
    {
        Auth::resolveUserUsing(null);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testRenderResponseRendersHtmlUsingViewLayer(): void
    {
        $response = $this->controller->callRenderResponse('admin/auth/verify-email', [
            'email' => 'web@lukrato.com',
            'message' => 'Verifique seu email',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('web@lukrato.com', $response->getContent());
        $this->assertStringContainsString('Verifique seu email', $response->getContent());
    }

    public function testBuildRedirectResponseBuildsExpectedLocation(): void
    {
        $response = $this->controller->callBuildRedirectResponse('dashboard');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'dashboard', $response->getHeaders()['Location'] ?? null);
    }

    public function testThrowRedirectResponseRaisesHttpResponseException(): void
    {
        $this->expectException(HttpResponseException::class);
        $this->expectExceptionCode(302);

        $this->controller->callThrowRedirectResponse('login');
    }

    public function testFlashErrorLifecycleStoresAndClearsMessage(): void
    {
        $this->startIsolatedSession('web-controller-test');

        $this->controller->callSetError('Falha de teste');

        $this->assertSame('Falha de teste', $this->controller->callGetError());
        $this->assertNull($this->controller->callGetError());
    }

    public function testInferMenuFromViewResolvesExpectedAdminMenu(): void
    {
        $this->assertSame('faturas', $this->controller->callInferMenuFromView('admin/parcelamentos/index'));
        $this->assertNull($this->controller->callInferMenuFromView('site/home'));
    }
}

final class TestableWebController extends WebController
{
    public function callRenderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
    {
        return $this->renderResponse($viewPath, $data, $header, $footer);
    }

    public function callBuildRedirectResponse(string $path, int $statusCode = 302): Response
    {
        return $this->buildRedirectResponse($path, $statusCode);
    }

    public function callThrowRedirectResponse(string $path, int $statusCode = 302): never
    {
        $this->throwRedirectResponse($path, $statusCode);
    }

    public function callSetError(string $message): void
    {
        $this->setError($message);
    }

    public function callGetError(): ?string
    {
        return $this->getError();
    }

    public function callInferMenuFromView(string $viewPath): ?string
    {
        return $this->inferMenuFromView($viewPath);
    }
}
