<?php

// Simular uma requisição POST com X-HTTP-Method-Override
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/lukrato/public/api/v2/contas/24';

require_once __DIR__ . '/../bootstrap.php';

use Application\Bootstrap\RequestHandler;

$handler = new RequestHandler();

echo "=== TESTE METHOD OVERRIDE ===\n\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "HTTP_X_HTTP_METHOD_OVERRIDE: " . ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? 'não definido') . "\n";
echo "Método detectado: " . $handler->getMethod() . "\n";
echo "Rota: " . $handler->parseRoute() . "\n";
