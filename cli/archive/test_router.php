<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Core\Router;

echo "=== TESTANDO ROTAS ===\n\n";

// Carregar rotas
require_once BASE_PATH . '/routes/web.php';

// Simular requisição PUT
$_SERVER['REQUEST_METHOD'] = 'PUT';
$requestedPath = '/lukrato/public/api/v2/contas/24';
$basePath = '/lukrato/public';

// Extrair caminho relativo
$relativePath = str_replace($basePath, '', $requestedPath);
$relativePath = trim($relativePath, '/');

echo "Request Method: PUT\n";
echo "Full Path: $requestedPath\n";
echo "Relative Path: $relativePath\n\n";

// Testar se encontra a rota
$routes = (new ReflectionClass(Router::class))->getStaticPropertyValue('routes');

echo "Total de rotas registradas: " . count($routes) . "\n\n";

echo "Rotas V2 de contas:\n";
foreach ($routes as $route) {
    if (str_contains($route['path'], 'api/v2/contas')) {
        echo "  [{$route['method']}] /{$route['path']}\n";
    }
}

echo "\n=== TESTANDO MATCH ===\n";
$pattern = "#^" . preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', 'api/v2/contas/{id}') . "$#";
echo "Pattern: $pattern\n";
echo "Path to match: $relativePath\n";

if (preg_match($pattern, $relativePath, $matches)) {
    echo "✅ MATCH ENCONTRADO!\n";
    echo "Params: " . json_encode($matches) . "\n";
} else {
    echo "❌ NENHUM MATCH\n";
}
