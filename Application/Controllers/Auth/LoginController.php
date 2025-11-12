<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\AuthService;
use Application\Services\CacheService;
use Application\Services\LogService;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Throwable; // Importado para capturar todos os erros

class LoginController extends BaseController
{
    /**
     * Injeção de dependência via Construtor (PHP 8.0+).
     * O BaseController já fornece $this->auth, $this->request, $this->response, $this->cache.
     */
    public function __construct(
        private readonly AuthService $authService = new AuthService()
    ) {
        parent::__construct();
    }

    /**
     * Exibe o formulário de login (View).
     */
    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirectToDashboard();
            return;
        }
        $this->renderLoginForm();
    }

    /**
     * Processa a tentativa de login (API Endpoint).
     */
    public function processLogin(): void
    {
        // Usa o $this->request injetado pelo BaseController
        if (!$this->request->isPost()) {
            LogService::warning('Login request rejected: not POST');
            $this->fail('Requisição inválida. Método esperado: POST.', 405); // 405 Method Not Allowed
            return;
        }

        $credentials = [
            'email'    => trim(strtolower($this->request->post('email', ''))),
            'password' => $this->request->post('password', '')
        ];

        try {
            $this->validateCsrfToken();
            $this->applyRateLimit();

            $result = $this->authService->login($credentials['email'], $credentials['password']);

            $this->clearOldCsrfTokens();

            // Usa o helper ok() do BaseController
            $redirectUrl = $result['redirect'] ?? (BASE_URL . 'dashboard');
            $url = filter_var($redirectUrl, FILTER_VALIDATE_URL) ? $redirectUrl : (BASE_URL . ltrim($redirectUrl, '/'));
            
            $this->ok([
                'message'  => 'Login realizado com sucesso!',
                'redirect' => $url
            ]);

        } catch (ValidationException $e) {
            $this->handleValidationException($e, $credentials['email']);
        
        } catch (Throwable $e) {
            $msg = trim($e->getMessage());
            
            // Captura específica para falha de login
            if (stripos($msg, 'credenciais inválidas') !== false) {
                $this->fail('E-mail ou senha inválidos.', 401); // 401 Unauthorized
                return;
            }
            
            // Usa o failAndLog() do BaseController para erros inesperados
            $this->failAndLog($e, 'Erro ao processar login.');
        }
    }

    /**
     * Processa o logout (API Endpoint).
     */
    public function logout(): void
    {
        $this->authService->logout();

        $expectsJson = $this->request->wantsJson() || $this->request->isAjax();

        if ($expectsJson) {
            // Retorna JSON para clientes que esperam API
            $this->ok([
                'message'  => 'Logout realizado com sucesso.',
                'redirect' => BASE_URL . 'login'
            ]);
            return;
        }

        // Fluxo web padrão -> redireciona direto
        $this->redirect('login');
    }

    // --- Métodos Auxiliares Privados ---

    private function redirectToDashboard(): void
    {
        $this->redirect('dashboard'); // Usa o redirect do BaseController
    }

    private function renderLoginForm(): void
    {
        $data = [
            'error'      => $this->getError(), // Usa o helper do BaseController
            'success'    => $this->getSuccess(), // Usa o helper do BaseController
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
        ];

        // Usa o render do BaseController
        $this->render('admin/admins/login', $data, null, 'admin/footer');
    }

    /**
     * Valida o token CSRF usando o $this->request do BaseController.
     * @throws ValidationException
     */
    private function validateCsrfToken(): void
    {
        CsrfMiddleware::handle($this->request, 'login_form');
    }

    /**
     * Aplica o Rate Limit usando o $this->cache e $this->request do BaseController.
     * @throws ValidationException
     */
    private function applyRateLimit(): void
    {
        if ($this->cache === null) {
            LogService::warning('CacheService not available for Rate Limiting. Skipping rate limit.');
            return; // Falha "aberta" se o cache não estiver configurado
        }
        
        $rateLimiter = new RateLimitMiddleware($this->cache);
        $identifier  = RateLimitMiddleware::getIdentifier($this->request);
        $rateLimiter->handle($this->request, 'login:' . $identifier);
    }

    /**
     * Limpa tokens CSRF antigos da sessão.
     */
    private function clearOldCsrfTokens(): void
    {
        unset($_SESSION['csrf_tokens']);
    }

    /**
     * Trata exceções de validação (CSRF, Rate Limit, Credenciais).
     */
    private function handleValidationException(ValidationException $e, string $email): void
    {
        $m = $e->getMessage();
        
        // str_contains é do PHP 8.0+
        if (str_contains($m, 'Muitas tentativas') || str_contains($m, 'rate limit')) {
            $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429); // 429 Too Many Requests
            return;
        }
        
        // Outras falhas de validação (CSRF ou login)
        $this->fail('E-mail ou senha inválidos.', 401, $e->getErrors()); // 401 Unauthorized
    }
}
