<?php

declare(strict_types=1);

return [
    'key' => 'frontend-pilot',
    'label' => 'Frontend Pilot',
    'title' => 'Frontend Pilot',
    'icon' => 'rocket',
    'group' => '',
    'route' => 'frontend-pilot',
    'menu' => 'perfil',
    'view_prefix' => 'admin/frontend-pilot',
    'view_ids' => [
        'admin-frontend-pilot-index',
    ],
    'vite_entry' => 'admin/frontend-pilot/index.js',
    'css_entry' => '',
    'breadcrumbs' => [
        [
            'label' => 'Perfil',
            'icon' => 'user',
        ],
    ],
    'placement' => 'hidden',
    'hidden' => true,
    'order' => 30,
];
