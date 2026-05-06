<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$categoriasPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'categorias'
    ? $layoutPageCapabilities
    : [];

$categoriasCustomizerCapabilities = is_array($categoriasPageCapabilities['customizer'] ?? null)
    ? $categoriasPageCapabilities['customizer']
    : [];

$categoriasForcedPreferences = is_array($categoriasCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $categoriasCustomizerCapabilities['forcedPreferences']
    : [];

$showCategoriasKpis = (bool) ($categoriasForcedPreferences['toggleCategoriasKpis'] ?? true);
$showCategoriasCreateCard = (bool) ($categoriasForcedPreferences['toggleCategoriasCreateCard'] ?? true);
$categoriasTrigger = is_array($categoriasCustomizerCapabilities['trigger'] ?? null)
    ? $categoriasCustomizerCapabilities['trigger']
    : [];
$categoriasTriggerLabel = (string) ($categoriasTrigger['label'] ?? 'Personalizar categorias');
?>

<section class="cat-page">

    <?php include __DIR__ . '/sections/kpis.php'; ?>
    <?php include __DIR__ . '/sections/create-card.php'; ?>
    <?php include __DIR__ . '/sections/categories-grid.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<?php include __DIR__ . '/../partials/modals/editar-categorias.php'; ?>
<?php include __DIR__ . '/sections/modal-orcamento.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->