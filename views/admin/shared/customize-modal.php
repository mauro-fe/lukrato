<?php
$customizeModalConfig = is_array($customizeModal ?? null) ? $customizeModal : [];

$escapeCustomizeValue = static function ($value): string {
    $value = (string) $value;

    return function_exists('escape')
        ? (string) escape($value)
        : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$customizeIds = is_array($customizeModalConfig['ids'] ?? null) ? $customizeModalConfig['ids'] : [];
$customizeTrigger = is_array($customizeModalConfig['trigger'] ?? null) ? $customizeModalConfig['trigger'] : [];
$customizeGroups = is_array($customizeModalConfig['groups'] ?? null) ? $customizeModalConfig['groups'] : [];
$customizeSize = (string) ($customizeModalConfig['size'] ?? '');

if ($customizeGroups === [] && is_array($customizeModalConfig['toggles'] ?? null)) {
    $customizeGroups[] = [
        'title' => $customizeModalConfig['groupTitle'] ?? 'Blocos da tela',
        'items' => $customizeModalConfig['toggles'],
    ];
}

$customizeOverlayId = (string) ($customizeIds['overlay'] ?? 'customizeModalOverlay');
$customizeTitleId = (string) ($customizeIds['title'] ?? ($customizeOverlayId . 'Title'));
$customizeDescriptionId = (string) ($customizeIds['description'] ?? ($customizeOverlayId . 'Description'));
$customizeCloseId = (string) ($customizeIds['close'] ?? 'btnCloseCustomize');
$customizeSaveId = (string) ($customizeIds['save'] ?? 'btnSaveCustomize');
$customizePresetEssentialId = (string) ($customizeIds['presetEssential'] ?? 'btnPresetEssencial');
$customizePresetCompleteId = (string) ($customizeIds['presetComplete'] ?? 'btnPresetCompleto');

$customizeTitle = (string) ($customizeModalConfig['title'] ?? 'Personalizar tela');
$customizeDescription = (string) ($customizeModalConfig['description'] ?? 'Comece no modo essencial e habilite os blocos quando quiser.');
$customizeRenderTrigger = !empty($customizeTrigger['render']);
$customizeTriggerId = (string) ($customizeTrigger['id'] ?? ($customizeIds['open'] ?? 'btnCustomizeDashboard'));
$customizeTriggerLabel = (string) ($customizeTrigger['label'] ?? 'Personalizar tela');
$customizeTriggerWrapperClass = trim('lk-customize-trigger ' . (string) ($customizeTrigger['wrapperClass'] ?? ''));
$customizeModalClass = trim('lk-customize-modal surface-card ' . ($customizeSize !== '' ? 'lk-customize-modal--' . $customizeSize : ''));
?>

<?php if ($customizeRenderTrigger): ?>
    <div class="<?= $escapeCustomizeValue($customizeTriggerWrapperClass) ?>">
        <button class="lk-customize-open surface-button surface-button--subtle" id="<?= $escapeCustomizeValue($customizeTriggerId) ?>" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span><?= $escapeCustomizeValue($customizeTriggerLabel) ?></span>
        </button>
    </div>
<?php endif; ?>

<div class="lk-customize-overlay" id="<?= $escapeCustomizeValue($customizeOverlayId) ?>" style="display:none;" aria-hidden="true">
    <div class="<?= $escapeCustomizeValue($customizeModalClass) ?>" role="dialog" aria-modal="true"
        aria-labelledby="<?= $escapeCustomizeValue($customizeTitleId) ?>"
        aria-describedby="<?= $escapeCustomizeValue($customizeDescriptionId) ?>">
        <div class="lk-customize-header">
            <div class="lk-customize-heading">
                <span class="lk-customize-icon" aria-hidden="true">
                    <i data-lucide="sliders-horizontal"></i>
                </span>
                <div class="lk-customize-heading__copy">
                    <h3 class="lk-customize-title" id="<?= $escapeCustomizeValue($customizeTitleId) ?>">
                        <?= $escapeCustomizeValue($customizeTitle) ?>
                    </h3>
                    <p class="lk-customize-desc" id="<?= $escapeCustomizeValue($customizeDescriptionId) ?>">
                        <?= $escapeCustomizeValue($customizeDescription) ?>
                    </p>
                </div>
            </div>

            <button class="lk-customize-close" id="<?= $escapeCustomizeValue($customizeCloseId) ?>" type="button"
                aria-label="Fechar personalização">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="lk-customize-body">
            <div class="lk-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="lk-customize-preset" id="<?= $escapeCustomizeValue($customizePresetEssentialId) ?>" type="button">
                    <i data-lucide="list-checks"></i>
                    <span>Modo essencial</span>
                </button>
                <button class="lk-customize-preset" id="<?= $escapeCustomizeValue($customizePresetCompleteId) ?>" type="button">
                    <i data-lucide="layout-grid"></i>
                    <span>Modo completo</span>
                </button>
            </div>

            <div class="lk-customize-groups">
                <?php foreach ($customizeGroups as $customizeGroup): ?>
                    <?php
                    $customizeItems = is_array($customizeGroup['items'] ?? null) ? $customizeGroup['items'] : [];
                    $customizeGroupTitle = (string) ($customizeGroup['title'] ?? 'Blocos da tela');
                    $customizeItemCount = count($customizeItems);
                    ?>
                    <section class="lk-customize-group" aria-label="<?= $escapeCustomizeValue($customizeGroupTitle) ?>">
                        <div class="lk-customize-group__head">
                            <span class="lk-customize-group__title"><?= $escapeCustomizeValue($customizeGroupTitle) ?></span>
                            <span class="lk-customize-group__count"><?= $customizeItemCount ?> <?= $customizeItemCount === 1 ? 'bloco' : 'blocos' ?></span>
                        </div>

                        <div class="lk-customize-options">
                            <?php foreach ($customizeItems as $customizeItem): ?>
                                <?php
                                if (!is_array($customizeItem)) {
                                    continue;
                                }

                                $customizeToggleId = (string) ($customizeItem['id'] ?? '');
                                if ($customizeToggleId === '') {
                                    continue;
                                }

                                $customizeToggleLabel = (string) ($customizeItem['label'] ?? $customizeToggleId);
                                $customizeToggleDescription = (string) ($customizeItem['description'] ?? '');
                                $customizeToggleChecked = array_key_exists('checked', $customizeItem) ? (bool) $customizeItem['checked'] : true;
                                ?>
                                <label class="lk-customize-toggle" for="<?= $escapeCustomizeValue($customizeToggleId) ?>">
                                    <span class="lk-customize-toggle__copy">
                                        <span class="lk-customize-toggle__title"><?= $escapeCustomizeValue($customizeToggleLabel) ?></span>
                                        <?php if ($customizeToggleDescription !== ''): ?>
                                            <span class="lk-customize-toggle__desc"><?= $escapeCustomizeValue($customizeToggleDescription) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="lk-customize-toggle__control">
                                        <input class="lk-customize-toggle__input" type="checkbox"
                                            id="<?= $escapeCustomizeValue($customizeToggleId) ?>" <?= $customizeToggleChecked ? 'checked' : '' ?>>
                                        <span class="lk-customize-switch" aria-hidden="true"></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="lk-customize-footer">
            <button class="lk-customize-save surface-button surface-button--primary" id="<?= $escapeCustomizeValue($customizeSaveId) ?>" type="button">
                <i data-lucide="check"></i>
                <span>Salvar</span>
            </button>
        </div>
    </div>
</div>

<?php
unset(
    $customizeModal,
    $customizeModalConfig,
    $customizeIds,
    $customizeTrigger,
    $customizeGroups,
    $customizeSize,
    $customizeOverlayId,
    $customizeTitleId,
    $customizeDescriptionId,
    $customizeCloseId,
    $customizeSaveId,
    $customizePresetEssentialId,
    $customizePresetCompleteId,
    $customizeTitle,
    $customizeDescription,
    $customizeRenderTrigger,
    $customizeTriggerId,
    $customizeTriggerLabel,
    $customizeTriggerWrapperClass,
    $customizeModalClass,
    $escapeCustomizeValue
);
?>
