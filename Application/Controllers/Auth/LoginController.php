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

    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirectToDashboard();
            return;
        }
        $this->renderLoginForm();
    }

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
            $this->validateCsrfToken();

            $credentials = $this->getLoginCredentials();

            $this->applyRateLimit();

            $result = $this->authService->login($credentials['email'], $credentials['password']);

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

    public function logout(): void
    {
        $this->authService->logout();
        echo "<script>
            localStorage.setItem('logout_success', '1');
            window.location.href = '" . BASE_URL . "login';
        </script>";
    }


    private function redirectToDashboard(): void
    {
        $this->redirect('dashboard');
    }

    private function renderLoginForm(): void
    {
        $data = [
            'error'      => $this->getError(),
            'success'    => $this->getSuccess(),
            'csrf_token' => CsrfMiddleware::generateToken('login_form'),
        ];

        $this->render('admin/admins/login', $data, null, 'admin/footer');
    }


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

    private function respondError(string $message, array $errors = []): void
    {
        $response = ['status' => 'error', 'message' => $message];
        if (!empty($errors)) $response['errors'] = $errors;
        echo json_encode($response);
    }

    private function respondLoginSuccess(string $redirectUrl): void
    {
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
