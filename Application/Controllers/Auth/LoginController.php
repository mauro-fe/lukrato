<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\WebController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
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
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?CacheService $cache = null,
        ?AuthService $authService = null,
        ?TurnstileService $turnstile = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    ) {
        parent::__construct(cache: $cache);

        $this->authService = $this->resolveOrCreate($authService, AuthService::class);
        $this->turnstile = $this->resolveOrCreate($turnstile, TurnstileService::class);
        $this->runtimeConfig = $this->resolveOrCreate($runtimeConfig, AuthRuntimeConfig::class);
    }

    public function login(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse($this->runtimeConfig->dashboardUrl());
        }
        $intended = self::sanitizeIntended($this->getStringQuery('intended'));
        if ($this->runtimeConfig->hasConfiguredLoginUrl()) {
            $targetLoginUrl = $this->runtimeConfig->loginUrlForIntended($intended);

            if ($this->shouldRedirectToConfiguredLogin($targetLoginUrl)) {
                return $this->buildRedirectResponse($targetLoginUrl);
            }
        }

        $registerErrors = $this->pullRegisterErrors();
        $activeTab = $this->resolveActiveTab($registerErrors);

        $errorMessage = $this->getError();
        $ip = $this->request->ip() ?? 'unknown';

        if ($intended !== '') {
            $this->putSessionValue('login_intended', $intended);
        }

        return $this->renderResponse('admin/auth/login', [
            'error' => $errorMessage,
            'registerErrorMessage' => $errorMessage,
            'registerErrors' => $registerErrors,
            'activeTab' => $activeTab,
            'success' => $this->getSuccess(),
            'socialSuccess' => $this->isSocialSuccessRedirect(),
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
            'require_captcha' => $this->turnstile->shouldRequireCaptcha($ip),
            'turnstile_site_key' => TurnstileService::isEnabled() ? TURNSTILE_SITE_KEY : '',
            'intended' => $intended,
            'verifyEmailNoticeUrl' => $this->runtimeConfig->verifyEmailNoticePageUrl(),
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

    private function shouldRedirectToConfiguredLogin(string $targetLoginUrl): bool
    {
        $currentUrl = $this->resolveCurrentRequestUrl();

        if ($currentUrl !== '' && $this->urlsPointToSameLocation($targetLoginUrl, $currentUrl)) {
            return false;
        }

        return true;
    }

    private function resolveCurrentRequestUrl(): string
    {
        $requestUri = (string) $this->request->server('REQUEST_URI', '/');
        $requestUri = $requestUri !== '' ? $requestUri : '/';
        $host = trim((string) $this->request->server('HTTP_HOST', ''));

        if ($host === '') {
            return $requestUri;
        }

        return sprintf('%s://%s%s', $this->detectRequestScheme(), $host, $requestUri);
    }

    private function detectRequestScheme(): string
    {
        $https = strtolower((string) $this->request->server('HTTPS', ''));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }

        $forwardedProto = strtolower((string) $this->request->server('HTTP_X_FORWARDED_PROTO', ''));
        if ($forwardedProto !== '') {
            return explode(',', $forwardedProto, 2)[0] === 'https' ? 'https' : 'http';
        }

        $serverPort = (int) $this->request->server('SERVER_PORT', 80);

        return $serverPort === 443 ? 'https' : 'http';
    }

    private function urlsPointToSameLocation(string $urlA, string $urlB): bool
    {
        $partsA = $this->normalizeUrlParts($urlA);
        $partsB = $this->normalizeUrlParts($urlB);

        if ($partsA === null || $partsB === null) {
            return false;
        }

        if ($partsA['host'] !== '' && $partsB['host'] !== '' && $partsA['host'] !== $partsB['host']) {
            return false;
        }

        if ($partsA['port'] !== null && $partsB['port'] !== null && $partsA['port'] !== $partsB['port']) {
            return false;
        }

        return $partsA['path'] === $partsB['path']
            && $partsA['query'] === $partsB['query'];
    }

    /**
     * @return array{host: string, port: int|null, path: string, query: string}|null
     */
    private function normalizeUrlParts(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $path = '/' . trim((string) ($parts['path'] ?? '/'), '/');
        $path = $path === '//' ? '/' : $path;

        $query = (string) ($parts['query'] ?? '');
        if ($query !== '') {
            parse_str($query, $queryParams);
            ksort($queryParams);
            $query = http_build_query($queryParams);
        }

        return [
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'query' => $query,
        ];
    }

    public function processLogin(): Response
    {
        if (!$this->request->isPost()) {
            LogService::warning('Login request rejected: not POST');
            return $this->fail('Requisição inválida. Método esperado: POST.', 405);
        }

        $email = '';
        $ip = $this->request->ip() ?? 'unknown';

        try {
            $this->validateCsrfToken();
            $this->applyRateLimit($ip);
            $this->verifyCaptchaIfRequired($ip);

            $remember = $this->request->postBool('remember', false);
            $email = $this->request->postString('email', '');
            $password = $this->request->postString('password', '');

            $result = $this->authService->login($email, $password, $remember);
            $result['redirect'] = $this->resolvePostLoginRedirect((string) ($result['redirect'] ?? ''));

            $this->clearOldCsrfTokens();
            $this->turnstile->resetFailedAttempts($ip);

            return $this->ok([
                'message' => 'Login realizado com sucesso!',
                'redirect' => $result['redirect'],
            ]);
        } catch (ValidationException $e) {
            return $this->handleValidationFailure($e, $ip, $email);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'login',
                'email' => $email,
            ]);

            return $this->handleLoginError($e, $ip);
        }
    }

    public function logout(): Response
    {
        $result = $this->authService->logout();

        if ($this->request->wantsJson() || $this->request->isAjax()) {
            return $this->ok($result);
        }

        return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
    }

    private function validateCsrfToken(): void
    {
        CsrfMiddleware::handle($this->request, 'login_form');
    }

    private function applyRateLimit(string $ip): void
    {
        $this->cache->checkRateLimit('login:' . $ip, 5, 60);
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

    private function resolvePostLoginRedirect(string $defaultRedirect): string
    {
        $intended = self::sanitizeIntended((string) ($_SESSION['login_intended'] ?? ''));
        unset($_SESSION['login_intended']);

        if ($intended !== '') {
            return $this->runtimeConfig->intendedUrl($intended, $this->runtimeConfig->dashboardUrl());
        }

        if ($defaultRedirect === '' || $this->isDashboardRedirect($defaultRedirect)) {
            return $this->runtimeConfig->dashboardUrl();
        }

        return $defaultRedirect;
    }

    private function isDashboardRedirect(string $redirect): bool
    {
        $normalized = rtrim($redirect, '/');
        $backendDashboard = rtrim(BASE_URL, '/') . '/dashboard';

        return $normalized === 'dashboard'
            || $normalized === $backendDashboard
            || $normalized === rtrim($this->runtimeConfig->dashboardUrl(), '/');
    }

    private function handleValidationFailure(ValidationException $e, string $ip, string $email): Response
    {
        $captchaErrors = $e->getErrors()['captcha'] ?? null;
        if (!$captchaErrors) {
            $this->turnstile->recordFailedAttempt($ip);
        }

        LogService::persist(
            LogLevel::WARNING,
            LogCategory::AUTH,
            'Login: falha de validação',
            [
                'email' => $email,
                'errors' => $e->getErrors(),
                'captcha_required' => $this->turnstile->shouldRequireCaptcha($ip),
            ]
        );

        return $this->handleValidationException($e, $ip);
    }

    private function handleValidationException(ValidationException $e, string $ip): Response
    {
        $message = $e->getMessage();
        $captchaFlag = $this->turnstile->shouldRequireCaptcha($ip);
        $errors = $e->getErrors();

        if ($captchaFlag) {
            $errors['require_captcha'] = true;
        }

        if ($this->isRateLimitValidationFailure($e, $errors, $message)) {
            return $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $errors);
        }

        if (str_contains($message, 'Conta vinculada ao Google')) {
            return $this->fail($message, 401, $errors);
        }

        if (!empty($errors['email_not_verified'])) {
            $this->putSessionValue('unverified_email', $errors['user_email'] ?? '');

            return $this->fail(
                $errors['email'] ?? 'Você precisa verificar seu e-mail antes de fazer login.',
                403,
                $errors
            );
        }

        return $this->fail('E-mail ou senha inválidos.', 401, $errors, 'INVALID_CREDENTIALS');
    }

    private function handleLoginError(Throwable $e, string $ip): Response
    {
        $message = trim($e->getMessage());

        $this->turnstile->recordFailedAttempt($ip);

        if ($this->isCredentialErrorMessage($message)) {
            $extra = $this->turnstile->shouldRequireCaptcha($ip)
                ? ['require_captcha' => true]
                : [];

            return $this->fail('E-mail ou senha inválidos.', 401, $extra, 'INVALID_CREDENTIALS');
        }

        return $this->failAndLog($e, 'Erro ao processar login.');
    }

    private function pullRegisterErrors(): ?array
    {
        $errors = $this->pullSessionValue('register_errors');

        return is_array($errors) ? $errors : null;
    }

    private function resolveActiveTab(?array $registerErrors): string
    {
        $activeTab = $this->pullSessionValue('auth_active_tab', 'login');
        $activeTab = is_string($activeTab) ? $activeTab : 'login';

        $queryTab = $this->getStringQuery('tab');
        if ($this->isAllowedTab($queryTab)) {
            $activeTab = $queryTab;
        }

        if (!empty($registerErrors)) {
            $activeTab = 'register';
        }

        if (!$this->isAllowedTab($activeTab)) {
            return 'login';
        }

        return $activeTab;
    }

    private function isAllowedTab(string $tab): bool
    {
        return in_array($tab, ['login', 'register'], true);
    }

    private function isSocialSuccessRedirect(): bool
    {
        return $this->getIntQuery('new_google', 0) === 1;
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function isRateLimitValidationFailure(ValidationException $e, array $errors, string $message): bool
    {
        return $e->getCode() === 429
            || isset($errors['rate_limit'])
            || stripos($message, 'rate limit') !== false
            || str_contains($message, 'Muitas tentativas');
    }

    private function isCredentialErrorMessage(string $message): bool
    {
        return str_contains($message, 'credenciais inválidas')
            || str_contains($message, 'inválidos');
    }
}
