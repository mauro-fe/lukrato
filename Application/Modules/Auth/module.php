<?php

declare(strict_types=1);

return array (
  0 => 
  array (
    'key' => 'auth_login',
    'label' => 'Login',
    'title' => 'Login',
    'icon' => 'log-in',
    'group' => '',
    'route' => 'login',
    'menu' => NULL,
    'view_prefix' => 'admin/auth/login',
    'view_ids' => 
    array (
      0 => 'admin-auth-login',
    ),
    'vite_entry' => 'admin/auth/login/index.js',
    'css_entry' => 'auth-login-style',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'infer_menu' => false,
    'order' => 200,
  ),
  1 => 
  array (
    'key' => 'auth_forgot_password',
    'label' => 'Esqueci a Senha',
    'title' => 'Esqueci a Senha',
    'icon' => 'key-round',
    'group' => '',
    'route' => 'forgot-password',
    'menu' => NULL,
    'view_prefix' => 'admin/auth/forgot-password',
    'view_ids' => 
    array (
      0 => 'admin-auth-forgot-password',
    ),
    'vite_entry' => 'admin/auth/forgot-password/index.js',
    'css_entry' => 'auth-shared-style',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'infer_menu' => false,
    'order' => 201,
  ),
  2 => 
  array (
    'key' => 'auth_reset_password',
    'label' => 'Redefinir Senha',
    'title' => 'Redefinir Senha',
    'icon' => 'key-round',
    'group' => '',
    'route' => 'reset-password',
    'menu' => NULL,
    'view_prefix' => 'admin/auth/reset-password',
    'view_ids' => 
    array (
      0 => 'admin-auth-reset-password',
    ),
    'vite_entry' => 'admin/auth/reset-password/index.js',
    'css_entry' => 'auth-shared-style',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'infer_menu' => false,
    'order' => 202,
  ),
  3 => 
  array (
    'key' => 'auth_verify_email',
    'label' => 'Verificar Email',
    'title' => 'Verificar Email',
    'icon' => 'mail-check',
    'group' => '',
    'route' => 'email/verify',
    'menu' => NULL,
    'view_prefix' => 'admin/auth/verify-email',
    'view_ids' => 
    array (
      0 => 'admin-auth-verify-email',
    ),
    'vite_entry' => 'admin/auth/verify-email/index.js',
    'css_entry' => 'auth-verify-email-style',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'infer_menu' => false,
    'order' => 203,
  ),
);
