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

if (!isset($_ENV['CPF_ENCRYPTION_KEY']) && !getenv('CPF_ENCRYPTION_KEY') && !isset($_ENV['APP_KEY']) && !getenv('APP_KEY')) {
    $_ENV['CPF_ENCRYPTION_KEY'] = 'base64:' . base64_encode(hash('sha256', 'lukrato-test-cpf-key', true));
}

// Timezone
date_default_timezone_set($_ENV['APP_TZ'] ?? 'America/Sao_Paulo');

// BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/lukrato/');
}

// Storage isolado e gravável para testes
$testStoragePath = sys_get_temp_dir() . '/lukrato-test-storage';
if (!is_dir($testStoragePath)) {
    mkdir($testStoragePath, 0755, true);
}
if (!is_dir($testStoragePath . '/cache')) {
    mkdir($testStoragePath . '/cache', 0755, true);
}
$_ENV['STORAGE_PATH'] = $testStoragePath;
$_ENV['REDIS_ENABLED'] = 'false';

$testRuntimePath = BASE_PATH . '/tests/.runtime';
$testSessionPath = $testRuntimePath . '/sessions';
if (!is_dir($testSessionPath)) {
    mkdir($testSessionPath, 0755, true);
}
ini_set('session.save_path', $testSessionPath);

// Eloquent (para testes que dependem de banco)
use Application\Lib\Auth;
use Application\Models\Usuario;
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

Auth::setDefaultUserResolver(static function (int $userId): ?Usuario {
    $cached = $_SESSION['usuario_cache'] ?? null;

    if (($cached['id'] ?? null) === $userId && (($cached['data'] ?? null) instanceof Usuario)) {
        return $cached['data'];
    }

    return Usuario::find($userId);
});
