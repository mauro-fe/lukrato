<?php

namespace Application\Controllers;

use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;
use Application\Services\LogService;
use Application\Services\CacheService;
use Throwable; // Importar Throwable para capturar todos os erros

abstract class BaseController
{
    protected ?int $userId = null;
    protected ?string $adminUsername = null;
    private ?array $jsonBodyCache = null;

    /**
     * Construtor com Propriedades Promovidas (PHP 8.0).
     * As dependências são injetadas e atribuídas automaticamente.
     */
    public function __construct(
        protected readonly Auth $auth = new Auth(),
        protected readonly Request $request = new Request(),
        protected readonly Response $response = new Response(), // Mantido se $this->response for usado
        protected ?CacheService $cache = null
    ) {
        // Inicializa o cache condicionalmente
        if ($this->cache === null && class_exists(CacheService::class)) {
            $this->cache = new CacheService();
        }
    }

    /**
     * Exige autenticação para rotas web (redireciona em caso de falha).
     */
    protected function requireAuth(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->redirect('login');
        }
        
        $this->userId = Auth::id();
        $user         = Auth::user();
        // Uso de Null Coalescing para garantir um valor
        $this->adminUsername = $user?->username ?? $user?->nome ?? null; 

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->redirect('login');
        }
    }

    /**
     * Exige autenticação para rotas de API (retorna JSON 401 em caso de falha).
     */
    protected function requireAuthApi(): void
    {
        if (!Auth::isLoggedIn()) {
            Response::unauthorized('Não autenticado'); // Usa helper de Resposta
            return;
        }
        
        $this->userId = Auth::id();
        $user         = Auth::user();
        $this->adminUsername = $user?->username ?? $user?->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            Response::unauthorized('Sessão inválida'); // Usa helper de Resposta
            return;
        }
    }

    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    /**
     * Renderiza uma view, opcionalmente com header e footer.
     */
    protected function render(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): void
    {
        // Tenta inferir o item de menu ativo se não for fornecido
        if (empty($data['menu'])) {
            $data['menu'] = $this->inferMenuFromView($viewPath) ?? $data['menu'] ?? null;
        }

        $view = new View($viewPath, $data);
        if ($header) $view->setHeader($header);
        if ($footer) $view->setFooter($footer);
        
        echo $view->render();
    }

    /**
     * Helper para renderizar páginas do painel de administração com header/footer padrão.
     */
    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $this->render($viewPath, $data, 'admin/partials/header', 'admin/partials/footer');
    }

    /**
     * Redireciona o usuário para um caminho interno ou URL completa.
     */
    protected function redirect(string $path): void
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        // Usa a instância de Response injetada
        $this->response->redirect($url)->send();
    }

    // --- Helpers de Request ---

    protected function getPost(string $key, mixed $default = null): mixed
    {
        return $this->request->post($key, $default);
    }

    protected function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->request->get($key, $default);
    }

    /**
     * Obtém o corpo da requisição JSON (com cache interno).
     * @param string|null $key Chave específica para retornar (ou null para o array todo).
     * @param mixed $default Valor padrão se a chave não existir.
     */
    protected function getJson(string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonBodyCache === null) {
            $raw = file_get_contents('php://input') ?: '';
            try {
                // Uso de JSON_THROW_ON_ERROR (PHP 7.3+) para sanitização
                $this->jsonBodyCache = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($this->jsonBodyCache)) {
                    $this->jsonBodyCache = [];
                }
            } catch (\JsonException $e) {
                $this->jsonBodyCache = [];
            }
        }
        
        if ($key === null) {
            return $this->jsonBodyCache;
        }
        
        return $this->jsonBodyCache[$key] ?? $default;
    }

    // --- Helpers de Sanitização ---

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeDeep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeDeep'], $value);
        }
        return is_string($value) ? $this->sanitize($value) : $value;
    }

    // --- Helpers de Flash Message (Sessão) ---

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

    // --- Helpers de Resposta API ---

    /** Retorna uma resposta de sucesso (200 OK). */
    protected function ok(array $payload = [], int $status = 200): void
    {
        Response::success($payload, $status);
    }

    /** Retorna uma resposta de erro (400 Bad Request / 422 Unprocessable). */
    protected function fail(string $message, int $status = 400, array $extra = []): void
    {
        Response::error($message, $status, $extra);
    }

    /**
     * Loga um erro (Throwable) e retorna uma resposta de erro (500 Internal Error)
     * de forma padronizada.
     */
    protected function failAndLog(Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = []): void
    {
        $rid = bin2hex(random_bytes(6)); // Request ID para rastreamento

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

        // Padroniza a resposta de erro
        Response::error($userMessage, $status, ['request_id' => $rid]);
    }

    /**
     * Tenta inferir o item de menu ativo com base no caminho da view.
     * Refatorado com 'match' (PHP 8.0).
     */
    protected function inferMenuFromView(string $viewPath): ?string
    {
        $trimmed = trim($viewPath, '/');
        $segments = preg_split('#[\\/]+#', $trimmed);

        // Se não for 'admin' ou não tiver um segundo segmento, retorna null
        if (($segments[0] ?? null) !== 'admin') {
            return null;
        }

        // Usa match para mapear o segundo segmento (ex: admin/dashboard/index -> dashboard)
        return match ($segments[1] ?? null) {
            'dashboard'   => 'dashboard',
            'contas'      => 'contas',
            'lancamentos' => 'lancamentos',
            'relatorios'  => 'relatorios',
            'categorias'  => 'categorias',
            'perfil'      => 'perfil',
            default       => null,
        };
    }
}