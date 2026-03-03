<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\TurnstileService;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;
use Throwable;

class LoginController extends BaseController
{
    private AuthService $authService;
    private TurnstileService $turnstile;

    public function __construct(?CacheService $cache = null)
    {
        parent::__construct();

        // Se não passar nada, cria o CacheService padrão.
        // Se passar (num teste, por exemplo), usa o que veio.
        $this->cache = $cache ?? new CacheService();

        $this->authService = new AuthService($this->request, $this->cache);
        $this->turnstile = new TurnstileService($this->cache);
    }


    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }

        $activeTab = $_SESSION['auth_active_tab'] ?? 'login';
        unset($_SESSION['auth_active_tab']);

        // Permite definir a aba via query string
        if (isset($_GET['tab']) && in_array($_GET['tab'], ['login', 'register'])) {
            $activeTab = $_GET['tab'];
        }

        $registerErrors = $_SESSION['register_errors'] ?? null;
        unset($_SESSION['register_errors']);

        if (!empty($registerErrors)) {
            $activeTab = 'register';
        }

        $errorMessage = $this->getError();
        $socialSuccess = isset($_GET['new_google']) && $_GET['new_google'] == 1;

        // Verificar se CAPTCHA deve ser exibido para este IP
        $ip = $this->request->ip() ?? 'unknown';
        $requireCaptcha = $this->turnstile->shouldRequireCaptcha($ip);

        $this->render('admin/auth/login', [
            'error' => $errorMessage,
            'registerErrorMessage' => $errorMessage,
            'registerErrors' => $registerErrors,
            'activeTab' => $activeTab,
            'success' => $this->getSuccess(),
            'socialSuccess' => $socialSuccess,
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
            'require_captcha' => $requireCaptcha,
            'turnstile_site_key' => TurnstileService::isEnabled() ? TURNSTILE_SITE_KEY : '',
        ]);
    }



    /**
     * Processa a tentativa de login.
     */
    public function processLogin(): void
    {
        LogService::info('[LOGIN DEBUG] Início processLogin');

        if (!$this->request->isPost()) {
            LogService::warning('Login request rejected: not POST');
            $this->fail('Requisição inválida. Método esperado: POST.', 405);
            return;
        }

        $email = '';

        try {
            LogService::info('[LOGIN DEBUG] Validando CSRF');
            // Segurança
            $ip = $this->request->ip() ?? 'unknown';
            $this->validateCsrfToken();

            LogService::info('[LOGIN DEBUG] Aplicando rate limit');
            $this->applyRateLimit();

            // Turnstile CAPTCHA (progressivo — só valida se o IP já atingiu o threshold)
            $this->verifyCaptchaIfRequired($ip);

            // Autenticação
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

            // Login OK: zera contador de falhas do Turnstile
            $this->turnstile->resetFailedAttempts($ip);

            LogService::info('[LOGIN DEBUG] Retornando sucesso');
            $this->ok([
                'message'  => 'Login realizado com sucesso!',
                'redirect' => $result['redirect']
            ]);
        } catch (ValidationException $e) {
            // Incrementa falhas do Turnstile (exceto se o próprio captcha falhou)
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
            $this->handleValidationException($e);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'login',
                'email' => $email,
            ]);
            $this->handleLoginError($e);
        }
    }

    /**
     * Processa o logout.
     */
    public function logout(): void
    {
        $result = $this->authService->logout();

        if ($this->request->wantsJson() || $this->request->isAjax()) {
            $this->ok($result);
            return;
        }

        $this->redirect('login');
    }

    // --- Métodos Auxiliares Privados ---

    /**
     * Valida token CSRF.
     * @throws ValidationException
     */
    private function validateCsrfToken(): void
    {
        CsrfMiddleware::handle($this->request, 'login_form');
    }

    /**
     * Aplica rate limiting.
     * @throws ValidationException
     */
    private function applyRateLimit(): void
    {
        if ($this->cache === null) {
            LogService::warning('CacheService not available for Rate Limiting');
            return;
        }

        $this->cache->checkRateLimit(
            'login:' . ($this->request->ip() ?? 'unknown')
        );
    }

    /**
     * Verifica CAPTCHA Turnstile se o IP atingiu o threshold de falhas.
     * @throws ValidationException
     */
    private function verifyCaptchaIfRequired(string $ip): void
    {
        if (!$this->turnstile->shouldRequireCaptcha($ip)) {
            return;
        }

        $token = $this->request->post('cf-turnstile-response', '');
        $this->turnstile->verify($token, $ip);
    }

    /**
     * Limpa tokens CSRF antigos.
     */
    private function clearOldCsrfTokens(): void
    {
        unset($_SESSION['csrf_tokens']);
    }

    /**
     * Trata exceções de validação.
     */
    private function handleValidationException(ValidationException $e): void
    {
        $message = $e->getMessage();
        $ip = $this->request->ip() ?? 'unknown';
        $captchaFlag = $this->turnstile->shouldRequireCaptcha($ip);
        $errors = $e->getErrors();

        if ($captchaFlag) {
            $errors['require_captcha'] = true;
        }

        if (str_contains($message, 'Muitas tentativas') || str_contains($message, 'rate limit')) {
            $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $errors);
            return;
        }

        // Caso especial para conta Google-only
        if (str_contains($message, 'Conta vinculada ao Google')) {
            $this->fail($message, 401, $errors);
            return;
        }

        // Caso especial para email não verificado
        if (!empty($errors['email_not_verified'])) {
            $_SESSION['unverified_email'] = $errors['user_email'] ?? '';
            $this->fail(
                $errors['email'] ?? 'Você precisa verificar seu e-mail antes de fazer login.',
                403,
                $errors
            );
            return;
        }

        // Caso geral
        $this->fail('E-mail ou senha inválidos.', 401, $errors);
    }

    /**
     * Trata erros gerais de login.
     */
    private function handleLoginError(Throwable $e): void
    {
        $message = trim($e->getMessage());
        $ip = $this->request->ip() ?? 'unknown';

        // Incrementa falhas do Turnstile para erros de credenciais
        $this->turnstile->recordFailedAttempt($ip);

        if (str_contains($message, 'credenciais inválidas') || str_contains($message, 'inválidos')) {
            $extra = $this->turnstile->shouldRequireCaptcha($ip)
                ? ['require_captcha' => true]
                : [];
            $this->fail('E-mail ou senha inválidos.', 401, $extra);
            return;
        }

        $this->failAndLog($e, 'Erro ao processar login.');
    }
}
