<?php

namespace Application\Controllers;

use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ClientErrorException;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Exceptions\ValidationException;
use Application\Enums\LogCategory;
use Application\Core\View;
use Application\Lib\Auth;
use Application\Core\Response;
use Application\Core\Request;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\CacheService;
use Application\Models\Telefone;
use Application\Models\BlogCategoria;
use Application\Models\Usuario;
use DomainException;
use InvalidArgumentException;
use Throwable;
use ValueError;

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
            $this->throwRedirectResponse('login');
        }

        $this->userId = Auth::id();
        $user         = Auth::user();
        $this->adminUsername = $user?->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            $this->throwRedirectResponse('login');
        }
    }


    protected function requireAuthApiOrFail(): void
    {
        if (!Auth::isLoggedIn()) {
            throw new AuthException('Nao autenticado', 401);
        }

        $this->userId = Auth::id();
        $user = Auth::user();
        $this->adminUsername = $user?->nome ?? null;

        if (empty($this->userId) || empty($this->adminUsername)) {
            $this->auth->logout();
            throw new AuthException('Sessao invalida', 401);
        }
    }

    protected function requireUserId(): int
    {
        $this->requireAuth();

        return (int) $this->userId;
    }

    protected function requireUser(): Usuario
    {
        $this->requireAuth();
        $user = Auth::user();

        if (!$user) {
            $this->auth->logout();
            $this->throwRedirectResponse('login');
        }

        return $user;
    }

    protected function requireAdminUser(): Usuario
    {
        $user = $this->requireUser();

        if ((int) ($user->is_admin ?? 0) !== 1) {
            $this->throwRedirectResponse('login');
        }

        return $user;
    }

    protected function requireApiUserIdOrFail(): int
    {
        $this->requireAuthApiOrFail();

        return (int) $this->userId;
    }

    protected function requireApiUserOrFail(): Usuario
    {
        $this->requireAuthApiOrFail();
        $user = Auth::user();

        if (!$user) {
            $this->auth->logout();
            throw new AuthException('Sessao invalida', 401);
        }

        return $user;
    }

    protected function requireApiUserIdAndReleaseSessionOrFail(): int
    {
        $userId = $this->requireApiUserIdOrFail();
        $this->releaseSession();

        return $userId;
    }

    protected function requireApiUserAndReleaseSessionOrFail(): Usuario
    {
        $user = $this->requireApiUserOrFail();
        $this->releaseSession();

        return $user;
    }

    protected function requireApiAdminUserOrFail(string $message = 'Acesso negado'): Usuario
    {
        $user = $this->requireApiUserOrFail();

        if ((int) ($user->is_admin ?? 0) !== 1) {
            throw new AuthException($message, 403);
        }

        return $user;
    }

    protected function requireApiAdminUserAndReleaseSessionOrFail(string $message = 'Acesso negado'): Usuario
    {
        $user = $this->requireApiAdminUserOrFail($message);
        $this->releaseSession();

        return $user;
    }

    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    protected function renderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
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

        return Response::htmlResponse($view->render());
    }


    protected function renderAdminResponse(string $viewPath, array $data = []): Response
    {
        return $this->renderResponse($viewPath, $data, 'admin/partials/header', 'admin/partials/footer');
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
        $data['planTier']       = $data['planTier'] ?? ($currentUser && method_exists($currentUser, 'planTier') ? $currentUser->planTier() : 'free');
        $data['planLabel']      = $data['planLabel'] ?? match ($data['planTier']) {
            'ultra' => 'ULTRA',
            'pro'   => 'PRO',
            default => 'FREE',
        };
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


    protected function buildRedirectResponse(string $path, int $statusCode = 302): Response
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        return Response::redirectResponse($url, $statusCode);
    }

    protected function throwRedirectResponse(string $path, int $statusCode = 302): never
    {
        throw new HttpResponseException($this->buildRedirectResponse($path, $statusCode));
    }

    protected function getPost(string $key, mixed $default = null): mixed
    {
        return $this->request->post($key, $default);
    }

    protected function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->request->query($key, $default);
    }

    protected function getStringQuery(string $key, string $default = ''): string
    {
        return $this->request->queryString($key, $default);
    }

    protected function getIntQuery(string $key, int $default = 0): int
    {
        return $this->request->queryInt($key, $default);
    }

    protected function getBoolQuery(string $key, bool $default = false): bool
    {
        return $this->request->queryBool($key, $default);
    }

    protected function getArrayQuery(string $key, array $default = []): array
    {
        return $this->request->queryArray($key, $default);
    }

    protected function releaseSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    protected function parseYearMonth(string $monthInput): array
    {
        $monthInput = trim($monthInput);
        $date = \DateTimeImmutable::createFromFormat('!Y-m', $monthInput);

        if (!$date || $date->format('Y-m') !== $monthInput) {
            throw new \ValueError('Formato de mês inválido (YYYY-MM).');
        }

        return $this->buildYearMonthPeriod($date);
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    protected function normalizeYearMonth(string $monthInput, ?string $fallbackMonth = null): array
    {
        $monthInput = trim($monthInput);
        $fallbackMonth ??= date('Y-m');

        $date = \DateTimeImmutable::createFromFormat('!Y-m', $monthInput);
        if ($date && $date->format('Y-m') === $monthInput) {
            return $this->buildYearMonthPeriod($date);
        }

        $fallbackDate = \DateTimeImmutable::createFromFormat('!Y-m', $fallbackMonth);
        if ($fallbackDate && $fallbackDate->format('Y-m') === $fallbackMonth) {
            return $this->buildYearMonthPeriod($fallbackDate);
        }

        return $this->buildYearMonthPeriod(new \DateTimeImmutable('first day of this month'));
    }


    protected function getJson(?string $key = null, mixed $default = null): mixed
    {
        $this->ensureValidJsonPayload();

        if ($this->jsonBodyCache === null) {
            $this->jsonBodyCache = $this->request->json() ?? [];
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
            $payload = $this->request->post() ?? [];
        }
        return $payload;
    }

    private function ensureValidJsonPayload(): void
    {
        if (!$this->request->hasJsonError()) {
            return;
        }

        throw new ValidationException([
            'json' => $this->request->jsonError() ?? 'JSON invalido na requisicao.',
        ], 'Validation failed', 400);
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


    protected function ok(array $payload = [], int $status = 200): Response
    {
        $message = $payload['message'] ?? 'Success';
        if (array_key_exists('message', $payload)) {
            unset($payload['message']);
        }

        return Response::successResponse($payload, $message, $status);
    }

    protected function fail(string $message, int $status = 400, mixed $extra = null, ?string $code = null): Response
    {
        return Response::errorResponse($message, $status, $extra, $code);
    }

    protected function failResponse(string $message, int $status = 400, mixed $extra = null, ?string $code = null): Response
    {
        return Response::errorResponse($message, $status, $extra, $code);
    }

    protected function failAndLog(Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = [], ?string $code = null): Response
    {
        $meta = $this->reportExceptionWithReference($e, $userMessage, $extra);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function failAndLogResponse(Throwable $e, string $userMessage = 'Erro interno.', int $status = 500, array $extra = [], ?string $code = null): Response
    {
        $meta = $this->reportExceptionWithReference($e, $userMessage, $extra);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function internalErrorResponse(
        Throwable $e,
        string $userMessage = 'Erro interno do servidor.',
        int $status = 500,
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL,
        ?string $code = null
    ): Response {
        $meta = $this->reportExceptionWithReference($e, $userMessage, $extra, $category);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function internalErrorMessage(
        Throwable $e,
        string $userMessage = 'Erro interno. Tente novamente mais tarde.',
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): string {
        $meta = $this->reportExceptionWithReference($e, $userMessage, $extra, $category);

        return $userMessage . ' (ref: ' . $meta['error_id'] . ')';
    }

    protected function internalErrorMeta(
        Throwable $e,
        string $userMessage = 'Erro interno do servidor.',
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): array {
        $meta = $this->reportExceptionWithReference($e, $userMessage, $extra, $category);

        return [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    protected function workflowFailureResponse(
        array $result,
        string $publicMessage = 'Erro interno do servidor.',
        LogCategory $category = LogCategory::GENERAL,
        array $context = []
    ): Response {
        $status = (int) ($result['status'] ?? 400);
        $message = (string) ($result['message'] ?? $publicMessage);
        $errors = $result['errors'] ?? null;
        $code = isset($result['code']) && is_string($result['code']) ? $result['code'] : null;

        if ($errors === []) {
            $errors = null;
        }

        if ($status < 500) {
            return Response::errorResponse($message, $status, is_array($errors) ? $errors : null, $code);
        }

        if (is_array($errors) && (isset($errors['error_id']) || isset($errors['request_id']))) {
            return Response::errorResponse($publicMessage, $status, $errors, $code);
        }

        $errorId = LogService::reportException(
            e: new \RuntimeException($message),
            publicMessage: $publicMessage,
            context: array_merge($context, [
                'workflow_status' => $status,
            ]),
            userId: $this->userId ?? null,
            category: $category,
        );

        return Response::errorResponse($publicMessage, $status, [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ], $code);
    }

    private function reportExceptionWithReference(
        Throwable $e,
        string $userMessage,
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): array {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $userMessage,
            context: array_merge([
                'url' => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
                'user_id' => $this->userId ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ], $extra),
            userId: $this->userId ?? null,
            category: $category,
        );

        return [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ];
    }

    protected function domainErrorResponse(
        Throwable $e,
        string $fallbackMessage,
        int $status = 400,
        array $extra = [],
        ?string $code = null
    ): Response {
        return $this->failResponse(
            $this->safeThrowableMessage($e, $fallbackMessage),
            $status,
            $extra !== [] ? $extra : null,
            $code
        );
    }

    protected function notFoundFromThrowable(
        Throwable $e,
        string $fallbackMessage = 'Recurso nao encontrado.',
        array $extra = []
    ): Response {
        return $this->domainErrorResponse($e, $fallbackMessage, 404, $extra, 'RESOURCE_NOT_FOUND');
    }

    private function safeThrowableMessage(Throwable $e, string $fallbackMessage): string
    {
        if (
            !$e instanceof ValidationException
            && !$e instanceof AuthException
            && !$e instanceof ClientErrorException
            && !$e instanceof DomainException
            && !$e instanceof InvalidArgumentException
            && !$e instanceof ValueError
        ) {
            return $fallbackMessage;
        }

        $message = trim($e->getMessage());
        if ($message === '' || $this->looksLikeSensitiveThrowableMessage($message)) {
            return $fallbackMessage;
        }

        return $message;
    }

    private function looksLikeSensitiveThrowableMessage(string $message): bool
    {
        $normalized = strtolower($message);
        $markers = [
            'sqlstate',
            'syntax error',
            'stack trace',
            'failed to open stream',
            'uncaught',
            'pdoexception',
            'queryexception',
            'integrity constraint',
            'table ',
            'column ',
            'insert into',
            'update `',
            'delete from',
            'select *',
            ' on line ',
            ' in c:\\',
            ' in /',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
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

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    private function buildYearMonthPeriod(\DateTimeImmutable $date): array
    {
        return [
            'month' => $date->format('Y-m'),
            'year' => (int) $date->format('Y'),
            'monthNum' => (int) $date->format('m'),
            'start' => $date->format('Y-m-01'),
            'end' => $date->format('Y-m-t'),
        ];
    }
}
