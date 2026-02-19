<?php

use Application\Middlewares\AuthMiddleware;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Application\Middlewares\SysadminMiddleware;
use Application\Middlewares\OnboardingMiddleware;

return [
    'auth' => AuthMiddleware::class,
    'csrf' => CsrfMiddleware::class,
    'ratelimit' => RateLimitMiddleware::class,
    'sysadmin' => SysadminMiddleware::class,
    'onboarding' => OnboardingMiddleware::class,
];
