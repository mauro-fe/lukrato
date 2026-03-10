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

// Eloquent (para testes que dependem de banco)
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'database'  => $_ENV['DB_NAME'] ?? '',
    'username'  => $_ENV['DB_USER'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
