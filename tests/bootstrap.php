<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Composer autoload
require BASE_PATH . '/vendor/autoload.php';

// Carregar .env.testing se existir, senão .env
$envFile = file_exists(BASE_PATH . '/.env.testing') ? '.env.testing' : '.env';
if (file_exists(BASE_PATH . '/' . $envFile)) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(BASE_PATH, $envFile);
    $dotenv->safeLoad();
}

// Timezone
date_default_timezone_set($_ENV['APP_TZ'] ?? 'America/Sao_Paulo');

// BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/lukrato/');
}
