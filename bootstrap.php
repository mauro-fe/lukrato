<?php
// C:\xampp\htdocs\lukrato\bootstrap.php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

// Composer
require BASE_PATH . '/vendor/autoload.php';

// Dotenv - usar createUnsafeImmutable para popular $_ENV, $_SERVER e putenv()
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(BASE_PATH);
    $dotenv->safeLoad(); // não explode se faltar algo
}

// Timezone padrão
date_default_timezone_set($_ENV['APP_TZ'] ?? 'America/Sao_Paulo');

// BASE_URL (CLI não tem SERVER vars)
if (!defined('BASE_URL')) {
    $baseFromEnv = $_ENV['BASE_URL'] ?? $_ENV['APP_URL'] ?? 'http://localhost/lukrato/';
    define('BASE_URL', rtrim($baseFromEnv, '/') . '/');
}

// Eloquent
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
