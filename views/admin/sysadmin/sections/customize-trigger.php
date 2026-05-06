<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$sysadminPageCapabilities = isset($sysadminPageCapabilities) && is_array($sysadminPageCapabilities)
    ? $sysadminPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'sysadmin'
        ? $layoutPageCapabilities
        : []);

$sysadminCustomizerCapabilities = isset($sysadminCustomizerCapabilities) && is_array($sysadminCustomizerCapabilities)
    ? $sysadminCustomizerCapabilities
    : (is_array($sysadminPageCapabilities['customizer'] ?? null)
        ? $sysadminPageCapabilities['customizer']
        : []);

$sysadminTrigger = isset($sysadminTrigger) && is_array($sysadminTrigger)
    ? $sysadminTrigger
    : (is_array($sysadminCustomizerCapabilities['trigger'] ?? null)
        ? $sysadminCustomizerCapabilities['trigger']
        : []);

$sysadminTriggerLabel = isset($sysadminTriggerLabel) && is_string($sysadminTriggerLabel) && $sysadminTriggerLabel !== ''
    ? $sysadminTriggerLabel
    : (string) ($sysadminTrigger['label'] ?? 'Personalizar sysadmin');
?>

<div class="sys-customize-trigger">
    <button class="sys-customize-open" id="btnCustomizeSysadmin" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span><?= escape($sysadminTriggerLabel) ?></span>
    </button>
</div>