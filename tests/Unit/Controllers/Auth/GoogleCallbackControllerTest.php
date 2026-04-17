<?php

declare(strict_types=1);

namespace {
    if (!function_exists('vite_scripts')) {
        function vite_scripts(string ...$entries): string
        {
            return '';
        }
    }
}

namespace Tests\Unit\Controllers\Auth {

    use Application\Config\AuthRuntimeConfig;
    use Application\Controllers\Auth\GoogleCallbackController;
    use Application\Models\Usuario;
    use Application\Services\Auth\GoogleAuthService;
    use Mockery;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use PHPUnit\Framework\TestCase;
    use Tests\Support\SessionIsolation;

    class GoogleCallbackControllerTest extends TestCase
    {
        use MockeryPHPUnitIntegration;
        use SessionIsolation;

        /** @var array<int, string> */
        private array $authFrontendEnvKeys = [
            'FRONTEND_APP_URL',
            'FRONTEND_GOOGLE_CONFIRM_URL',
            'FRONTEND_LOGIN_URL',
            'FRONTEND_WELCOME_URL',
            'FRONTEND_DASHBOARD_URL',
        ];

        protected function setUp(): void
        {
            parent::setUp();
            $this->resetSessionState();
            $this->clearAuthFrontendEnv();
            $_GET = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        protected function tearDown(): void
        {
            unset($_GET, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_ACCEPT']);
            $this->clearAuthFrontendEnv();
            $this->resetSessionState();
            parent::tearDown();
        }

        public function testCallbackRedirectsToConfiguredConfirmPageWhenConfirmationIsRequired(): void
        {
            $this->startIsolatedSession('google-callback-confirm-page');
            $_GET['code'] = 'oauth-code';

            $service = Mockery::mock(GoogleAuthService::class);
            $service
                ->shouldReceive('handleCallback')
                ->once()
                ->with('oauth-code')
                ->andReturn([
                    'needs_confirmation' => true,
                    'user_info' => [
                        'name' => 'Maria Google',
                        'email' => 'maria@lukrato.com',
                        'picture' => 'https://example.com/avatar.png',
                    ],
                ]);

            $this->setEnvValue('FRONTEND_GOOGLE_CONFIRM_URL', 'https://app.example.com/google/confirm');
            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController($service, $runtimeConfig);
            $response = $controller->callback();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/google/confirm', $response->getHeaders()['Location'] ?? null);
            $this->assertSame('Maria Google', $_SESSION['google_pending_user']['name'] ?? null);
        }

        public function testConfirmPageReturnsRedirectWhenSessionHasNoPendingUser(): void
        {
            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class), $runtimeConfig);

            $response = $controller->confirmPage();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
        }

