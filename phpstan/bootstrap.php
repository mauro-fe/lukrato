<?php

declare(strict_types=1);

defined('APP_NAME') || define('APP_NAME', 'Lukrato');
defined('APP_ENV') || define('APP_ENV', 'testing');
defined('APP_DEBUG') || define('APP_DEBUG', false);
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
defined('BASE_URL') || define('BASE_URL', 'http://localhost/lukrato/');
defined('VIEW_PATH') || define('VIEW_PATH', BASE_PATH . '/views');
defined('STORAGE_PATH') || define('STORAGE_PATH', BASE_PATH . '/storage');
defined('TURNSTILE_SITE_KEY') || define('TURNSTILE_SITE_KEY', '');
defined('TURNSTILE_SECRET_KEY') || define('TURNSTILE_SECRET_KEY', '');
defined('TURNSTILE_THRESHOLD') || define('TURNSTILE_THRESHOLD', 3);
defined('DEV_BYPASS_REGISTRATION_ANTIFRAUD') || define('DEV_BYPASS_REGISTRATION_ANTIFRAUD', false);

defined('DB_DRIVER') || define('DB_DRIVER', 'mysql');
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASSWORD') || define('DB_PASSWORD', '');
defined('DB_NAME') || define('DB_NAME', 'lukrato');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');
