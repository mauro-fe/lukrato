<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_only_cookies', '1');

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', realpath(__DIR__));

$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Erro: Execute "composer install" para instalar as dependências.');
}
require_once $autoloadPath;

if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

$app = new Application\Bootstrap\Application();
$app->run();
