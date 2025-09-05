<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\AuthService;
use Application\Services\CacheService;
use Application\Services\LogService;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Application\Core\Request;

class LoginController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /** Exibe o formulário de login (se já autenticado, redireciona) */
    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirectToDashboard();
            return;
        }
        $this->renderLoginForm();
    }

    /** Processa tentativa de login via AJAX (POST) */
    public function processLogin(): void
    {
        $this->prepareJsonResponse();

        if (!$this->isPostRequest()) {
            LogService::warning('Login request rejected: not POST');
            $this->respondError('Requisição inválida.');
            return;
        }

        $credentials = ['email' => '', 'password' => ''];

        try {
            // 1) CSRF
            $this->validateCsrfToken();

            // 2) credenciais
            $credentials = $this->getLoginCredentials();

            // 3) rate limit
            $this->applyRateLimit();

            // 4) autenticar
            // AuthService->login DEVE setar a sessão de usuário (ex.: Auth::login($user))
            // e retornar o path de redirect (ex.: 'dashboard')
            $result = $this->authService->login($credentials['email'], $credentials['password']);

            // CSRF cleanup após sucesso
            $this->clearOldCsrfTokens();

            $this->respondLoginSuccess($result['redirect'] ?? (BASE_URL . 'dashboard'));
        } catch (ValidationException $e) {
            $this->handleValidationException($e, $credentials['email'] ?? '');
        } catch (\Throwable $e) {
            $msg = trim($e->getMessage());
            if (stripos($msg, 'credenciais inválidas') !== false) {
                $this->respondError('E-mail ou senha inválidos.');
                return;
            }
            $this->respondError('Erro ao processar login: ' . htmlspecialchars($msg));
        }
    }

    /** Logout */
    public function logout(): void
    {
        $this->authService->logout();
        // Redireciona pro /login com “toaster” via localStorage (mantive seu padrão)
        echo "<script>
            localStorage.setItem('logout_success', '1');
            window.location.href = '" . BASE_URL . "login';
        </script>";
    }

    // ========================= RENDER/REDIRECT =========================

    private function redirectToDashboard(): void
    {
        $this->redirect('dashboard');
    }

    private function renderLoginForm(): void
    {
        $data = [
            'error'      => $this->getError(),
            'success'    => $this->getSuccess(),
            'csrf_token' => \Application\Middlewares\CsrfMiddleware::generateToken('login_form'),
        ];

        // View atual do seu projeto de login
        $this->render('admin/admins/login', $data, null, 'admin/footer');
    }

    // ========================= PROCESSAMENTO =========================

    private function prepareJsonResponse(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');
    }

    private function isPostRequest(): bool
    {
        return (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
    }

    private function validateCsrfToken(): void
    {
        $request = new Request();
        // Token name deve bater com csrf_input('login_form') do HTML
        CsrfMiddleware::handle($request, 'login_form');
    }

    private function getLoginCredentials(): array
    {
        return [
            'email'    => trim(strtolower($_POST['email'] ?? '')),
            'password' => $_POST['password'] ?? ''
        ];
    }

    private function applyRateLimit(): void
    {
        $request     = new Request();
        $rateLimiter = new RateLimitMiddleware(new CacheService());
        $identifier  = RateLimitMiddleware::getIdentifier($request);
        $rateLimiter->handle($request, 'login:' . $identifier);
    }

    // ========================= RESPOSTAS JSON =========================

    private function respondError(string $message, array $errors = []): void
    {
        $response = ['status' => 'error', 'message' => $message];
        if (!empty($errors)) $response['errors'] = $errors;
        echo json_encode($response);
    }

    private function respondLoginSuccess(string $redirectUrl): void
    {
        // aceita caminho relativo ou absoluto
        $url = filter_var($redirectUrl, FILTER_VALIDATE_URL) ? $redirectUrl : (BASE_URL . ltrim($redirectUrl, '/'));
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Login realizado com sucesso!',
            'redirect' => $url
        ]);
    }

    private function clearOldCsrfTokens(): void
    {
        unset($_SESSION['csrf_tokens']);
    }

    // ========================= ERROS =========================

    private function handleValidationException(ValidationException $e, string $email): void
    {
        $m = $e->getMessage();
        if (str_contains($m, 'Muitas tentativas') || str_contains($m, 'rate limit')) {
            $this->respondError('Muitas tentativas. Aguarde 1 minuto e tente novamente.');
            return;
        }
        $this->respondError('E-mail ou senha inválidos.', $e->getErrors());
    }
}
