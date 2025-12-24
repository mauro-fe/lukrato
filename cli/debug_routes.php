<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Simular servidor
$_SERVER['REQUEST_URI'] = '/lukrato/public/api/contas';
$_SERVER['SCRIPT_NAME'] = '/lukrato/public/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "=== DEBUG DE REQUISIÇÃO ===\n\n";

echo "REQUEST_URI: {$_SERVER['REQUEST_URI']}\n";
echo "SCRIPT_NAME: {$_SERVER['SCRIPT_NAME']}\n\n";

// Testar processamento
$handler = new Application\Bootstrap\RequestHandler();
$route = $handler->parseRoute();

echo "Rota processada: $route\n\n";

// Verificar basePath
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = str_replace('/index.php', '', dirname($scriptName));
$basePath = rtrim($basePath, '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

echo "BasePath calculado: '$basePath'\n\n";

// Simular remoção do basePath
$requestUri = $_SERVER['REQUEST_URI'];
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $afterBase = substr($requestUri, strlen($basePath));
    echo "Após remover basePath: '$afterBase'\n";
}

echo "\n=== FIM DO DEBUG ===\n";
