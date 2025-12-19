<?php


use Application\Core\Router;

/**
 * ============================================
 * ROTAS PÚBLICAS (LANDING PAGE)
 * ============================================
 */

// Landing principal
Router::add('GET', '/', 'Site\\LandingController@index');

// Seções da landing
Router::add('GET', '/funcionalidades', 'Site\\LandingController@index');
Router::add('GET', '/beneficios',      'Site\\LandingController@index');
Router::add('GET', '/planos',          'Site\\LandingController@index');
Router::add('GET', '/contato',         'Site\\LandingController@index');

// Páginas legais
Router::add('GET', '/termos',      'Site\\LegalController@terms');
Router::add('GET', '/privacidade', 'Site\\LegalController@privacy');
Router::add('GET', '/lgpd',        'Site\\LegalController@lgpd');
