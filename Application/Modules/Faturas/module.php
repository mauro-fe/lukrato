<?php

declare(strict_types=1);

return array (
  0 =>
  array (
    'key' => 'faturas',
    'label' => 'Faturas',
    'title' => 'Faturas de Cartão',
    'icon' => 'receipt',
    'group' => 'Finanças',
    'route' => 'faturas',
    'menu' => 'faturas',
    'view_prefix' => 'admin/faturas',
    'view_ids' =>
    array (
      0 => 'admin-faturas-index',
      1 => 'admin-faturas-show',
    ),
    'vite_entry' => 'admin/faturas/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array (
      0 =>
      array (
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
      1 =>
      array (
        'label' => 'Cartões',
        'url' => 'cartoes',
        'icon' => 'credit-card',
      ),
    ),
    'placement' => 'sidebar',
    'order' => 50,
  ),
  1 =>
  array (
    'key' => 'parcelamentos',
    'label' => 'Parcelamentos',
    'title' => 'Parcelamentos',
    'icon' => 'receipt',
    'group' => 'Finanças',
    'route' => 'faturas',
    'menu' => 'faturas',
    'view_prefix' => 'admin/parcelamentos',
    'view_ids' =>
    array (
      0 => 'admin-parcelamentos-index',
    ),
    'vite_entry' => 'admin/faturas/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array (
      0 =>
      array (
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
      1 =>
      array (
        'label' => 'Cartões',
        'url' => 'cartoes',
        'icon' => 'credit-card',
      ),
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 51,
  ),
);
