<!-- CSS Lancamentos — carregado via Vite (import no JS entry) -->

<?php
$isPro = $isPro ?? false;
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];
$lancamentosPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'lancamentos'
    ? $layoutPageCapabilities
    : [];
$lancamentosCustomizerCapabilities = is_array($lancamentosPageCapabilities['customizer'] ?? null)
    ? $lancamentosPageCapabilities['customizer']
    : [];
$lancamentosForcedPreferences = is_array($lancamentosCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $lancamentosCustomizerCapabilities['forcedPreferences']
    : null;
$showLanFilters = !is_array($lancamentosForcedPreferences)
    || (bool) ($lancamentosForcedPreferences['toggleLanFilters'] ?? true);
$showLanExport = !is_array($lancamentosForcedPreferences)
    || (bool) ($lancamentosForcedPreferences['toggleLanExport'] ?? true);
$lancamentosCanAccessComplete = array_key_exists('canAccessComplete', $lancamentosCustomizerCapabilities)
    ? (bool) $lancamentosCustomizerCapabilities['canAccessComplete']
    : (bool) $isPro;
?>

<section class="lan-page">
    <div class="lan-stage lan-stage--overview">
        <div class="lan-overview-bottom">
            <?php include __DIR__ . '/sections/filters.php'; ?>
            <?php include __DIR__ . '/sections/export-card.php'; ?>
        </div>
    </div>

    <div class="lan-stage lan-stage--listing">
        <?php include __DIR__ . '/sections/table.php'; ?>
    </div>

    <div class="lan-stage lan-stage--secondary">
        <?php include __DIR__ . '/sections/customize-modal.php'; ?>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/editar-lancamentos.php'; ?>
<?php include __DIR__ . '/../partials/modals/visualizar-lancamento.php'; ?>
<?php include __DIR__ . '/../partials/modals/editar-transferencia.php'; ?>
<?php include __DIR__ . '/../partials/modals/excluir-lancamento-escopo.php'; ?>