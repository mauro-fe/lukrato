<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$perfilPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'perfil'
    ? $layoutPageCapabilities
    : [];

$perfilCustomizerCapabilities = is_array($perfilPageCapabilities['customizer'] ?? null)
    ? $perfilPageCapabilities['customizer']
    : [];

$perfilCustomizerDescriptor = is_array($perfilCustomizerCapabilities['descriptor'] ?? null)
    ? $perfilCustomizerCapabilities['descriptor']
    : [];

$perfilTrigger = is_array($perfilCustomizerCapabilities['trigger'] ?? null)
    ? $perfilCustomizerCapabilities['trigger']
    : [];

$customizeModal = [
    'title' => (string) ($perfilCustomizerDescriptor['title'] ?? 'Personalizar perfil'),
    'description' => (string) ($perfilCustomizerDescriptor['description'] ?? 'Escolha quais blocos estruturais quer manter visíveis na tela de perfil.'),
    'size' => (string) ($perfilCustomizerDescriptor['size'] ?? 'wide'),
    'renderOverlay' => (bool) ($perfilCustomizerCapabilities['renderOverlay'] ?? false),
    'showPresets' => false,
    'trigger' => [
        'render' => (bool) ($perfilTrigger['show'] ?? true),
        'id' => (string) ($perfilCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizePerfil'),
        'label' => (string) ($perfilTrigger['label'] ?? 'Personalizar perfil'),
        'wrapperClass' => (string) ($perfilCustomizerDescriptor['trigger']['wrapperClass'] ?? 'profile-customize-trigger'),
    ],
    'ids' => is_array($perfilCustomizerDescriptor['ids'] ?? null)
        ? $perfilCustomizerDescriptor['ids']
        : [
            'overlay' => 'perfilCustomizeModalOverlay',
            'title' => 'perfilCustomizeModalTitle',
            'description' => 'perfilCustomizeModalDescription',
            'close' => 'btnCloseCustomizePerfil',
            'save' => 'btnSaveCustomizePerfil',
            'presetEssential' => 'btnPresetEssencialPerfil',
            'presetComplete' => 'btnPresetCompletoPerfil',
        ],
    'groups' => is_array($perfilCustomizerDescriptor['groups'] ?? null)
        ? $perfilCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'togglePerfilHeader', 'label' => 'Cabeçalho do perfil'],
                    ['id' => 'togglePerfilTabs', 'label' => 'Navegação por abas'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
