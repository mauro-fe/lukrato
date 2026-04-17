<?php

declare(strict_types=1);

use Application\Core\Router;

// Authentication
Router::add('POST', '/api/v1/auth/login', 'Auth\\LoginController@processLogin', ['ratelimit']);
Router::add('POST', '/api/v1/auth/logout', 'Auth\\LoginController@logout', ['auth', 'csrf']);
Router::add('POST', '/api/v1/auth/register', 'Auth\\RegistroController@store', ['ratelimit']);

// Social auth
Router::add('GET', '/api/v1/auth/google/login', 'Auth\\GoogleLoginController@login');
Router::add('GET', '/api/v1/auth/google/register', 'Auth\\GoogleLoginController@login');
Router::add('GET', '/api/v1/auth/google/callback', 'Auth\\GoogleCallbackController@callback');
Router::add('GET', '/api/v1/auth/google/pending', 'Auth\\GoogleCallbackController@pending');
Router::add('GET', '/api/v1/auth/google/confirm-page', 'Auth\\GoogleCallbackController@confirmPage');
Router::add('GET', '/api/v1/auth/google/confirm', 'Auth\\GoogleCallbackController@confirm');
Router::add('GET', '/api/v1/auth/google/cancel', 'Auth\\GoogleCallbackController@cancel');

// Email verification
Router::add('GET', '/api/v1/auth/email/verify', 'Auth\\EmailVerificationController@verify');
Router::add('GET', '/api/v1/auth/email/notice', 'Auth\\EmailVerificationController@noticeData');
Router::add('POST', '/api/v1/auth/email/resend', 'Auth\\EmailVerificationController@resend', ['ratelimit']);

// Password reset
Router::add('POST', '/api/v1/auth/password/forgot', 'Auth\\ForgotPasswordController@sendResetLink', ['ratelimit']);
Router::add('GET', '/api/v1/auth/password/reset/validate', 'Auth\\ForgotPasswordController@validateResetLink');
Router::add('POST', '/api/v1/auth/password/reset', 'Auth\\ForgotPasswordController@resetPassword', ['ratelimit']);
