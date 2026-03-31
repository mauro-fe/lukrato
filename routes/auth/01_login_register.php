<?php

declare(strict_types=1);

use Application\Core\Router;

// Login/logout
Router::add('GET',  '/login', 'Auth\\LoginController@login');
Router::add('POST', '/login/entrar', 'Auth\\LoginController@processLogin', ['ratelimit']);
Router::add('GET',  '/logout', 'Auth\\LoginController@logout');

// Registration
Router::add('POST', '/register/criar', 'Auth\\RegistroController@store', ['ratelimit']);
