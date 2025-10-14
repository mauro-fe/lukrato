<?php

use Illuminate\Database\Capsule\Manager as Capsule;


defineApplicationConstants();
defineDatabaseConstants();
definePathConstants();
configureDatabaseConnection();


function defineApplicationConstants(): void
{
    if (!defined('APP_NAME')) {
        define('APP_NAME', $_ENV['APP_NAME'] ?? 'Lukrato');
    }

    if (!defined('BASE_URL')) {
        define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/lukrato/public/');
    }
}

function defineDatabaseConstants(): void
{
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'lukrato');
}

function definePathConstants(): void
{
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__));
    }

    if (!defined('VIEW_PATH')) {
        define('VIEW_PATH', BASE_PATH . '/views');
    }
}

function configureDatabaseConnection(): void
{
    $capsule = new Capsule;

    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => DB_HOST,
        'database'  => DB_NAME,
        'username'  => DB_USER,
        'password'  => DB_PASSWORD,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}
