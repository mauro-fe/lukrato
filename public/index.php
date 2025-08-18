<?php

/**
 * Front Controller - Ponto de entrada da aplicação
 *
 * Este arquivo gerencia todas as requisições HTTP e as direciona
 * para os controladores apropriados através do sistema de rotas.
 */

// ============================================================================
// CONFIGURAÇÕES INICIAIS
// ============================================================================

// Timezone padrão
date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_only_cookies', 1);

// Definição de constantes
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', realpath(__DIR__));

// ============================================================================
// CARREGAMENTO DE DEPENDÊNCIAS
// ============================================================================

// Autoloader do Composer
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Erro: Execute "composer install" para instalar as dependências.');
}
require_once $autoloadPath;

// Variáveis de ambiente
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// ============================================================================
// CONFIGURAÇÃO DE AMBIENTE
// ============================================================================

$environment = $_ENV['APP_ENV'] ?? 'production';

if ($environment === 'development') {
    configureDevelopmentEnvironment();
} else {
    configureProductionEnvironment();
}

// ============================================================================
// CONFIGURAÇÃO DE SESSÃO E SEGURANÇA
// ============================================================================

configureSession();
configureSecurityHeaders();

// ============================================================================
// CARREGAMENTO DE ARQUIVOS DE CONFIGURAÇÃO
// ============================================================================

loadConfigurationFiles();

// ============================================================================
// PROCESSAMENTO DE REQUISIÇÕES
// ============================================================================

processRequest($environment);

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Configura o ambiente de desenvolvimento
 */
function configureDevelopmentEnvironment(): void
{
    ini_set('display_errors', 0); // ← importante desativar para capturar e formatar corretamente
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED);

    set_error_handler(function ($severity, $message, $file, $line) {
        $isAjax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Erro: $message",
                'file' => $file,
                'line' => $line
            ]);
            exit;
        } else {
            echo "<b>Erro:</b> $message<br><small>$file:$line</small>";
        }

        return true;
    });

    set_exception_handler(function (\Throwable $e) {
        $isAjax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Exceção não tratada',
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            exit;
        } else {
            echo "<h2>Exceção não tratada:</h2>";
            echo "<p><b>{$e->getMessage()}</b></p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
        }
    });
}


/**
 * Configura o ambiente de produção
 */
function configureProductionEnvironment(): void
{
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    // Handler de erros customizado
    set_error_handler(function ($severity, $message, $file, $line) {
        error_log("Error: [$severity] $message in $file on line $line");

        if (in_array($severity, [E_ERROR, E_USER_ERROR, E_PARSE])) {
            http_response_code(500);
            include BASE_PATH . '/views/errors/500.php';
            exit;
        }

        return false;
    });

    // Handler de exceções não tratadas
    set_exception_handler(function (\Throwable $e) {
        error_log(
            "Unhandled Exception: " . $e->getMessage() .
                " in " . $e->getFile() .
                " on line " . $e->getLine() .
                "\n" . $e->getTraceAsString()
        );

        // Detecta se é requisição AJAX (ou Content-Type: application/json)
        $isAjax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) &&
            stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        );

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');

            // Garante que nenhum HTML será exibido
            echo json_encode([
                'status' => 'error',
                'message' => 'Erro inesperado no servidor.',
                'details' => $e->getMessage() // se quiser ocultar essa linha em produção, remova
            ]);
        } else {
            http_response_code(500);
            $errorPage = BASE_PATH . '/views/errors/500.php';
            if (file_exists($errorPage)) {
                include $errorPage;
            } else {
                echo 'Ocorreu um erro interno. Por favor, tente novamente mais tarde.';
            }
        }

        exit;
    });
}


/**
 * Configura a sessão com parâmetros seguros
 */
function configureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain = $_SERVER['HTTP_HOST'] ?? '';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/**
 * Configura headers de segurança
 */
function configureSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    // header('Referrer-Policy: strict-origin-when-cross-origin');

    // CSP comentado - descomentar e ajustar conforme necessário
    /*
    header("Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://demos.creative-tim.com https://kit.fontawesome.com https://code.jquery.com https://cdn.datatables.net https://buttons.github.io; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://demos.creative-tim.com https://cdn.datatables.net; " .
        "img-src 'self' data:; " .
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://demos.creative-tim.com https://kit.fontawesome.com; " .
        "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://kit.fontawesome.com;");
    */
}

/**
 * Carrega arquivos de configuração necessários
 */
function loadConfigurationFiles(): void
{
    $configPath = BASE_PATH . '/config/config.php';
    if (!file_exists($configPath)) {
        die('Erro: Arquivo de configuração não encontrado.');
    }
    require_once $configPath;

    $routesPath = BASE_PATH . '/routes/web.php';
    if (!file_exists($routesPath)) {
        die('Erro: Arquivo de rotas não encontrado.');
    }
    require_once $routesPath;
}

use Application\Core\Router;


/**
 * Processa a requisição HTTP atual
 */
function processRequest(string $environment): void
{

    try {
        $route = parseRequestRoute();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        Router::run($route, $method);
    } catch (\Throwable $e) {
        handleRequestError($e, $environment);
    }
}

/**
 * Analisa e normaliza a rota da requisição
 */
function parseRequestRoute(): string
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    // Remove o caminho base da URI
    $basePath = str_replace('/index.php', '', dirname($scriptName));
    $basePath = rtrim($basePath, '/');

    if ($basePath === '' || $basePath === '.') {
        $basePath = '';
    }

    if ($basePath && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    // Extrai apenas o path da URL
    $parsedUrl = parse_url($requestUri);
    $route = $parsedUrl['path'] ?? '/';

    // Normaliza a rota
    $route = '/' . trim($route, '/');
    if ($route === '//') {
        $route = '/';
    }

    return $route;
}

/**
 * Trata erros durante o processamento da requisição
 */
function handleRequestError(\Throwable $e, string $environment): void
{
    if ($environment === 'development') {
        echo '<h1>Erro na requisição:</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<h2>Stack Trace:</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        error_log(
            "Request Error: " . $e->getMessage() .
                " in " . $e->getFile() .
                " on line " . $e->getLine()
        );

        http_response_code(500);

        $errorPage = BASE_PATH . '/views/errors/500.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo 'Ocorreu um erro interno. Por favor, tente novamente mais tarde.';
        }
    }
}