<?php

declare(strict_types=1);

/**
 * Public web routes split by concern for easier navigation.
 * Keep load order stable to preserve route matching behavior.
 */
$webRouteFiles = [
    __DIR__ . '/web/01_landing.php',
    __DIR__ . '/web/02_card_legal.php',
    __DIR__ . '/web/03_blog.php',
    __DIR__ . '/web/04_legacy_redirects.php',
    __DIR__ . '/web/05_sitemap.php',
];

foreach ($webRouteFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        throw new \RuntimeException("Web route file not found: {$routeFile}");
    }

    require_once $routeFile;
}
