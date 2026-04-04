<?php

declare(strict_types=1);

return array (
  'key' => 'categorias',
  'label' => 'Categorias',
  'title' => 'Categorias',
  'icon' => 'tags',
  'group' => 'Organização',
  'route' => 'categorias',
  'menu' => 'categorias',
  'view_prefix' => 'admin/categorias',
  'view_ids' =>
  array (
    0 => 'admin-categorias-index',
  ),
  'vite_entry' => 'admin/categorias/index.js',
  'css_entry' => '',
  'breadcrumbs' =>
  array (
    0 =>
    array (
      'label' => 'Organização',
      'icon' => 'folder',
    ),
  ),
  'placement' => 'sidebar',
  'order' => 90,
);
