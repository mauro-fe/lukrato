<?php
$customizeModal = [
    'title' => 'Personalizar gamificação',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'ids' => [
        'overlay' => 'gamificationCustomizeModalOverlay',
        'title' => 'gamificationCustomizeModalTitle',
        'description' => 'gamificationCustomizeModalDescription',
        'close' => 'btnCloseCustomizeGamification',
        'save' => 'btnSaveCustomizeGamification',
        'presetEssential' => 'btnPresetEssencialGamification',
        'presetComplete' => 'btnPresetCompletoGamification',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleGamHeader', 'label' => 'Cabeçalho'],
                ['id' => 'toggleGamProgress', 'label' => 'Progresso geral'],
                ['id' => 'toggleGamAchievements', 'label' => 'Conquistas'],
                ['id' => 'toggleGamHistory', 'label' => 'Histórico recente'],
                ['id' => 'toggleGamLeaderboard', 'label' => 'Ranking'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
