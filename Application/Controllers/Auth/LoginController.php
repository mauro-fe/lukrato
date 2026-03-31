<?php

namespace Application\Controllers\Auth;

use Application\Controllers\WebController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Auth\AuthService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\TurnstileService;
use Throwable;

class LoginController extends WebController
{
    private AuthService $authService;
    private TurnstileService $turnstile;

    public function __construct(?CacheService $cache = null)
    {
        parent::__construct();

        $this->cache = $cache ?? new CacheService();
        $this->authService = new AuthService($this->request, $this->cache);
        $this->turnstile = new TurnstileService($this->cache);
    }

    public function login(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse('dashboard');
        }

        $activeTab = $_SESSION['auth_active_tab'] ?? 'login';
        unset($_SESSION['auth_active_tab']);

        if (isset($_GET['tab']) && in_array($_GET['tab'], ['login', 'register'], true)) {
            $activeTab = $_GET['tab'];
        }

        $registerErrors = $_SESSION['register_errors'] ?? null;
        unset($_SESSION['register_errors']);

        if (!empty($registerErrors)) {
            $activeTab = 'register';
        }

        $errorMessage = $this->getError();
        $socialSuccess = isset($_GET['new_google']) && $_GET['new_google'] == 1;

        $ip = $this->request->ip() ?? 'unknown';
        $requireCaptcha = $this->turnstile->shouldRequireCaptcha($ip);
        $intended = self::sanitizeIntended($_GET['intended'] ?? '');

        if ($intended !== '') {
            $_SESSION['login_intended'] = $intended;
        }

        return $this->renderResponse('admin/auth/login', [
            'error' => $errorMessage,
            'registerErrorMessage' => $errorMessage,
            'registerErrors' => $registerErrors,
            'activeTab' => $activeTab,
            'success' => $this->getSuccess(),
            'socialSuccess' => $socialSuccess,
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
            'require_captcha' => $requireCaptcha,
            'turnstile_site_key' => TurnstileService::isEnabled() ? TURNSTILE_SITE_KEY : '',
            'intended' => $intended,
        ]);
    }

    private static function sanitizeIntended(string $raw): string
    {
        $path = trim($raw, '/ ');

        if ($path === '' || $path === 'login') {
            return '';
        }

        if (!preg_match('#^[a-zA-Z0-9/_\-]+$#', $path)) {
            return '';
        }

        return $path;
    }

    public function processLogin(): Response
    {
        LogService::info('[LOGIN DEBUG] Início processLogin');

        if (!$this->request->isPost()) {
            LogService::warning('Login request rejected: not POST');
            return $this->fail('Requisição inválida. Método esperado: POST.', 405);
        }

        $email = '';

        try {
            LogService::info('[LOGIN DEBUG] Validando CSRF');
            $ip = $this->request->ip() ?? 'unknown';
            $this->validateCsrfToken();

            LogService::info('[LOGIN DEBUG] Aplicando rate limit');
            $this->applyRateLimit();

            $this->verifyCaptchaIfRequired($ip);

            $remember = $this->request->post('remember', '0') === '1';
            $email = $this->request->post('email', '');

            LogService::info('[LOGIN DEBUG] Tentando autenticar', ['email' => $email]);

            $result = $this->authService->login(
                $email,
                $this->request->post('password', ''),
                $remember
            );

            LogService::info('[LOGIN DEBUG] Login OK, limpando tokens');
            $this->clearOldCsrfTokens();
            $this->turnstile->resetFailedAttempts($ip);

            LogService::info('[LOGIN DEBUG] Retornando sucesso');
            return $this->ok([
                'message' => 'Login realizado com sucesso!',
                'redirect' => $result['redirect'],
            ]);
        } catch (ValidationException $e) {
            $captchaErrors = $e->getErrors()['captcha'] ?? null;
            if (!$captchaErrors) {
                $this->turnstile->recordFailedAttempt($ip);
            }

            LogService::persist(
                \Application\Enums\LogLevel::WARNING,
                LogCategory::AUTH,
                'Login: falha de validação',
                [
                    'email' => $email,
                    'errors' => $e->getErrors(),
                    'captcha_required' => $this->turnstile->shouldRequireCaptcha($ip),
                ]
            );

            return $this->handleValidationException($e);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'login',
                'email' => $email,
            ]);

            return $this->handleLoginError($e);
        }
    }

    public function logout(): Response
    {
        $result = $this->authService->logout();

        if ($this->request->wantsJson() || $this->request->isAjax()) {
            return $this->ok($result);
        }

        return $this->buildRedirectResponse('login');
    }

    private function validateCsrfToken(): void
    {
        CsrfMiddleware::handle($this->request, 'login_form');
    }

    private function applyRateLimit(): void
    {
        $this->cache->checkRateLimit(
            'login:' . ($this->request->ip() ?? 'unknown'),
            5,
            60
        );
    }

    private function verifyCaptchaIfRequired(string $ip): void
    {
        if (!$this->turnstile->shouldRequireCaptcha($ip)) {
            return;
        }

        $token = $this->request->post('cf-turnstile-response', '');
        $this->turnstile->verify($token, $ip);
    }

    private function clearOldCsrfTokens(): void
    {
        unset($_SESSION['csrf_tokens']);
    }

    private function handleValidationException(ValidationException $e): Response
    {
        $message = $e->getMessage();
        $ip = $this->request->ip() ?? 'unknown';
        $captchaFlag = $this->turnstile->shouldRequireCaptcha($ip);
        $errors = $e->getErrors();

        if ($captchaFlag) {
            $errors['require_captcha'] = true;
        }

        if ($e->getCode() === 429 || isset($errors['rate_limit']) || stripos($message, 'rate limit') !== false || str_contains($message, 'Muitas tentativas')) {
            return $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $errors);
        }

        if (str_contains($message, 'Conta vinculada ao Google')) {
            return $this->fail($message, 401, $errors);
        }

        if (!empty($errors['email_not_verified'])) {
            $_SESSION['unverified_email'] = $errors['user_email'] ?? '';

            return $this->fail(
                $errors['email'] ?? 'Você precisa verificar seu e-mail antes de fazer login.',
                403,
                $errors
            );
        }

        return $this->fail('E-mail ou senha inválidos.', 401, $errors, 'INVALID_CREDENTIALS');
    }

    private function handleLoginError(Throwable $e): Response
    {
        $message = trim($e->getMessage());
        $ip = $this->request->ip() ?? 'unknown';

        $this->turnstile->recordFailedAttempt($ip);

        if (str_contains($message, 'credenciais inválidas') || str_contains($message, 'inválidos')) {
            $extra = $this->turnstile->shouldRequireCaptcha($ip)
                ? ['require_captcha' => true]
                : [];

            return $this->fail('E-mail ou senha inválidos.', 401, $extra, 'INVALID_CREDENTIALS');
        }

        return $this->failAndLog($e, 'Erro ao processar login.');
    }
}
