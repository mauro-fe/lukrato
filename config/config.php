<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar ambiente
defineApplicationConstants();
defineDatabaseConstants();
definePathConstants();
configureErrorReporting();
configureDatabaseConnection();

/**
 * Define constantes da aplicação.
 */
function defineApplicationConstants(): void
{
    if (!defined('APP_NAME')) {
        define('APP_NAME', $_ENV['APP_NAME'] ?? 'Lukrato');
    }

    if (!defined('APP_ENV')) {
        define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
    }

    if (!defined('APP_DEBUG')) {
        define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
    }

    if (!defined('BASE_URL')) {
        if (APP_ENV === 'production') {
            define('BASE_URL', rtrim($_ENV['BASE_URL'] ?? 'https://lukrato.com.br', '/'));
        } else {
            define('BASE_URL', rtrim($_ENV['BASE_URL'] ?? 'http://localhost/lukrato', '/'));
        }
    }
}

/**
 * Define constantes do banco de dados.
 */
function defineDatabaseConstants(): void
{
    define('DB_DRIVER', $_ENV['DB_DRIVER'] ?? 'mysql');
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'lukrato');
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
}

/**
 * Define constantes de caminhos.
 */
function definePathConstants(): void
{
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__));
    }

    if (!defined('VIEW_PATH')) {
        define('VIEW_PATH', BASE_PATH . '/views');
    }

    if (!defined('STORAGE_PATH')) {
        define('STORAGE_PATH', BASE_PATH . '/storage');
    }
}

/**
 * Configura error reporting baseado no ambiente.
 */
function configureErrorReporting(): void
{
    if (APP_ENV === 'production') {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }
}

/**
 * Configura a conexão com o banco de dados via Eloquent.
 */
function configureDatabaseConnection(): void
{
    $capsule = new Capsule;

    $config = [
        'driver' => DB_DRIVER,
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'charset' => DB_CHARSET,
        'collation' => DB_CHARSET . '_unicode_ci',
        'prefix' => '',
    ];

    // Opções específicas para MySQL/MariaDB
    if (in_array(DB_DRIVER, ['mysql', 'mariadb'], true)) {
        $config['options'] = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,
        ];
    }

    // Opções para SQLite (útil para testes)
    if (DB_DRIVER === 'sqlite') {
        $config['database'] = DB_NAME === ':memory:' ? ':memory:' : BASE_PATH . '/database/' . DB_NAME;
        unset($config['host'], $config['username'], $config['password'], $config['charset'], $config['collation']);
    }

    $capsule->addConnection($config);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    // Habilitar query log em desenvolvimento
    if (APP_DEBUG && APP_ENV !== 'production') {
        $capsule->connection()->enableQueryLog();
    }
}
