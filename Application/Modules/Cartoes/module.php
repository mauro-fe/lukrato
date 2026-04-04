<?php

declare(strict_types=1);

return array(
  0 =>
  array(
    'key' => 'cartoes',
    'label' => 'Cartões',
    'title' => 'Cartões de Crédito',
    'icon' => 'credit-card',
    'group' => 'Finanças',
    'route' => 'cartoes',
    'menu' => 'cartoes',
    'view_prefix' => 'admin/cartoes',
    'view_ids' =>
    array(
      0 => 'admin-cartoes-index',
    ),
    'vite_entry' => 'admin/cartoes/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array(
      0 =>
      array(
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
    ),
    'placement' => 'sidebar',
    'order' => 40,
  ),
  1 =>
  array(
    'key' => 'cartoes_arquivados',
    'label' => 'Cartões Arquivados',
    'title' => 'Cartões Arquivados',
    'icon' => 'archive',
    'group' => 'Finanças',
    'route' => 'cartoes/arquivadas',
    'menu' => 'cartoes',
    'view_prefix' => 'admin/cartoes/arquivadas',
    'view_ids' =>
    array(
      0 => 'admin-cartoes-arquivadas',
    ),
    'vite_entry' => 'admin/cartoes-arquivadas/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array(
      0 =>
      array(
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 41,
  ),
);
