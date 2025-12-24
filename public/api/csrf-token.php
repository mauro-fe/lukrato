<?php

require_once __DIR__ . '/../../bootstrap.php';

use Application\Middlewares\CsrfMiddleware;

session_start();

header('Content-Type: application/json');

// Gerar novo token
$token = CsrfMiddleware::generateToken('default');

echo json_encode([
    'token' => $token,
    'expires_in' => 1200
]);
