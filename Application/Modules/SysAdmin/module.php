<?php

declare(strict_types=1);

return array (
  0 => 
  array (
    'key' => 'super_admin',
    'label' => 'SysAdmin',
    'title' => 'SysAdmin',
    'icon' => 'shield',
    'group' => '',
    'route' => 'super_admin',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/index',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-index',
    ),
    'vite_entry' => 'admin/sysadmin/index.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'footer',
    'sysadmin_only' => true,
    'order' => 30,
  ),
  1 => 
  array (
    'key' => 'sysadmin_communications',
    'label' => 'Comunicações',
    'title' => 'Comunicações',
    'icon' => 'megaphone',
    'group' => '',
    'route' => 'sysadmin/comunicacoes',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/communications',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-communications',
    ),
    'vite_entry' => 'admin/sysadmin/communications.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'sysadmin_only' => true,
    'order' => 31,
  ),
  2 => 
  array (
    'key' => 'sysadmin_cupons',
    'label' => 'Cupons',
    'title' => 'Cupons',
    'icon' => 'ticket',
    'group' => '',
    'route' => 'sysadmin/cupons',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/cupons',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-cupons',
    ),
    'vite_entry' => 'admin/sysadmin/cupons.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'sysadmin_only' => true,
    'order' => 32,
  ),
  3 => 
  array (
    'key' => 'sysadmin_blog',
    'label' => 'Blog',
    'title' => 'Blog',
    'icon' => 'newspaper',
    'group' => '',
    'route' => 'sysadmin/blog',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/blog',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-blog',
    ),
    'vite_entry' => 'admin/sysadmin/blog.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'sysadmin_only' => true,
    'order' => 33,
  ),
  4 => 
  array (
    'key' => 'sysadmin_ai',
    'label' => 'IA',
    'title' => 'IA',
    'icon' => 'bot',
    'group' => '',
    'route' => 'sysadmin/ai',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/ai',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-ai',
    ),
    'vite_entry' => 'admin/sysadmin/ai-chat.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'sysadmin_only' => true,
    'order' => 34,
  ),
  5 => 
  array (
    'key' => 'sysadmin_ai_logs',
    'label' => 'Logs IA',
    'title' => 'Logs IA',
    'icon' => 'list-checks',
    'group' => '',
    'route' => 'sysadmin/ai/logs',
    'menu' => 'super_admin',
    'view_prefix' => 'admin/sysadmin/ai-logs',
    'view_ids' => 
    array (
      0 => 'admin-sysadmin-ai-logs',
    ),
    'vite_entry' => 'admin/sysadmin/ai-logs.js',
    'css_entry' => '',
    'breadcrumbs' => 
    array (
    ),
    'placement' => 'hidden',
    'hidden' => true,
    'sysadmin_only' => true,
    'order' => 35,
  ),
);
