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
    use Application\Controllers\Auth\LoginController;
    use Application\Lib\Auth;
    use Application\Middlewares\CsrfMiddleware;
    use Application\Services\Auth\AuthService;
    use Application\Services\Infrastructure\CacheService;
    use Application\Services\Infrastructure\TurnstileService;
    use Mockery;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use PHPUnit\Framework\TestCase;
    use Tests\Support\SessionIsolation;

    class LoginControllerTest extends TestCase
    {
        use MockeryPHPUnitIntegration;
        use SessionIsolation;

        /** @var array<int, string> */
        private array $authFrontendEnvKeys = [
            'FRONTEND_APP_URL',
            'FRONTEND_DASHBOARD_URL',
            'FRONTEND_LOGIN_URL',
            'FRONTEND_VERIFY_EMAIL_NOTICE_URL',
        ];

        protected function setUp(): void
        {
            parent::setUp();
            $this->resetSessionState();
            $this->startIsolatedSession('login-controller-test');
            Auth::resolveUserUsing(null);
            $this->clearAuthFrontendEnv();

            $_GET = [];
            $_POST = [];
            $_SESSION = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['SCRIPT_NAME'] = '/index.php';
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }

        protected function tearDown(): void
        {
            Auth::resolveUserUsing(null);
            $this->clearAuthFrontendEnv();
            unset(
                $_GET,
                $_POST,
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['SCRIPT_NAME'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_X_REQUESTED_WITH'],
                $_SERVER['HTTP_ACCEPT']
            );

            $this->resetSessionState();
            parent::tearDown();
        }

        public function testLoginRedirectsToDashboardWhenAlreadyAuthenticated(): void
        {
            $_SESSION['user_id'] = 77;
            $_SESSION['last_activity'] = time();

            $controller = new LoginController();
            $response = $controller->login();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame(BASE_URL . 'dashboard', $response->getHeaders()['Location'] ?? null);
        }

        public function testLoginRedirectsGuestToConfiguredFrontendLoginPage(): void
        {
            $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');
            $_GET['intended'] = 'relatorios';

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                Mockery::mock(TurnstileService::class),
                new AuthRuntimeConfig()
            );

            $response = $controller->login();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/login?intended=relatorios', $response->getHeaders()['Location'] ?? null);
        }

        public function testLoginRendersLocalPageWhenConfiguredFrontendLoginMatchesCurrentRequest(): void
        {
            if (!defined('ASSETS_URL')) {
                define('ASSETS_URL', BASE_URL . 'assets/');
            }

            $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://lukrato.com.br/login');
            $_SERVER['HTTP_HOST'] = 'lukrato.com.br';
            $_SERVER['REQUEST_URI'] = '/login';

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                $turnstile,
                new AuthRuntimeConfig()
            );

            $response = $controller->login();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="loginForm"', $response->getContent());
        }

        public function testLoginRendersLocalPageWhenConfiguredFrontendLoginOnlyDiffersByWwwHost(): void
        {
            if (!defined('ASSETS_URL')) {
                define('ASSETS_URL', BASE_URL . 'assets/');
            }

            $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://www.lukrato.com.br/login');
            $_SERVER['HTTP_HOST'] = 'lukrato.com.br';
            $_SERVER['REQUEST_URI'] = '/login';

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                $turnstile,
                new AuthRuntimeConfig()
            );

            $response = $controller->login();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="loginForm"', $response->getContent());
        }

        public function testLoginRedirectsToConfiguredDashboardWhenAlreadyAuthenticated(): void
        {
            $_SESSION['user_id'] = 77;
            $_SESSION['last_activity'] = time();
            $this->setEnvValue('FRONTEND_DASHBOARD_URL', 'https://app.example.com/dashboard');

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                Mockery::mock(TurnstileService::class),
                new AuthRuntimeConfig()
            );

            $response = $controller->login();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/dashboard', $response->getHeaders()['Location'] ?? null);
        }

        public function testLoginViewUsesVersionedGoogleOauthEntryPoints(): void
        {
            if (!defined('ASSETS_URL')) {
                define('ASSETS_URL', BASE_URL . 'assets/');
            }

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                $turnstile
            );

            $response = $controller->login();
            $content = $response->getContent();

            $this->assertStringContainsString(BASE_URL . 'api/v1/auth/google/login', $content);
            $this->assertStringContainsString(BASE_URL . 'api/v1/auth/google/register', $content);
            $this->assertStringNotContainsString(BASE_URL . 'auth/google/login', $content);
            $this->assertStringNotContainsString(BASE_URL . 'auth/google/register', $content);
        }

        public function testLoginViewExposesConfiguredVerifyEmailNoticeUrl(): void
        {
            if (!defined('ASSETS_URL')) {
                define('ASSETS_URL', BASE_URL . 'assets/');
            }

            $this->setEnvValue('FRONTEND_VERIFY_EMAIL_NOTICE_URL', 'https://app.example.com/verify-email');

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                Mockery::mock(AuthService::class),
                $turnstile,
                new AuthRuntimeConfig()
            );

            $response = $controller->login();
            $content = $response->getContent();

            $this->assertStringContainsString('name="verify-email-notice-url"', $content);
            $this->assertStringContainsString('https://app.example.com/verify-email', $content);
        }

        public function testProcessLoginRejectsNonPostRequests(): void
        {
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $authService = Mockery::mock(AuthService::class);
            $authService->shouldNotReceive('login');

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                $authService,
                Mockery::mock(TurnstileService::class)
            );

            $response = $controller->processLogin();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(405, $response->getStatusCode());
            $this->assertFalse($payload['success']);
            $this->assertSame('Requisição inválida. Método esperado: POST.', $payload['message']);
        }

        public function testProcessLoginReturnsSuccessPayloadAndResetsCaptchaCounter(): void
        {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $_POST['csrf_token'] = CsrfMiddleware::generateToken('login_form');
            $_POST['email'] = 'user@example.com';
            $_POST['password'] = 'Senha@123';
            $_POST['remember'] = '1';

            $cache = Mockery::mock(CacheService::class);
            $cache
                ->shouldReceive('checkRateLimit')
                ->once()
                ->with('login:127.0.0.1', 5, 60);

            $authService = Mockery::mock(AuthService::class);
            $authService
                ->shouldReceive('login')
                ->once()
                ->with('user@example.com', 'Senha@123', true)
                ->andReturn(['redirect' => 'dashboard']);

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();
            $turnstile
                ->shouldReceive('resetFailedAttempts')
                ->once()
                ->with('127.0.0.1');

            $controller = new LoginController($cache, $authService, $turnstile);

            $response = $controller->processLogin();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($payload['success']);
            $this->assertSame('Login realizado com sucesso!', $payload['message']);
            $this->assertSame(['redirect' => BASE_URL . 'dashboard'], $payload['data']);
        }

        public function testProcessLoginUsesConfiguredFrontendRedirectForIntendedPath(): void
        {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $_POST['csrf_token'] = CsrfMiddleware::generateToken('login_form');
            $_POST['email'] = 'user@example.com';
            $_POST['password'] = 'Senha@123';
            $_SESSION['login_intended'] = 'relatorios';
            $this->setEnvValue('FRONTEND_APP_URL', 'https://app.example.com');

            $cache = Mockery::mock(CacheService::class);
            $cache
                ->shouldReceive('checkRateLimit')
                ->once()
                ->with('login:127.0.0.1', 5, 60);

            $authService = Mockery::mock(AuthService::class);
            $authService
                ->shouldReceive('login')
                ->once()
                ->with('user@example.com', 'Senha@123', false)
                ->andReturn(['redirect' => BASE_URL . 'dashboard']);

            $turnstile = Mockery::mock(TurnstileService::class);
            $turnstile
                ->shouldReceive('shouldRequireCaptcha')
                ->once()
                ->with('127.0.0.1')
                ->andReturnFalse();
            $turnstile
                ->shouldReceive('resetFailedAttempts')
                ->once()
                ->with('127.0.0.1');

            $controller = new LoginController($cache, $authService, $turnstile, new AuthRuntimeConfig());

            $response = $controller->processLogin();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('https://app.example.com/relatorios', $payload['data']['redirect'] ?? null);
            $this->assertArrayNotHasKey('login_intended', $_SESSION);
        }

        public function testLogoutReturnsJsonResponseForAjaxRequests(): void
        {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

            $authService = Mockery::mock(AuthService::class);
            $authService
                ->shouldReceive('logout')
                ->once()
                ->andReturn([
                    'message' => 'Sessão encerrada.',
                    'redirect' => 'login',
                ]);

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                $authService,
                Mockery::mock(TurnstileService::class)
            );

            $response = $controller->logout();
            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($payload['success']);
            $this->assertSame('Sessão encerrada.', $payload['message']);
            $this->assertSame(['redirect' => 'login'], $payload['data']);
        }

        public function testLogoutRedirectsToLoginWhenRequestIsNotAjax(): void
        {
            $authService = Mockery::mock(AuthService::class);
            $authService->shouldReceive('logout')->once()->andReturn([]);

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                $authService,
                Mockery::mock(TurnstileService::class)
            );

            $response = $controller->logout();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
        }

        public function testLogoutRedirectsToConfiguredFrontendLoginWhenRequestIsNotAjax(): void
        {
            $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

            $authService = Mockery::mock(AuthService::class);
            $authService->shouldReceive('logout')->once()->andReturn([]);

            $controller = new LoginController(
                Mockery::mock(CacheService::class),
                $authService,
                Mockery::mock(TurnstileService::class),
                new AuthRuntimeConfig()
            );

            $response = $controller->logout();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/login', $response->getHeaders()['Location'] ?? null);
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
