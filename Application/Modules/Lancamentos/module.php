<?php

declare(strict_types=1);

return array(
  'key' => 'lancamentos',
  'label' => 'Transações',
  'title' => 'Lançamentos',
  'icon' => 'arrow-left-right',
  'group' => 'Finanças',
  'route' => 'lancamentos',
  'menu' => 'lancamentos',
  'view_prefix' => 'admin/lancamentos',
  'view_ids' =>
  array(
    0 => 'admin-lancamentos-index',
  ),
  'vite_entry' => 'admin/lancamentos/index.js',
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
  'order' => 20,
);
