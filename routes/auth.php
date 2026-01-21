<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DE AUTENTICAÇÃO
 * ============================================
 */

// Login
Router::add('GET',  '/login',        'Auth\\LoginController@login');
Router::add('POST', '/login/entrar', 'Auth\\LoginController@processLogin');
Router::add('GET',  '/logout',       'Auth\\LoginController@logout');

// Cadastro
Router::add('POST', '/register/criar', 'Auth\\RegistroController@store');

// Login com Google
Router::add('GET', '/auth/google/login',        'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/register',     'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/callback',     'Auth\\GoogleCallbackController@callback');
Router::add('GET', '/auth/google/confirm-page', 'Auth\\GoogleCallbackController@confirmPage');
Router::add('GET', '/auth/google/confirm',      'Auth\\GoogleCallbackController@confirm');
Router::add('GET', '/auth/google/cancel',       'Auth\\GoogleCallbackController@cancel');

// Recuperação de senha
Router::add('GET',  '/recuperar-senha', 'Auth\\ForgotPasswordController@showRequestForm');
Router::add('POST', '/recuperar-senha', 'Auth\\ForgotPasswordController@sendResetLink');
Router::add('GET',  '/resetar-senha',   'Auth\\ForgotPasswordController@showResetForm');
Router::add('POST', '/resetar-senha',   'Auth\\ForgotPasswordController@resetPassword');

// Exclusão de conta
Router::add('POST', '/config/excluir-conta', 'Settings\\AccountController@delete', ['auth', 'csrf']);
