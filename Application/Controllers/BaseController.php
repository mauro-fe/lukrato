<?php

namespace Application\Controllers;

use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\CacheService;
use Application\Models\Telefone;
use Application\Models\BlogCategoria;
use Throwable;

abstract class BaseController
{
    protected ?int $userId = null;
    protected ?string $adminUsername = null;
    private ?array $jsonBodyCache = null;

    public function __construct(
        protected readonly Auth $auth = new Auth(),
        protected readonly Request $request = new Request(),
        protected readonly Response $response = new Response(),
        protected ?CacheService $cache = null
    ) {
        if ($this->cache === null && class_exists(CacheService::class)) {
            $this->cache = new CacheService();
        }
    }
    protected function requireAuth(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->redirect('login');
        }

        $this->userId = Auth::id();
        $user         = Auth::user();
        $this->adminUsername = $user?->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->redirect('login');
        }
    }


    protected function requireAuthApi(): void
    {
        if (!Auth::isLoggedIn()) {
            Response::unauthorized('Não autenticado');
            exit;
        }

        $this->userId = Auth::id();
        $user         = Auth::user();
        $this->adminUsername = $user?->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            Response::unauthorized('Sessão inválida');
            exit;
        }
    }

    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    protected function render(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): void
    {
        if (empty($data['menu'])) {
            $data['menu'] = $this->inferMenuFromView($viewPath) ?? $data['menu'] ?? null;
        }

        // Auto-inject admin layout variables when using admin header
        if ($header === 'admin/partials/header') {
            $data = $this->injectAdminLayoutData($data);
        }

        // Auto-inject site layout variables when using site header
        if ($header === 'site/partials/header') {
            $data = $this->injectSiteLayoutData($data);
        }

        $view = new View($viewPath, $data);
        if ($header) $view->setHeader($header);
        if ($footer) $view->setFooter($footer);

        echo $view->render();
    }


    /**
     * Atalho: renderiza com header/footer admin.
     */
    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $this->render($viewPath, $data, 'admin/partials/header', 'admin/partials/footer');
    }

    /**
     * Injeta automaticamente variáveis do layout admin (plano, role, tema, etc.)
     * Só sobrescreve se o controller NÃO passou o valor explicitamente.
     */
    private function injectAdminLayoutData(array $data): array
    {
        $currentUser = $data['currentUser'] ?? Auth::user();

        // Plan check — fonte de verdade: Usuario::isPro()
        $isPro = $data['isPro'] ?? (
            $currentUser && method_exists($currentUser, 'isPro') && $currentUser->isPro()
        );

        $data['currentUser']    = $currentUser;
        $data['username']       = $data['username'] ?? ($currentUser?->nome ?? 'usuario');
        $data['isSysAdmin']     = $data['isSysAdmin'] ?? (((int)($currentUser?->is_admin ?? 0)) === 1);
        $data['isPro']          = $isPro;
        $data['planLabel']      = $data['planLabel'] ?? ($isPro ? 'PRO' : 'FREE');
        $data['showUpgradeCTA'] = $data['showUpgradeCTA'] ?? (!$isPro);

        // Theme
        if (!isset($data['userTheme'])) {
            $data['userTheme'] = 'dark';
            if ($currentUser && isset($currentUser->theme_preference)) {
                $data['userTheme'] = in_array($currentUser->theme_preference, ['light', 'dark'])
                    ? $currentUser->theme_preference
                    : 'dark';
            }
        }

        // Top navbar
        if (!isset($data['topNavFirstName'])) {
            $fullName = $currentUser->nome ?? ($currentUser->name ?? '');
            $data['topNavFirstName'] = $fullName ? explode(' ', trim($fullName))[0] : '';
        }

        $data['currentBreadcrumbs'] = $data['currentBreadcrumbs'] ?? $this->resolveBreadcrumbs($data['menu'] ?? '');

        // Botão suporte
        if (!isset($data['supportName'])) {
            $data['supportName']  = $currentUser->nome ?? '';
            $data['supportEmail'] = $currentUser->email ?? '';
            $userId = $currentUser->id_usuario ?? $currentUser->id ?? null;
            $telefoneModel = $userId ? Telefone::where('id_usuario', $userId)->first() : null;
            $data['supportTel']   = $telefoneModel?->numero ?? '';
            $data['supportDdd']   = $telefoneModel?->ddd?->codigo ?? '';
        }

        return $data;
    }

    private function injectSiteLayoutData(array $data): array
    {
        if (!isset($data['headerBlogCategorias'])) {
            $data['headerBlogCategorias'] = BlogCategoria::ordenadas()->get();
        }
        return $data;
    }

    private function resolveBreadcrumbs(string $menu): array
    {
        $map = [
            'dashboard'    => [],
            'contas'       => [['label' => 'Finanças', 'icon' => 'wallet']],
            'cartoes'      => [['label' => 'Finanças', 'icon' => 'wallet']],
            'faturas'      => [['label' => 'Finanças', 'icon' => 'wallet'], ['label' => 'Cartões', 'url' => 'cartoes', 'icon' => 'credit-card']],
            'categorias'   => [['label' => 'Organização', 'icon' => 'folder']],
            'lancamentos'  => [['label' => 'Finanças', 'icon' => 'wallet']],
            'relatorios'   => [['label' => 'Análises', 'icon' => 'bar-chart-3']],
            'gamification' => [['label' => 'Perfil', 'icon' => 'user']],
            'perfil'       => [],
            'billing'      => [['label' => 'Perfil', 'icon' => 'user']],
        ];

        return $map[$menu] ?? [];
    }


    protected function redirect(string $path): void
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        $this->response->redirect($url)->send();
    }

    protected function getPost(string $key, mixed $default = null): mixed
    {
        return $this->request->post($key, $default);
    }

    protected function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->request->get($key, $default);
    }


    protected function getJson(string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonBodyCache === null) {
            $raw = file_get_contents('php://input') ?: '';
            try {
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

    /**
     * Obtém o payload da requisição (JSON ou POST).
     * Útil para APIs que aceitam ambos formatos.
     */
    protected function getRequestPayload(): array
    {
        $payload = $this->getJson() ?? [];
        if (empty($payload)) {
            $payload = $this->request->post() ?? $_POST ?? [];
        }
        return $payload;
    }

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


    protected function ok(array $payload = [], int $status = 200): void
    {
        $message = $payload['message'] ?? 'Success';
        if (array_key_exists('message', $payload)) {
            unset($payload['message']);
        }

        Response::success($payload, $message, $status);
    }

    protected function fail(string $message, int $status = 400, array $extra = []): void
    {
        Response::error($message, $status, $extra);
    }

    protected function failAndLog(Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = []): void
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

        Response::error($userMessage, $status, ['request_id' => $rid]);
    }

    protected function inferMenuFromView(string $viewPath): ?string
    {
        $trimmed = trim($viewPath, '/');
        $segments = preg_split('#[\\/]+#', $trimmed);

        if (($segments[0] ?? null) !== 'admin') {
            return null;
        }

        return match ($segments[1] ?? null) {
            'dashboard'     => 'dashboard',
            'contas'        => 'contas',
            'lancamentos'   => 'lancamentos',
            'faturas'       => 'faturas',
            'parcelamentos' => 'faturas', // Redirecionar para faturas
            'relatorios'    => 'relatorios',
            'categorias'    => 'categorias',
            'financas'      => 'financas',
            'perfil'        => 'perfil',
            'sysadmin'      => 'super_admin',
            default         => null,
        };
    }
}
