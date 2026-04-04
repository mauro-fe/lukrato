<?php

declare(strict_types=1);

return array (
  0 => 
  array (
    'key' => 'gamification',
    'label' => 'Conquistas',
    'title' => 'Gamificação',
    'icon' => 'trophy',
    'group' => 'Extras',
    'route' => 'gamification',
    'menu' => 'gamification',
    'view_prefix' => 'admin/gamification',
    'view_ids' => 
    array (
      0 => 'admin-gamification-index',
    ),
    'vite_entry' => 'admin/gamification/index.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
      0 => 
      array (
        'label' => 'Perfil',
        'icon' => 'user',
      ),
    ),
    'placement' => 'sidebar',
    'order' => 100,
  ),
  1 => 
  array (
    'key' => 'billing',
    'label' => 'Assinatura',
    'title' => 'Assinar Pro',
    'icon' => 'star',
    'group' => 'Extras',
    'route' => 'billing',
    'menu' => NULL,
    'view_prefix' => 'admin/billing',
    'view_ids' => 
    array (
      0 => 'admin-billing-index',
    ),
    'vite_entry' => 'admin/billing/index.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 101,
  ),
);
