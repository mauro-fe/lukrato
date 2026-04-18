<?php

declare(strict_types=1);

return [
    [
        'key' => 'lancamentos',
        'label' => 'Transações',
        'title' => 'Lançamentos',
        'icon' => 'arrow-left-right',
        'group' => 'Finanças',
        'route' => 'lancamentos',
        'menu' => 'lancamentos',
        'view_prefix' => 'admin/lancamentos',
        'view_ids' => [
            'admin-lancamentos-index',
        ],
        'vite_entry' => 'admin/lancamentos/index.js',
        'css_entry' => '',
        'breadcrumbs' => [
            [
                'label' => 'Finanças',
                'icon' => 'wallet',
            ],
        ],
        'placement' => 'sidebar',
        'order' => 20,
    ],
    [
        'key' => 'lancamentos_create',
        'label' => 'Nova Transação',
        'title' => 'Nova Transação',
        'icon' => 'plus',
        'group' => 'Finanças',
        'route' => 'lancamentos/novo',
        'menu' => 'lancamentos',
        'view_prefix' => 'admin/lancamentos/create',
        'view_ids' => [
            'admin-lancamentos-create',
        ],
        'vite_entry' => 'admin/lancamentos/create.js',
        'css_entry' => '',
        'breadcrumbs' => [
            [
                'label' => 'Finanças',
                'icon' => 'wallet',
            ],
            [
                'label' => 'Transações',
                'url' => 'lancamentos',
                'icon' => 'arrow-left-right',
            ],
        ],
        'placement' => 'hidden',
        'hidden' => true,
        'order' => 20,
    ],
];
