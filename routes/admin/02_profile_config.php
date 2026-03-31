<?php

declare(strict_types=1);

use Application\Core\Router;

// Profile and settings
Router::add('GET', '/perfil', 'Admin\\PerfilController@index', ['auth']);
Router::add('GET', '/configuracoes', 'Admin\\ConfigController@index', ['auth']);
Router::add('GET', '/config', 'Admin\\ConfigController@index', ['auth']);
