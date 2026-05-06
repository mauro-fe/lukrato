<?php
$isPro = $isPro ?? false;
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];
$cartoesPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'cartoes'
    ? $layoutPageCapabilities
    : [];
$cartoesCustomizerCapabilities = is_array($cartoesPageCapabilities['customizer'] ?? null)
    ? $cartoesPageCapabilities['customizer']
    : [];
$cartoesForcedPreferences = is_array($cartoesCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $cartoesCustomizerCapabilities['forcedPreferences']
    : null;
$showCartoesKpis = !is_array($cartoesForcedPreferences)
    || (bool) ($cartoesForcedPreferences['toggleCartoesKpis'] ?? true);
$showCartoesToolbar = !is_array($cartoesForcedPreferences)
    || (bool) ($cartoesForcedPreferences['toggleCartoesToolbar'] ?? true);
$cartoesCanAccessComplete = array_key_exists('canAccessComplete', $cartoesCustomizerCapabilities)
    ? (bool) $cartoesCustomizerCapabilities['canAccessComplete']
    : (bool) $isPro;
?>

<section
    class="cartoes-page">

    <?php include __DIR__ . '/sections/kpis.php'; ?>
    <?php include __DIR__ . '/sections/alerts.php'; ?>
    <?php include __DIR__ . '/sections/list-section.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal-cartoes.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>