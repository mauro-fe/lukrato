<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\AuthService;
use Application\Services\CacheService;
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

    /**
     * Exibe o formulário de login (se já autenticado, redireciona)
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
     * Processa tentativa de login via AJAX (POST)
     */
    public function processLogin(): void
    {
        $this->prepareJsonResponse();

        if (!$this->isPostRequest()) {
            \Application\Services\LogService::warning('Login request rejected: not POST');
            $this->respondError('Requisição inválida.');
            return;
        }

        $credentials = ['email' => '', 'password' => ''];

        try {
            // 1) CSRF
            \Application\Services\LogService::info('Login CSRF check: start');
            $this->validateCsrfToken();
            \Application\Services\LogService::info('Login CSRF check: ok');

            // 2) credenciais
            $credentials = $this->getLoginCredentials(); // deve retornar ['email'=>..., 'password'=>...]
            \Application\Services\LogService::info('Login received credentials', ['email' => $credentials['email'] ?? '']);

            // 3) rate limit
            \Application\Services\LogService::info('Login rate-limit: start');
            $this->applyRateLimit();
            \Application\Services\LogService::info('Login rate-limit: ok');

            // 4) autenticar
            \Application\Services\LogService::info('Login authenticate: start', ['email' => $credentials['email'] ?? '']);
            $this->authenticateUser($credentials);
            \Application\Services\LogService::info('Login authenticate: ok', ['email' => $credentials['email'] ?? '']);
        } catch (\Application\Core\Exceptions\ValidationException $e) {
            \Application\Services\LogService::warning('Login validation exception', [
                'email' => $credentials['email'] ?? '',
                'errors' => $e->getErrors(),
                'msg' => $e->getMessage(),
            ]);
            $this->handleValidationException($e, $credentials['email'] ?? '');
        } catch (\Throwable $e) {
            // pega qualquer erro: CSRF fail, 500, etc.
            \Application\Services\LogService::error('Login fatal error', [
                'email' => $credentials['email'] ?? '',
                'exception' => get_class($e),
                'msg' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                // 'trace' => $e->getTraceAsString(), // habilite se precisar (cuidado com tamanho do log)
            ]);
            $this->handleGeneralException($e);
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->redirectWithLogoutNotification();
    }

    // =========================
    // RENDER
    // =========================

    private function redirectToDashboard(): void
    {
        // Como você quer manter o padrão antigo, se ainda usa admin/{username}/dashboard:
        $username = $_SESSION['admin_username'] ?? 'admin';
        $this->redirect('admin/' . $username . '/dashboard');

        // Se já migrou para /dashboard, troque por:
        // $this->redirect('dashboard');
    }

    private function renderLoginForm(): void
    {
        $data = [
            'error'      => $this->getError(),
            'success'    => $this->getSuccess(),
            'csrf_token' => \Application\Middlewares\CsrfMiddleware::generateToken('login_form'),
        ];

        // Mantenha exatamente como no seu projeto antigo:
        $this->render('admin/admins/login', $data, 'admin/header', 'admin/footer');
        // (se o seu header certo for 'admin/home/header', use ele)
    }

    // =========================
    // PROCESSAMENTO
    // =========================

    private function prepareJsonResponse(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');
    }

    private function isPostRequest(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
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
        $request = new Request();
        $rateLimiter = new RateLimitMiddleware(new CacheService());
        $identifier = RateLimitMiddleware::getIdentifier($request);
        $rateLimiter->handle($request, 'login:' . $identifier);
    }

    private function isAccountBlocked(string $email): bool
    {
        // No novo modelo não há coluna de bloqueio em `usuarios`.
        // Caso queira bloquear, implemente via cache/redis aqui.
        // Por ora, sempre false.
        return false;
    }

    private function authenticateUser(array $credentials): void
    {
        // AuthService atualizado já aceita (email, password)
        $result = $this->authService->login($credentials['email'], $credentials['password']);

        // Limpa tokens CSRF após sucesso
        $this->clearOldCsrfTokens();

        $this->respondLoginSuccess($result['redirect']);
    }

    // =========================
    // ERROS
    // =========================

    private function handleValidationException(ValidationException $e, string $email): void
    {
        if ($this->isRateLimitError($e)) {
            $this->respondError('Muitas tentativas. Aguarde 1 minuto e tente novamente.');
            return;
        }

        // Se quiser bloqueio temporário por email via cache, implemente aqui.
        // $this->blockAccountTemporarily($email);

        // Mantém resposta geral (frontend já lida com mensagem genérica)
        $this->respondError('E-mail ou senha inválidos.', $e->getErrors());
    }

    private function handleGeneralException(\Exception $e): void
    {
        // Se vier "Credenciais inválidas." do AuthService, devolvemos msg amigável
        $msg = trim($e->getMessage());
        if (stripos($msg, 'credenciais inválidas') !== false) {
            $this->respondError('E-mail ou senha inválidos.');
            return;
        }

        $this->respondError('Erro ao processar login: ' . htmlspecialchars($msg));
    }

    private function isRateLimitError(ValidationException $e): bool
    {
        $m = $e->getMessage();
        return str_contains($m, 'Muitas tentativas') || str_contains($m, 'rate limit');
    }

    // Se quiser bloquear por cache, implemente aqui (opcional)
    private function blockAccountTemporarily(string $email): void
    {
        // Exemplo (pseudocódigo):
        // CacheService::put('lock:login:' . sha1($email), 1, 60);
    }

    // =========================
    // RESPOSTAS JSON
    // =========================

    private function respondError(string $message, array $errors = []): void
    {
        $response = ['status' => 'error', 'message' => $message];
        if (!empty($errors)) $response['errors'] = $errors;
        echo json_encode($response);
    }

    private function respondLoginSuccess(string $redirectUrl): void
    {
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Login realizado com sucesso!',
            'redirect' => $redirectUrl
        ]);
    }

    private function clearOldCsrfTokens(): void
    {
        unset($_SESSION['csrf_tokens']);
    }
    private function redirectWithLogoutNotification(): void
    {
        // Mantendo o caminho antigo de login:
        echo "<script>
            localStorage.setItem('logout_success', '1');
            window.location.href = '" . BASE_URL . "admin/login';
          </script>";
    }

    // =========================
    // COMPAT (se ainda usar em algum lugar)
    // =========================

    private function handleLoginError(string $message, array $errors = []): void
    {
        if ($this->request->isAjax()) {
            $this->jsonError($message, 400, $errors);
            return;
        }
        $this->setError($message);
        $this->redirect('login');
    }
}