<?php

declare(strict_types=1);

use Application\Core\Router;

// Blog/Aprenda
Router::add('GET', '/blog', 'Site\\AprendaController@index');
Router::add('GET', '/blog/categoria/{slug}', 'Site\\AprendaController@categoria');
Router::add('GET', '/blog/{slug}', 'Site\\AprendaController@show');
