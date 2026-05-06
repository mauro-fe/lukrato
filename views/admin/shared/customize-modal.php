<?php
$pageCustomizeModal = isset($customizeModal) && is_array($customizeModal)
    ? $customizeModal
    : [];

$customizeModalConfig = $pageCustomizeModal;

$escapeCustomizeValue = static function ($value): string {
    $value = (string) $value;

    return function_exists('escape')
        ? (string) escape($value)
        : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$customizeIds = is_array($customizeModalConfig['ids'] ?? null) ? $customizeModalConfig['ids'] : [];
$customizeTrigger = is_array($customizeModalConfig['trigger'] ?? null) ? $customizeModalConfig['trigger'] : [];
$customizeGroups = is_array($customizeModalConfig['groups'] ?? null) ? $customizeModalConfig['groups'] : [];
$customizeLockedState = is_array($customizeModalConfig['lockedState'] ?? null) ? $customizeModalConfig['lockedState'] : [];
$customizeLockedToggleIds = is_array($customizeModalConfig['lockedToggleIds'] ?? null)
    ? array_values(array_filter(
        $customizeModalConfig['lockedToggleIds'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$customizeFooterCta = is_array($customizeModalConfig['footerCta'] ?? null) ? $customizeModalConfig['footerCta'] : [];
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
$customizeLocked = !empty($customizeModalConfig['locked']);
$customizeHasLockedToggles = $customizeLockedToggleIds !== [];
$customizeDisableInputs = $customizeLocked || (!empty($customizeModalConfig['disableInputs']));
$customizeHideSave = !empty($customizeModalConfig['hideSave']);
$customizeRenderTrigger = !empty($customizeTrigger['render']);
$customizeRenderOverlay = !array_key_exists('renderOverlay', $customizeModalConfig) || (bool) $customizeModalConfig['renderOverlay'];
$customizeShowPresets = !array_key_exists('showPresets', $customizeModalConfig) || (bool) $customizeModalConfig['showPresets'];
$customizeTriggerId = (string) ($customizeTrigger['id'] ?? ($customizeIds['open'] ?? 'btnCustomizeDashboard'));
$customizeTriggerLabel = (string) ($customizeTrigger['label'] ?? 'Personalizar tela');
$customizeTriggerHref = trim((string) ($customizeTrigger['href'] ?? ''));
$customizeFooterCtaLabel = (string) ($customizeFooterCta['label'] ?? '');
$customizeFooterCtaHref = trim((string) ($customizeFooterCta['href'] ?? ''));
$customizeTriggerWrapperClass = trim('lk-customize-trigger ' . (string) ($customizeTrigger['wrapperClass'] ?? ''));
$customizeTriggerButtonClass = trim((string) ($customizeTrigger['buttonClass'] ?? 'lk-customize-open surface-button surface-button--subtle'));
$customizeModalClass = trim('lk-customize-modal surface-card ' . ($customizeSize !== '' ? 'lk-customize-modal--' . $customizeSize : ''));
?>

<?php if ($customizeRenderTrigger): ?>
    <div class="<?= $escapeCustomizeValue($customizeTriggerWrapperClass) ?>">
        <?php if ($customizeTriggerHref !== ''): ?>
            <a class="<?= $escapeCustomizeValue($customizeTriggerButtonClass) ?>" id="<?= $escapeCustomizeValue($customizeTriggerId) ?>" href="<?= $escapeCustomizeValue($customizeTriggerHref) ?>">
                <i data-lucide="sliders-horizontal"></i>
                <span><?= $escapeCustomizeValue($customizeTriggerLabel) ?></span>
            </a>
        <?php else: ?>
            <button class="<?= $escapeCustomizeValue($customizeTriggerButtonClass) ?>" id="<?= $escapeCustomizeValue($customizeTriggerId) ?>" type="button">
                <i data-lucide="sliders-horizontal"></i>
                <span><?= $escapeCustomizeValue($customizeTriggerLabel) ?></span>
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($customizeRenderOverlay): ?>
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
                <?php if ($customizeLockedState !== [] && ($customizeLocked || $customizeHasLockedToggles)): ?>
                    <?php
                    $customizeLockedEyebrow = (string) ($customizeLockedState['eyebrow'] ?? 'Plano atual');
                    $customizeLockedTitle = (string) ($customizeLockedState['title'] ?? 'Recursos liberados em planos superiores');
                    $customizeLockedDescription = (string) ($customizeLockedState['description'] ?? 'Alguns recursos desta tela exigem um plano superior.');
                    $customizeLockedTiers = is_array($customizeLockedState['tiers'] ?? null) ? $customizeLockedState['tiers'] : [];
                    ?>
                    <section class="lk-customize-locked" aria-label="Recursos liberados por plano">
                        <div class="lk-customize-locked__intro">
                            <span class="lk-customize-locked__eyebrow"><?= $escapeCustomizeValue($customizeLockedEyebrow) ?></span>
                            <h4 class="lk-customize-locked__title"><?= $escapeCustomizeValue($customizeLockedTitle) ?></h4>
                            <p class="lk-customize-locked__desc"><?= $escapeCustomizeValue($customizeLockedDescription) ?></p>
                        </div>

                        <?php if ($customizeLockedTiers !== []): ?>
                            <div class="lk-customize-locked__plans" aria-label="Planos disponíveis">
                                <?php foreach ($customizeLockedTiers as $customizeLockedTier): ?>
                                    <?php
                                    if (!is_array($customizeLockedTier)) {
                                        continue;
                                    }

                                    $customizeTierName = (string) ($customizeLockedTier['name'] ?? 'Plano');
                                    $customizeTierDescription = (string) ($customizeLockedTier['description'] ?? '');
                                    $customizeTierItems = is_array($customizeLockedTier['items'] ?? null) ? $customizeLockedTier['items'] : [];
                                    $customizeTierText = $customizeTierDescription !== ''
                                        ? $customizeTierDescription
                                        : implode(' • ', array_map('strval', $customizeTierItems));
                                    $customizeTierTone = strtolower($customizeTierName) === 'ultra' ? 'ultra' : 'pro';

                                    if ($customizeTierText === '') {
                                        continue;
                                    }
                                    ?>
                                    <p class="lk-customize-locked__plan lk-customize-locked__plan--<?= $escapeCustomizeValue($customizeTierTone) ?>">
                                        <span class="lk-customize-locked__plan-badge lk-customize-locked__plan-badge--<?= $escapeCustomizeValue($customizeTierTone) ?>">
                                            <?= $escapeCustomizeValue($customizeTierName) ?>
                                        </span>
                                        <span class="lk-customize-locked__plan-text"><?= $escapeCustomizeValue($customizeTierText) ?></span>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($customizeShowPresets): ?>
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
                <?php endif; ?>

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
                                    $customizeTogglePlan = strtolower(trim((string) ($customizeItem['plan'] ?? 'free')));
                                    $customizeTogglePlanLabel = strtoupper($customizeTogglePlan);
                                    $customizeToggleChecked = array_key_exists('checked', $customizeItem) ? (bool) $customizeItem['checked'] : true;
                                    $customizeToggleLocked = in_array($customizeToggleId, $customizeLockedToggleIds, true);
                                    $customizeToggleDisabled = $customizeDisableInputs || $customizeToggleLocked || !empty($customizeItem['disabled']);
                                    ?>
                                    <label class="lk-customize-toggle<?= $customizeToggleDisabled ? ' is-disabled' : '' ?>" for="<?= $escapeCustomizeValue($customizeToggleId) ?>">
                                        <span class="lk-customize-toggle__copy">
                                            <span class="lk-customize-toggle__title-row">
                                                <span class="lk-customize-toggle__title"><?= $escapeCustomizeValue($customizeToggleLabel) ?></span>
                                                <?php if (($customizeLocked || $customizeToggleLocked) && $customizeTogglePlan !== 'free'): ?>
                                                    <span class="lk-customize-toggle__badge lk-customize-toggle__badge--<?= $escapeCustomizeValue($customizeTogglePlan) ?>">
                                                        <?= $escapeCustomizeValue($customizeTogglePlanLabel) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($customizeToggleDescription !== ''): ?>
                                                <span class="lk-customize-toggle__desc"><?= $escapeCustomizeValue($customizeToggleDescription) ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="lk-customize-toggle__control">
                                            <input class="lk-customize-toggle__input" type="checkbox"
                                                id="<?= $escapeCustomizeValue($customizeToggleId) ?>" <?= $customizeToggleChecked ? 'checked' : '' ?> <?= $customizeToggleDisabled ? 'disabled' : '' ?>>
                                            <span class="lk-customize-switch" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!$customizeHideSave || $customizeFooterCtaHref !== ''): ?>
                <div class="lk-customize-footer">
                    <?php if (!$customizeHideSave): ?>
                        <button class="lk-customize-save surface-button surface-button--primary" id="<?= $escapeCustomizeValue($customizeSaveId) ?>" type="button">
                            <i data-lucide="check"></i>
                            <span>Salvar</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($customizeFooterCtaHref !== ''): ?>
                        <a class="lk-customize-upgrade surface-button surface-button--upgrade" href="<?= $escapeCustomizeValue($customizeFooterCtaHref) ?>">
                            <i data-lucide="sparkles"></i>
                            <span><?= $escapeCustomizeValue($customizeFooterCtaLabel) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
unset(
    $customizeModal,
    $customizeModalConfig,
    $customizeIds,
    $customizeTrigger,
    $customizeGroups,
    $customizeLockedState,
    $customizeLockedToggleIds,
    $customizeFooterCta,
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
    $customizeLocked,
    $customizeHasLockedToggles,
    $customizeDisableInputs,
    $customizeHideSave,
    $customizeRenderTrigger,
    $customizeRenderOverlay,
    $customizeShowPresets,
    $customizeTriggerId,
    $customizeTriggerLabel,
    $customizeTriggerHref,
    $customizeFooterCtaLabel,
    $customizeFooterCtaHref,
    $customizeTriggerWrapperClass,
    $customizeTriggerButtonClass,
    $customizeModalClass,
    $escapeCustomizeValue
);
?>