<?php

use Application\Middlewares\AuthMiddleware;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Application\Middlewares\RateLimitStrictMiddleware;
use Application\Middlewares\SysadminMiddleware;
use Application\Middlewares\OnboardingMiddleware;
use Application\Middlewares\RequireFeature;
use Application\Middlewares\AIQuotaMiddleware;

return [
    'auth' => AuthMiddleware::class,
    'csrf' => CsrfMiddleware::class,
    'ratelimit' => RateLimitMiddleware::class,
    'ratelimit_strict' => RateLimitStrictMiddleware::class,
    'sysadmin' => SysadminMiddleware::class,
    'onboarding' => OnboardingMiddleware::class,
    'feature' => RequireFeature::class,
    'ai.quota' => AIQuotaMiddleware::class,
];
