<?php

declare(strict_types=1);

/**
 * Admin routes split by concern for easier navigation.
 * Keep load order stable to preserve route matching behavior.
 */
$adminRouteFiles = [
    __DIR__ . '/admin/01_main_pages.php',
    __DIR__ . '/admin/02_profile_config.php',
    __DIR__ . '/admin/03_finance_billing.php',
    __DIR__ . '/admin/04_sysadmin_views.php',
    __DIR__ . '/admin/05_legacy_redirects.php',
    __DIR__ . '/admin/06_frontend_pilot.php',
];

foreach ($adminRouteFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        throw new \RuntimeException("Admin route file not found: {$routeFile}");
    }

    require_once $routeFile;
}
