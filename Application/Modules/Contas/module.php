<?php

declare(strict_types=1);

return array (
  0 =>
  array (
    'key' => 'contas',
    'label' => 'Contas',
    'title' => 'Contas',
    'icon' => 'landmark',
    'group' => 'Finanças',
    'route' => 'contas',
    'menu' => 'contas',
    'view_prefix' => 'admin/contas',
    'view_ids' =>
    array (
      0 => 'admin-contas-index',
    ),
    'vite_entry' => 'admin/contas/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array (
      0 =>
      array (
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
    ),
    'placement' => 'sidebar',
    'order' => 30,
  ),
  1 =>
  array (
    'key' => 'contas_arquivadas',
    'label' => 'Contas Arquivadas',
    'title' => 'Contas Arquivadas',
    'icon' => 'archive',
    'group' => 'Finanças',
    'route' => 'contas/arquivadas',
    'menu' => 'contas',
    'view_prefix' => 'admin/contas/arquivadas',
    'view_ids' =>
    array (
      0 => 'admin-contas-arquivadas',
    ),
    'vite_entry' => 'admin/contas-arquivadas/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array (
      0 =>
      array (
        'label' => 'Finanças',
        'icon' => 'wallet',
      ),
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 31,
  ),
);
