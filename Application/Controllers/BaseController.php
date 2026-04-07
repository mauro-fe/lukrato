<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Container\ApplicationContainer;
use Application\Controllers\Concerns\HandlesAdminLayoutData;
use Application\Controllers\Concerns\HandlesApiResponses;
use Application\Controllers\Concerns\HandlesAuthGuards;
use Application\Controllers\Concerns\HandlesRequestUtilities;
use Application\Controllers\Concerns\HandlesWebPresentation;
use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Infrastructure\CacheService;

abstract class BaseController
{
    use HandlesAdminLayoutData;
    use HandlesApiResponses;
    use HandlesAuthGuards;
    use HandlesRequestUtilities;
    use HandlesWebPresentation;

    protected ?int $userId = null;
    protected ?string $adminUsername = null;
    protected readonly Auth $auth;
    protected readonly Request $request;
    protected readonly Response $response;
    protected ?CacheService $cache;

    public function __construct(
        ?Auth $auth = null,
        ?Request $request = null,
        ?Response $response = null,
        ?CacheService $cache = null
    ) {
        $this->auth = $auth ?? $this->resolveDependency(Auth::class) ?? new Auth();
        $this->request = $request ?? $this->resolveDependency(Request::class) ?? new Request();
        $this->response = $response ?? $this->resolveDependency(Response::class) ?? new Response();
        $this->cache = $cache ?? $this->resolveDependency(CacheService::class) ?? new CacheService();
    }

    protected function resolveDependency(string $abstract): mixed
    {
        return ApplicationContainer::tryMake($abstract);
    }

    protected function resolveOrCreate(mixed $dependency, string $abstract, ?callable $factory = null): mixed
    {
        if ($dependency !== null) {
            return $dependency;
        }

        return $this->resolveDependency($abstract) ?? ($factory ? $factory() : new $abstract());
    }
}
