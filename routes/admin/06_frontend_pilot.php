<?php

declare(strict_types=1);

use Application\Core\Router;

// Opt-in frontend separation pilot shell.
Router::add('GET', '/frontend-pilot', 'Admin\\FrontendPilotController@index', ['auth']);
