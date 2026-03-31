<?php

declare(strict_types=1);

use Application\Core\Router;

// Password reset
Router::add('GET',  '/recuperar-senha', 'Auth\\ForgotPasswordController@showRequestForm');
Router::add('POST', '/recuperar-senha', 'Auth\\ForgotPasswordController@sendResetLink', ['ratelimit']);
Router::add('GET',  '/resetar-senha', 'Auth\\ForgotPasswordController@showResetForm');
Router::add('POST', '/resetar-senha', 'Auth\\ForgotPasswordController@resetPassword', ['ratelimit']);
