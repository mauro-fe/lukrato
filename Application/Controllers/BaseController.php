<?php

namespace Application\Controllers;

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
}
