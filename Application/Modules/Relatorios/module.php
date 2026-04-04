<?php

declare(strict_types=1);

return array (
  'key' => 'relatorios',
  'label' => 'Relatórios',
  'title' => 'Relatórios',
  'icon' => 'bar-chart',
  'group' => 'Análise',
  'route' => 'relatorios',
  'menu' => 'relatorios',
  'view_prefix' => 'admin/relatorios',
  'view_ids' =>
  array (
    0 => 'admin-relatorios-index',
  ),
  'vite_entry' => 'admin/relatorios/index.js',
  'css_entry' => '',
  'breadcrumbs' =>
  array (
    0 =>
    array (
      'label' => 'Análises',
      'icon' => 'bar-chart-3',
    ),
  ),
  'placement' => 'sidebar',
  'order' => 80,
);
