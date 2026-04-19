<?php

declare(strict_types=1);

return array(
  0 =>
  array(
    'key' => 'gamification',
    'label' => 'Conquistas',
    'title' => 'Gamificação',
    'icon' => 'trophy',
    'group' => 'Extras',
    'route' => 'gamification',
    'menu' => 'gamification',
    'view_prefix' => 'admin/gamification',
    'view_ids' =>
    array(
      0 => 'admin-gamification-index',
    ),
    'vite_entry' => 'admin/gamification/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array(
      0 =>
      array(
        'label' => 'Perfil',
        'icon' => 'user',
      ),
    ),
    'placement' => 'sidebar',
    'order' => 100,
  ),
  1 =>
  array(
    'key' => 'billing',
    'label' => 'Assinatura',
    'title' => 'Assinar Pro',
    'icon' => 'star',
    'group' => 'Extras',
    'route' => 'billing',
    'menu' => 'billing',
    'view_prefix' => 'admin/billing',
    'view_ids' =>
    array(
      0 => 'admin-billing-index',
    ),
    'vite_entry' => 'admin/billing/index.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array(
      0 =>
      array(
        'label' => 'Assinatura',
        'icon' => 'star',
      ),
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 101,
  ),
  2 =>
  array(
    'key' => 'billing_checkout',
    'label' => 'Checkout',
    'title' => 'Pagamento Seguro',
    'icon' => 'credit-card',
    'group' => 'Extras',
    'route' => 'billing/checkout',
    'menu' => 'billing',
    'view_prefix' => 'admin/billing/checkout',
    'view_ids' =>
    array(
      0 => 'admin-billing-checkout',
    ),
    'vite_entry' => 'admin/billing/checkout.js',
    'css_entry' => '',
    'breadcrumbs' =>
    array(
      0 =>
      array(
        'label' => 'Assinatura',
        'url' => '/billing',
        'icon' => 'star',
      ),
      1 =>
      array(
        'label' => 'Pagamento',
        'icon' => 'credit-card',
      ),
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 102,
  ),
);
