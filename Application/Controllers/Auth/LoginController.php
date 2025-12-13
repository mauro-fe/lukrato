<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\Services\CacheService;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\LogService;
use Throwable;

class LoginController extends BaseController
{
    private AuthService $authService;

    public function __construct(?CacheService $cache = null)
    {
        parent::__construct();

        // Se não passar nada, cria o CacheService padrão.
        // Se passar (num teste, por exemplo), usa o que veio.
        $this->cache = $cache ?? new CacheService();

        $this->authService = new AuthService($this->request, $this->cache);
    }


    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }

        $activeTab = $_SESSION['auth_active_tab'] ?? 'login';
        unset($_SESSION['auth_active_tab']);

        $registerErrors = $_SESSION['register_errors'] ?? null;
        unset($_SESSION['register_errors']);

        if (!empty($registerErrors)) {
            $activeTab = 'register';
        }

        $errorMessage = $this->getError();
        $socialSuccess = isset($_GET['new_google']) && $_GET['new_google'] == 1;

        $this->render('admin/admins/login', [
            'error' => $errorMessage,
            'registerErrorMessage' => $errorMessage,
            'registerErrors' => $registerErrors,
            'activeTab' => $activeTab,
            'success' => $this->getSuccess(),
            'socialSuccess' => $socialSuccess,
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
        ], null, 'admin/footer');
    }



    /**
     * Processa a tentativa de login.
     */
    public function processLogin(): void
    {
        if (!$this->request->isPost()) {
            LogService::warning('Login request rejected: not POST');
            $this->fail('Requisição inválida. Método esperado: POST.', 405);
            return;
        }

        try {
            // Segurança
            $this->validateCsrfToken();
            $this->applyRateLimit();

            // Autenticação
            $result = $this->authService->login(
                $this->request->post('email', ''),
                $this->request->post('password', '')
            );

            $this->clearOldCsrfTokens();

            $this->ok([
                'message'  => 'Login realizado com sucesso!',
                'redirect' => $result['redirect']
            ]);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (Throwable $e) {
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

        if (str_contains($message, 'Muitas tentativas') || str_contains($message, 'rate limit')) {
            $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $e->getErrors());
            return;
        }

        // Caso especial para conta Google-only
        if (str_contains($message, 'Conta vinculada ao Google')) {
            $this->fail($message, 401, $e->getErrors());
            return;
        }

        // Caso geral
        $this->fail('E-mail ou senha inválidos.', 401, $e->getErrors());
    }

    /**
     * Trata erros gerais de login.
     */
    private function handleLoginError(Throwable $e): void
    {
        $message = trim($e->getMessage());

        if (str_contains($message, 'credenciais inválidas') || str_contains($message, 'inválidos')) {
            $this->fail('E-mail ou senha inválidos.', 401);
            return;
        }

        $this->failAndLog($e, 'Erro ao processar login.');
    }
}
