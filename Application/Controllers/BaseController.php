<?php

namespace Application\Controllers;

use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;

abstract class BaseController
{
    protected View $view;
    protected Auth $auth;
    protected ?int $adminId = null;          // agora espelha Auth::id()
    protected ?string $adminUsername = null; // agora vem de Auth::user()
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->auth     = new Auth();
        $this->request  = new Request();
        $this->response = new Response();
    }

    /** Exige usuário autenticado; se não, manda para /login */
    protected function requireAuth(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->redirect('login');
        }

        // Fonte da verdade = Auth
        $this->adminId       = Auth::id();
        $user                = Auth::user();
        $this->adminUsername = $user->username ?? $user->nome ?? null;

        // Segurança: se por alguma razão não houver id/username, força logout
        if (empty($this->adminId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->redirect('login');
        }
    }

    /** Verifica se está autenticado */
    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    /** Render com header/footer opcionais */
    protected function render(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): void
    {
        $view = new View($viewPath, $data);
        if ($header) $view->setHeader($header);
        echo $view->render();
    }

    /** Atalho para páginas do admin com header/footer padrão */
    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $this->render($viewPath, $data, 'admin/home/header', 'admin/footer');
    }

    /** Redirect helper */
    protected function redirect(string $path): void
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : BASE_URL . ltrim($path, '/');

        $this->response->redirect($url)->send();
    }

    // Helpers diversos (mantidos)
    protected function getPost(string $key, $default = null)
    {
        return $this->request->get($key, $default);
    }
    protected function getQuery(string $key, $default = null)
    {
        return $this->request->get($key, $default);
    }
    protected function sanitize($value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // Flash
    protected function setError(string $message): void
    {
        $_SESSION['error'] = $message;
    }
    protected function setSuccess(string $message): void
    {
        $_SESSION['success'] = $message;
    }
    protected function getError(): ?string
    {
        $x = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        return $x;
    }
    protected function getSuccess(): ?string
    {
        $x = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
        return $x;
    }
}
