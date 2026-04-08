<?php

declare(strict_types=1);

// Application/Services/Auth/RateLimitSecurityCheck.php
namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\Infrastructure\CacheService;
use Application\Middlewares\RateLimitMiddleware;

class RateLimitSecurityCheck extends AbstractSecurityCheck
{
    private CacheService $cache;
    private string $prefix;

    public function __construct(Request $request, ?CacheService $cache = null, string $prefix = 'login')
    {
        parent::__construct($request);
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
        $this->prefix = $prefix;
    }

    protected function performCheck(Request $request): void
    {
        $rateLimiter = new RateLimitMiddleware($this->cache);
        $identifier = RateLimitMiddleware::getIdentifier($request);
        $rateLimiter->handle($request, $this->prefix . ':' . $identifier);
    }
}
