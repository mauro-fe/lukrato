<?php

declare(strict_types=1);

function phpstan_env_string(string $name, string $default = ''): string
{
    $value = getenv($name);

    return is_string($value) && $value !== '' ? $value : $default;
}

function phpstan_env_bool(string $name, bool $default = false): bool
{
    $value = getenv($name);

    return is_string($value) ? filter_var($value, FILTER_VALIDATE_BOOL) : $default;
}

function phpstan_env_int(string $name, int $default): int
{
    $value = getenv($name);

    return is_string($value) && is_numeric($value) ? (int) $value : $default;
}

defined('APP_NAME') || define('APP_NAME', phpstan_env_string('APP_NAME', 'Lukrato'));
defined('APP_ENV') || define('APP_ENV', phpstan_env_string('APP_ENV', 'testing'));
defined('APP_DEBUG') || define('APP_DEBUG', phpstan_env_bool('APP_DEBUG'));
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
defined('BASE_URL') || define('BASE_URL', phpstan_env_string('BASE_URL', 'http://localhost/lukrato/'));
defined('VIEW_PATH') || define('VIEW_PATH', BASE_PATH . '/views');
defined('STORAGE_PATH') || define('STORAGE_PATH', BASE_PATH . '/storage');
defined('TURNSTILE_SITE_KEY') || define('TURNSTILE_SITE_KEY', phpstan_env_string('TURNSTILE_SITE_KEY'));
defined('TURNSTILE_SECRET_KEY') || define('TURNSTILE_SECRET_KEY', phpstan_env_string('TURNSTILE_SECRET_KEY'));
defined('TURNSTILE_THRESHOLD') || define('TURNSTILE_THRESHOLD', phpstan_env_int('TURNSTILE_THRESHOLD', 3));
defined('DEV_BYPASS_REGISTRATION_ANTIFRAUD') || define('DEV_BYPASS_REGISTRATION_ANTIFRAUD', phpstan_env_bool('DEV_BYPASS_REGISTRATION_ANTIFRAUD'));

defined('DB_DRIVER') || define('DB_DRIVER', phpstan_env_string('DB_DRIVER', 'mysql'));
defined('DB_HOST') || define('DB_HOST', phpstan_env_string('DB_HOST', 'localhost'));
defined('DB_USER') || define('DB_USER', phpstan_env_string('DB_USER', 'root'));
defined('DB_PASSWORD') || define('DB_PASSWORD', phpstan_env_string('DB_PASSWORD'));
defined('DB_NAME') || define('DB_NAME', phpstan_env_string('DB_NAME', 'lukrato'));
defined('DB_CHARSET') || define('DB_CHARSET', phpstan_env_string('DB_CHARSET', 'utf8mb4'));

defined('LARAVEL_VERSION') || define('LARAVEL_VERSION', '11.51.0');
defined('Larastan\\Larastan\\LARAVEL_VERSION') || define('Larastan\\Larastan\\LARAVEL_VERSION', LARAVEL_VERSION);

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim(BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/\\') : ''), '/\\');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('Application' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return base_path('database' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}
