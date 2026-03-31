<?php

declare(strict_types=1);

use Application\Core\Router;

// Email verification
Router::add('GET',  '/verificar-email', 'Auth\\EmailVerificationController@verify');
Router::add('POST', '/verificar-email/reenviar', 'Auth\\EmailVerificationController@resend', ['ratelimit']);
Router::add('GET',  '/verificar-email/aviso', 'Auth\\EmailVerificationController@notice');

// Google auth
Router::add('GET', '/auth/google/login', 'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/register', 'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/callback', 'Auth\\GoogleCallbackController@callback');
Router::add('GET', '/auth/google/confirm-page', 'Auth\\GoogleCallbackController@confirmPage');
Router::add('GET', '/auth/google/confirm', 'Auth\\GoogleCallbackController@confirm');
Router::add('GET', '/auth/google/cancel', 'Auth\\GoogleCallbackController@cancel');
