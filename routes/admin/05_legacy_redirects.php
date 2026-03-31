<?php

declare(strict_types=1);

use Application\Core\Response;
use Application\Core\Router;

// Legacy redirects
Router::add('GET', '/admin', function () {
    return Response::redirectResponse(BASE_URL . 'login');
});

Router::add('GET', '/admin/login', function () {
    return Response::redirectResponse(BASE_URL . 'login');
});

Router::add('GET', '/admin/dashboard', function () {
    return Response::redirectResponse(BASE_URL . 'dashboard');
});

Router::add('GET', '/admin/home', function () {
    if (isset($_SESSION['user_id'])) {
        return Response::redirectResponse(BASE_URL . 'dashboard');
    }

    session_destroy();

    return Response::redirectResponse(BASE_URL . 'login');
});
