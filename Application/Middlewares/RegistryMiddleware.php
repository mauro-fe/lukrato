<?php

use Application\Middlewares\AuthMiddleware;
use Application\Middlewares\CsrfMiddleware;
use Application\Middlewares\RateLimitMiddleware;
use Application\Middlewares\SysadminMiddleware;

return [
    'auth' => AuthMiddleware::class,
    'csrf' => CsrfMiddleware::class,
    'ratelimit' => RateLimitMiddleware::class,
    'sysadmin' => SysadminMiddleware::class // ⬅️ Aqui você registra o middleware sysadmin
];
