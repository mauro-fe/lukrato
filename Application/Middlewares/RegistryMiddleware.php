<?php

use Application\Middlewares\AuthMiddleware;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Application\Middlewares\RateLimitStrictMiddleware;
use Application\Middlewares\SysadminMiddleware;
use Application\Middlewares\RequireFeature;
use Application\Middlewares\AIQuotaMiddleware;
use Application\Middlewares\AIRateLimitMiddleware;

return [
    'auth' => AuthMiddleware::class,
    'csrf' => CsrfMiddleware::class,
    'ratelimit' => RateLimitMiddleware::class,
    'ratelimit_strict' => RateLimitStrictMiddleware::class,
    'ai.ratelimit' => AIRateLimitMiddleware::class,
    'sysadmin' => SysadminMiddleware::class,
    'feature' => RequireFeature::class,
    'ai.quota' => AIQuotaMiddleware::class,
];
