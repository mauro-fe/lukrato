<?php

namespace Application\Controllers;

use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;
use Application\Services\LogService;
use Application\Services\CacheService;

abstract class BaseController
{
    protected View $view;
    protected Auth $auth;
    protected ?int $userId = null;
    protected ?string $adminUsername = null;
    protected Request $request;
    protected Response $response;

    protected ?CacheService $cache = null;

    private ?array $jsonBodyCache = null;

    public function __construct()
    {
        $this->auth     = new Auth();
        $this->request  = new Request();
        $this->response = new Response();

        $this->cache    = class_exists(CacheService::class) ? new CacheService() : null;
    }

    protected function requireAuth(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->redirect('login');
        }
        $this->userId       = Auth::id();
        $user                = Auth::user();
        $this->adminUsername = $user->username ?? $user->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->redirect('login');
        }
    }

    protected function requireAuthApi(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->response->jsonBody(['error' => 'Não autenticado'], 401)->send();
            return;
        }
        $this->userId = Auth::id();
        $user = Auth::user();
        $this->adminUsername = $user->username ?? $user->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->response->jsonBody(['error' => 'Sessão inválida'], 401)->send();
            return;
        }
    }

    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    protected function render(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): void
    {
        if (!array_key_exists('menu', $data) || $data['menu'] === null || $data['menu'] === '') {
            $inferred = $this->inferMenuFromView($viewPath);
            if ($inferred !== null) {
                $data['menu'] = $inferred;
            }
        }

        $view = new View($viewPath, $data);
        if ($header) $view->setHeader($header);
        if ($footer) $view->setFooter($footer);
        echo $view->render();
    }

    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $this->render($viewPath, $data, 'admin/partials/header', 'admin/partials/footer');
    }

    protected function redirect(string $path): void
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        $this->response->redirect($url)->send();
    }

    protected function getPost(string $key, $default = null)
    {
        return $this->request->post($key, $default);
    }
    protected function getQuery(string $key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    protected function sanitize($value): string
    {
        return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeDeep($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeDeep'], $value);
        }
        return is_string($value) ? $this->sanitize($value) : $value;
    }

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
        if ($this->jsonBodyCache === null) {
            $raw = file_get_contents('php://input') ?: '';
            $this->jsonBodyCache = $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($this->jsonBodyCache)) {
                $this->jsonBodyCache = [];
            }
        }
        if ($key === null) return $this->jsonBodyCache;
        return $this->jsonBodyCache[$key] ?? $default;
    }

    protected function ok(array $payload = [], int $status = 200): void
    {
        Response::success($payload, $status);
    }
    protected function fail(string $message, int $status = 400, array $extra = []): void
    {
        Response::error($message, $status, $extra);
    }

    protected function failAndLog(\Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = []): void
    {
        $rid = bin2hex(random_bytes(6));

        $ctx = array_merge([
            'request_id' => $rid,
            'type'       => get_class($e),
            'message'    => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'trace'      => $e->getTraceAsString(),
            'url'        => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
            'user_id'    => $this->userId ?? null,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ], $extra);

        LogService::error($userMessage, $ctx);

        $this->response->jsonBody([
            'error'      => $userMessage,
            'request_id' => $rid,
        ], $status)->send();
    }

    protected function inferMenuFromView(string $viewPath): ?string
    {
        $trimmed = trim($viewPath, '/');
        if ($trimmed === '') {
            return null;
        }

        $segments = preg_split('#[\\/]+#', $trimmed);
        if (!$segments || ($segments[0] ?? '') !== 'admin') {
            return null;
        }

        $map = [
            'dashboard'   => 'dashboard',
            'contas'      => 'contas',
            'lancamentos' => 'lancamentos',
            'relatorios'  => 'relatorios',
            'categorias'  => 'categorias',
            'perfil'      => 'perfil',
        ];

        $section = $segments[1] ?? null;
        return $section !== null ? ($map[$section] ?? null) : null;
    }
}