        public function testConfirmPageReturnsHtmlResponseWhenPendingUserExists(): void
        {
            $this->startIsolatedSession('google-callback-controller-test');
            $_SESSION['google_pending_user'] = [
                'name' => 'Maria Google',
                'email' => 'maria@lukrato.com',
                'picture' => 'https://example.com/avatar.png',
            ];

            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class), $runtimeConfig);
            $response = $controller->confirmPage();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('Criar sua conta', $content);
            $this->assertStringContainsString('data-google-confirm-root', $content);
            $this->assertStringContainsString(BASE_URL . 'api/v1/auth/google/pending', $content);
            $this->assertStringContainsString(BASE_URL . 'api/v1/auth/google/confirm', $content);
            $this->assertStringContainsString(BASE_URL . 'api/v1/auth/google/cancel', $content);
            $this->assertStringNotContainsString('Maria Google', $content);
            $this->assertStringNotContainsString('maria@lukrato.com', $content);
        }

        public function testConfirmPageRedirectsToConfiguredFrontendUrlWhenAvailable(): void
        {
            $this->startIsolatedSession('google-confirm-page-redirect-test');
            $_SESSION['google_pending_user'] = [
                'name' => 'Maria Google',
                'email' => 'maria@lukrato.com',
            ];
            $this->setEnvValue('FRONTEND_GOOGLE_CONFIRM_URL', 'https://app.example.com/google/confirm');

            $runtimeConfig = new AuthRuntimeConfig();
            $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class), $runtimeConfig);
            $response = $controller->confirmPage();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/google/confirm', $response->getHeaders()['Location'] ?? null);
        }

        public function testCallbackRedirectsExistingUserToConfiguredFrontendIntendedPath(): void
        {
            $this->startIsolatedSession('google-callback-intended-test');
            $_GET['code'] = 'oauth-code';
            $_SESSION['login_intended'] = 'relatorios';

            $usuario = new Usuario();
            $usuario->id = 77;
            $usuario->google_id = 'google-77';
            $usuario->email = 'maria@lukrato.com';

            $service = Mockery::mock(GoogleAuthService::class);
            $service
                ->shouldReceive('handleCallback')
                ->once()
                ->with('oauth-code')
                ->andReturn([
                    'needs_confirmation' => false,
                    'usuario' => $usuario,
                    'is_new' => false,
                ]);
            $service
                ->shouldReceive('loginUser')
                ->once()
                ->with($usuario, [
                    'id' => 'google-77',
                    'email' => 'maria@lukrato.com',
                    'picture' => null,
                ]);

            $this->setEnvValue('FRONTEND_APP_URL', 'https://app.example.com');
            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController($service, $runtimeConfig);
            $response = $controller->callback();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/relatorios', $response->getHeaders()['Location'] ?? null);
            $this->assertArrayNotHasKey('login_intended', $_SESSION);
        }

        public function testPendingReturnsVersionedActionUrls(): void
        {
            $this->startIsolatedSession('google-callback-pending-test');
            $_SESSION['google_pending_user'] = [
                'name' => 'Maria Google',
                'email' => 'maria@lukrato.com',
                'picture' => 'https://example.com/avatar.png',
            ];

            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class), $runtimeConfig);
            $response = $controller->pending();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($payload['success']);
            $this->assertSame('Maria Google', $payload['data']['pending_user']['name'] ?? null);
            $this->assertSame(BASE_URL . 'api/v1/auth/google/confirm', $payload['data']['actions']['confirm_url'] ?? null);
            $this->assertSame(BASE_URL . 'api/v1/auth/google/cancel', $payload['data']['actions']['cancel_url'] ?? null);
        }

        public function testConfirmReturnsJsonPayloadForAjaxRequests(): void
        {
            $this->startIsolatedSession('google-callback-confirm-test');
            $_SESSION['google_pending_user'] = [
                'name' => 'Maria Google',
                'email' => 'maria@lukrato.com',
                'picture' => 'https://example.com/avatar.png',
            ];
            $_SESSION['pending_referral_code'] = 'ABCD1234';
            $_SERVER['HTTP_ACCEPT'] = 'application/json';

            $usuario = new Usuario();
            $usuario->id = 99;
            $usuario->google_id = 'google-99';
            $usuario->email = 'maria@lukrato.com';

            $service = Mockery::mock(GoogleAuthService::class);
            $service
                ->shouldReceive('createUserFromPending')
                ->once()
                ->with($_SESSION['google_pending_user'], 'ABCD1234')
                ->andReturn($usuario);
            $service
                ->shouldReceive('loginUser')
                ->once()
                ->with($usuario, [
                    'id' => 'google-99',
                    'email' => 'maria@lukrato.com',
                    'picture' => 'https://example.com/avatar.png',
                ]);

            $this->setEnvValue('FRONTEND_WELCOME_URL', 'https://app.example.com/welcome');
            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController($service, $runtimeConfig);
            $response = $controller->confirm();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($payload['success']);
            $this->assertSame('https://app.example.com/welcome', $payload['data']['redirect'] ?? null);
        }

        public function testCancelReturnsJsonPayloadForAjaxRequests(): void
        {
            $this->startIsolatedSession('google-callback-cancel-test');
            $_SESSION['google_pending_user'] = [
                'name' => 'Maria Google',
                'email' => 'maria@lukrato.com',
            ];
            $_SERVER['HTTP_ACCEPT'] = 'application/json';

            $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');
            $runtimeConfig = new AuthRuntimeConfig();

            $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class), $runtimeConfig);
            $response = $controller->cancel();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($payload['success']);
            $this->assertSame('https://app.example.com/login', $payload['data']['redirect'] ?? null);
            $this->assertArrayNotHasKey('google_pending_user', $_SESSION);
        }

        private function setEnvValue(string $key, string $value): void
        {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        private function clearAuthFrontendEnv(): void
        {
            foreach ($this->authFrontendEnvKeys as $key) {
                unset($_ENV[$key]);
                putenv($key);
            }
        }
    }
}
