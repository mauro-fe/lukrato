<?php

declare(strict_types=1);

/**
 * Auth routes split by concern to keep discovery simple.
 * Keep load order stable to preserve route matching behavior.
 */
$authRouteFiles = [
    __DIR__ . '/auth/01_login_register.php',
    __DIR__ . '/auth/02_email_google.php',
    __DIR__ . '/auth/03_password_reset.php',
    __DIR__ . '/auth/04_account_delete.php',
];

foreach ($authRouteFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        throw new \RuntimeException("Auth route file not found: {$routeFile}");
    }

    require_once $routeFile;
}
