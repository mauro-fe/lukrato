<?php

declare(strict_types=1);

return array (
  0 => 
  array (
    'key' => 'orcamento',
    'label' => 'Orçamentos',
    'title' => 'Orçamentos',
    'icon' => 'piggy-bank',
    'group' => 'Planejamento',
    'route' => 'orcamento',
    'menu' => 'orcamento',
    'view_prefix' => 'admin/orcamento',
    'view_ids' => 
    array (
      0 => 'admin-orcamento-index',
    ),
    'vite_entry' => 'admin/orcamento/index.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'sidebar',
    'order' => 60,
  ),
  1 => 
  array (
    'key' => 'financas',
    'label' => 'Finanças',
    'title' => 'Finanças',
    'icon' => 'wallet',
    'group' => 'Planejamento',
    'route' => 'financas',
    'menu' => 'financas',
    'view_prefix' => 'admin/financas',
    'view_ids' => 
    array (
      0 => 'admin-financas-index',
    ),
    'vite_entry' => 'admin/financas/index.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 71,
  ),
);
