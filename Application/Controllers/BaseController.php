<?php

namespace Application\Controllers;

use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;
use Application\Services\LogService;

abstract class BaseController
{
    protected View $view;
    protected Auth $auth;
    protected ?int $adminId = null;
    protected ?string $adminUsername = null;
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
        $this->render($viewPath, $data, 'admin/partials/header', 'admin/footer');
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
        // CORRIGIDO: ler exclusivamente POST
        return $this->request->post($key, $default);
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


    protected function getJson(string $key = null, $default = null)
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return $key ? $default : [];
        $json = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) return $key ? $default : [];
        if ($key === null) return $json;
        return $json[$key] ?? $default;
    }
    protected function failAndLog(\Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = []): void
    {
        // gera um ID pra correlacionar client ↔ server
        $rid = bin2hex(random_bytes(6));

        // contexto do log
        $ctx = array_merge([
            'request_id' => $rid,
            'type'       => get_class($e),
            'message'    => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'trace'      => $e->getTraceAsString(),
            'url'        => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
            'user_id'    => $this->adminId ?? null,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ], $extra);

        // manda pro Monolog
        LogService::error($userMessage, $ctx);

        // responde pro client (sem vazar detalhes sensíveis)
        $this->response->jsonBody([
            'error'      => $userMessage,
            'request_id' => $rid,
        ], $status)->send();
    }
}
