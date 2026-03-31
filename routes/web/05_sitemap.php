<?php

declare(strict_types=1);

use Application\Core\Router;

// Dynamic sitemap
Router::add('GET', '/sitemap.xml', 'Site\\SitemapController@index');
