<?php
$customizeModal = [
    'title' => 'Personalizar billing',
    'description' => 'Escolha os blocos que deseja manter visíveis nesta tela.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeBilling',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'bill-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'billingCustomizeModalOverlay',
        'title' => 'billingCustomizeModalTitle',
        'description' => 'billingCustomizeModalDescription',
        'close' => 'btnCloseCustomizeBilling',
        'save' => 'btnSaveCustomizeBilling',
        'presetEssential' => 'btnPresetEssencialBilling',
        'presetComplete' => 'btnPresetCompletoBilling',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleBillingHeader', 'label' => 'Cabeçalho da página'],
                ['id' => 'toggleBillingPlans', 'label' => 'Grid de planos'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
