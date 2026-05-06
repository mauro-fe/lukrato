<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$billingPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'billing'
    ? $layoutPageCapabilities
    : [];

$billingCustomizerCapabilities = is_array($billingPageCapabilities['customizer'] ?? null)
    ? $billingPageCapabilities['customizer']
    : [];

$billingCustomizerDescriptor = is_array($billingCustomizerCapabilities['descriptor'] ?? null)
    ? $billingCustomizerCapabilities['descriptor']
    : [];

$billingTrigger = is_array($billingCustomizerCapabilities['trigger'] ?? null)
    ? $billingCustomizerCapabilities['trigger']
    : [];

$customizeModal = [
    'title' => (string) ($billingCustomizerDescriptor['title'] ?? 'Personalizar assinatura'),
    'description' => (string) ($billingCustomizerDescriptor['description'] ?? 'Escolha quais blocos quer manter visíveis na página de planos.'),
    'size' => (string) ($billingCustomizerDescriptor['size'] ?? 'wide'),
    'renderOverlay' => (bool) ($billingCustomizerCapabilities['renderOverlay'] ?? false),
    'showPresets' => false,
    'trigger' => [
        'render' => (bool) ($billingTrigger['show'] ?? true),
        'id' => (string) ($billingCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeBilling'),
        'label' => (string) ($billingTrigger['label'] ?? 'Personalizar assinatura'),
        'wrapperClass' => (string) ($billingCustomizerDescriptor['trigger']['wrapperClass'] ?? 'bill-customize-trigger'),
    ],
    'ids' => is_array($billingCustomizerDescriptor['ids'] ?? null)
        ? $billingCustomizerDescriptor['ids']
        : [
            'overlay' => 'billingCustomizeModalOverlay',
            'title' => 'billingCustomizeModalTitle',
            'description' => 'billingCustomizeModalDescription',
            'close' => 'btnCloseCustomizeBilling',
            'save' => 'btnSaveCustomizeBilling',
            'presetEssential' => 'btnPresetEssencialBilling',
            'presetComplete' => 'btnPresetCompletoBilling',
        ],
    'groups' => is_array($billingCustomizerDescriptor['groups'] ?? null)
        ? $billingCustomizerDescriptor['groups']
        : [
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
