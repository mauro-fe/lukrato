<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DE AUTENTICAÇÃO
 * ============================================
 * 
 * NOTA: As rotas de login/registro/senha NÃO usam middleware CSRF global
 * porque os controllers já validam CSRF com tokenId específico internamente.
 * Isso evita conflito entre tokenId 'default' (Router) e 'login_form' (Controller)
 */

// Login
Router::add('GET',  '/login',        'Auth\\LoginController@login');
Router::add('POST', '/login/entrar', 'Auth\\LoginController@processLogin', ['ratelimit']);
Router::add('GET',  '/logout',       'Auth\\LoginController@logout');

// Cadastro - CSRF validado internamente no controller
Router::add('POST', '/register/criar', 'Auth\\RegistroController@store', ['ratelimit']);

// Verificação de email
Router::add('GET',  '/verificar-email',          'Auth\\EmailVerificationController@verify');
Router::add('POST', '/verificar-email/reenviar', 'Auth\\EmailVerificationController@resend', ['ratelimit']);
Router::add('GET',  '/verificar-email/aviso',    'Auth\\EmailVerificationController@notice');

// Login com Google
Router::add('GET', '/auth/google/login',        'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/register',     'Auth\\GoogleLoginController@login');
Router::add('GET', '/auth/google/callback',     'Auth\\GoogleCallbackController@callback');
Router::add('GET', '/auth/google/confirm-page', 'Auth\\GoogleCallbackController@confirmPage');
Router::add('GET', '/auth/google/confirm',      'Auth\\GoogleCallbackController@confirm');
Router::add('GET', '/auth/google/cancel',       'Auth\\GoogleCallbackController@cancel');

// Recuperação de senha - CSRF validado internamente no controller
Router::add('GET',  '/recuperar-senha', 'Auth\\ForgotPasswordController@showRequestForm');
Router::add('POST', '/recuperar-senha', 'Auth\\ForgotPasswordController@sendResetLink', ['ratelimit']);
Router::add('GET',  '/resetar-senha',   'Auth\\ForgotPasswordController@showResetForm');
Router::add('POST', '/resetar-senha',   'Auth\\ForgotPasswordController@resetPassword', ['ratelimit']);

// Exclusão de conta
Router::add('POST', '/config/excluir-conta', 'Settings\\AccountController@delete', ['auth', 'csrf']);
