<?php

declare(strict_types=1);

use Application\Core\Router;

// Landing
Router::add('GET', '/', 'Site\\LandingController@index');
Router::add('GET', '/funcionalidades', 'Site\\LandingController@index');
Router::add('GET', '/beneficios', 'Site\\LandingController@index');
Router::add('GET', '/planos', 'Site\\LandingController@index');
Router::add('GET', '/contato', 'Site\\LandingController@index');
