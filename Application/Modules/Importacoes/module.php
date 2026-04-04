<?php

declare(strict_types=1);

return [
    [
        'key' => 'importacoes',
        'label' => 'Importações',
        'title' => 'Importações',
        'icon' => 'file-up',
        'group' => 'Finanças',
        'route' => 'importacoes',
        'menu' => 'importacoes',
        'view_prefix' => 'admin/importacoes',
        'view_ids' => [
            'admin-importacoes-index',
        ],
        'vite_entry' => 'admin/importacoes/index.js',
        'css_entry' => '',
        'breadcrumbs' => [
            [
                'label' => 'Finanças',
                'icon' => 'wallet',
            ],
        ],
        'placement' => 'sidebar',
        'order' => 60,
    ],
    [
        'key' => 'importacoes_configuracoes',
        'label' => 'Configuracoes de Importacao',
        'title' => 'Configuracoes de Importacao',
        'icon' => 'sliders-horizontal',
        'group' => 'Finanças',
        'route' => 'importacoes/configuracoes',
        'menu' => 'importacoes',
        'view_prefix' => 'admin/importacoes/configuracoes',
        'view_ids' => [
            'admin-importacoes-configuracoes-index',
        ],
        'vite_entry' => 'admin/importacoes/configuracoes.js',
        'css_entry' => '',
        'breadcrumbs' => [
            [
                'label' => 'Finanças',
                'icon' => 'wallet',
            ],
            [
                'label' => 'Importações',
                'url' => 'importacoes',
                'icon' => 'file-up',
            ],
        ],
        'placement' => 'hidden',
        'hidden' => true,
        'order' => 61,
    ],
    [
        'key' => 'importacoes_historico',
        'label' => 'Historico de Importações',
        'title' => 'Historico de Importações',
        'icon' => 'history',
        'group' => 'Finanças',
        'route' => 'importacoes/historico',
        'menu' => 'importacoes',
        'view_prefix' => 'admin/importacoes/historico',
        'view_ids' => [
            'admin-importacoes-historico-index',
        ],
        'vite_entry' => 'admin/importacoes/historico.js',
        'css_entry' => '',
        'breadcrumbs' => [
            [
                'label' => 'Finanças',
                'icon' => 'wallet',
            ],
            [
                'label' => 'Importações',
                'url' => 'importacoes',
                'icon' => 'file-up',
            ],
        ],
        'placement' => 'hidden',
        'hidden' => true,
        'order' => 62,
    ],
];
