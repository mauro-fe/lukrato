<?php

declare(strict_types=1);

use Application\Core\Response;
use Application\Core\Router;

// Legacy redirects /aprenda -> /blog
Router::add('GET', '/aprenda', function () {
    return Response::redirectResponse(rtrim(BASE_URL, '/') . '/blog', 301);
});

Router::add('GET', '/aprenda/categoria/{slug}', function ($slug) {
    return Response::redirectResponse(rtrim(BASE_URL, '/') . '/blog/categoria/' . $slug, 301);
});

Router::add('GET', '/aprenda/{slug}', function ($slug) {
    return Response::redirectResponse(rtrim(BASE_URL, '/') . '/blog/' . $slug, 301);
});
