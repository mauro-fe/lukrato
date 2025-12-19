<?php

declare(strict_types=1);

// Configurações iniciais
date_default_timezone_set('America/Sao_Paulo');

// Definir caminhos
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', realpath(__DIR__));

// Verificar autoload do Composer
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    die('Erro: Execute "composer install" para instalar as dependências.');
}
require_once $autoloadPath;

// Carregar variáveis de ambiente
$envPath = BASE_PATH . '/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    die('Erro: Arquivo .env não encontrado. Copie .env.example para .env e configure.');
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
    
    // Validar variáveis obrigatórias em produção
    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env === 'production') {
        $dotenv->required(['APP_NAME', 'DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Erro ao carregar configurações: ' . $e->getMessage());
}

// Configurações de sessão
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $env === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

// Inicializar aplicação
try {
    $app = new Application\Bootstrap\Application();
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    
    // Em desenvolvimento, mostrar erro detalhado
    if (($_ENV['APP_DEBUG'] ?? false) && $env !== 'production') {
        echo '<h1>Erro na Aplicação</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Em produção, mensagem genérica
        echo 'Erro interno do servidor. Por favor, tente novamente mais tarde.';
        
        // Log do erro
        error_log('Application Error: ' . $e->getMessage());
    }
}
