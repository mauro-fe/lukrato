<?php

declare(strict_types=1);

use Application\Core\Router;

// Account deletion
Router::add('POST', '/config/excluir-conta', 'Settings\\AccountController@delete', ['auth', 'csrf', 'ratelimit_strict']);
