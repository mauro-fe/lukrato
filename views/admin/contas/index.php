<?php
$isPro = $isPro ?? false;
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];
$contasPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'contas'
    ? $layoutPageCapabilities
    : [];
$contasCustomizerCapabilities = is_array($contasPageCapabilities['customizer'] ?? null)
    ? $contasPageCapabilities['customizer']
    : [];
$contasForcedPreferences = is_array($contasCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $contasCustomizerCapabilities['forcedPreferences']
    : null;
$showContasHero = !is_array($contasForcedPreferences)
    || (bool) ($contasForcedPreferences['toggleContasHero'] ?? true);
$showContasKpis = !is_array($contasForcedPreferences)
    || (bool) ($contasForcedPreferences['toggleContasKpis'] ?? true);
$contasCanAccessComplete = array_key_exists('canAccessComplete', $contasCustomizerCapabilities)
    ? (bool) $contasCustomizerCapabilities['canAccessComplete']
    : (bool) $isPro;
$contasTriggerLabel = (string) ($contasCustomizerCapabilities['trigger']['label'] ?? 'Personalizar contas');
$contasTriggerAction = (string) ($contasCustomizerCapabilities['trigger']['action'] ?? 'customize');
?>

<section class="cont-page">
    <div class="cont-stage cont-stage--overview">
        <div class="cont-overview-top">
            <?php include __DIR__ . '/sections/hero.php'; ?>
            <?php include __DIR__ . '/sections/kpis.php'; ?>
        </div>
    </div>

    <div class="cont-stage cont-stage--listing">
        <?php include __DIR__ . '/sections/list-section.php'; ?>
    </div>
</section>

<?php include __DIR__ . '/sections/customize-modal.php'; ?>

<?php include __DIR__ . '/../partials/modals/modal-contas.php'; ?>