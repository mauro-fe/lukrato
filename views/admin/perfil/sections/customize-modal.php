<?php
$customizeModal = [
    'title' => 'Personalizar perfil',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizePerfil',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'profile-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'perfilCustomizeModalOverlay',
        'title' => 'perfilCustomizeModalTitle',
        'description' => 'perfilCustomizeModalDescription',
        'close' => 'btnCloseCustomizePerfil',
        'save' => 'btnSaveCustomizePerfil',
        'presetEssential' => 'btnPresetEssencialPerfil',
        'presetComplete' => 'btnPresetCompletoPerfil',
    ],
    'groups' => [
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
