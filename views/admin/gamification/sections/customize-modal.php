<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$gamificationPageCapabilities = isset($gamificationPageCapabilities) && is_array($gamificationPageCapabilities)
    ? $gamificationPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'gamification'
        ? $layoutPageCapabilities
        : []);

$gamificationCustomizerCapabilities = isset($gamificationCustomizerCapabilities) && is_array($gamificationCustomizerCapabilities)
    ? $gamificationCustomizerCapabilities
    : (is_array($gamificationPageCapabilities['customizer'] ?? null)
        ? $gamificationPageCapabilities['customizer']
        : []);

$gamificationCustomizerDescriptor = is_array($gamificationCustomizerCapabilities['descriptor'] ?? null)
    ? $gamificationCustomizerCapabilities['descriptor']
    : [];

$gamificationCanCustomize = (bool) ($gamificationCustomizerCapabilities['canCustomize'] ?? false);
$gamificationLockedToggles = is_array($gamificationCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $gamificationCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$gamificationHasLockedToggles = $gamificationLockedToggles !== [];
$gamificationUpgradeCta = is_array($gamificationCustomizerCapabilities['upgradeCta'] ?? null)
    ? $gamificationCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($gamificationCustomizerDescriptor['title'] ?? 'Personalizar gamificação'),
    'description' => (string) ($gamificationCustomizerDescriptor['description'] ?? 'Mantenha a jornada essencial em foco ou libere o histórico e o ranking na tela completa.'),
    'size' => (string) ($gamificationCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$gamificationCanCustomize && $gamificationHasLockedToggles,
    'lockedState' => $gamificationHasLockedToggles && is_array($gamificationCustomizerDescriptor['lockedState'] ?? null)
        ? $gamificationCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $gamificationLockedToggles,
    'disableInputs' => !$gamificationCanCustomize,
    'hideSave' => !$gamificationCanCustomize,
    'footerCta' => $gamificationHasLockedToggles && !empty($gamificationUpgradeCta['show'])
        ? [
            'label' => (string) ($gamificationUpgradeCta['label'] ?? 'Desbloquear gamificação completa'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($gamificationCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($gamificationCustomizerCapabilities['availablePresets'] ?? null) ? $gamificationCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($gamificationCustomizerDescriptor['ids'] ?? null)
        ? $gamificationCustomizerDescriptor['ids']
        : [
            'overlay' => 'gamificationCustomizeModalOverlay',
            'title' => 'gamificationCustomizeModalTitle',
            'description' => 'gamificationCustomizeModalDescription',
            'close' => 'btnCloseCustomizeGamification',
            'save' => 'btnSaveCustomizeGamification',
            'presetEssential' => 'btnPresetEssencialGamification',
            'presetComplete' => 'btnPresetCompletoGamification',
        ],
    'groups' => is_array($gamificationCustomizerDescriptor['groups'] ?? null)
        ? $gamificationCustomizerDescriptor['groups']
        : [
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
