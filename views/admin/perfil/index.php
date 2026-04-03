<!-- CSS carregado via loadPageCss() no header -->

<?php
$perfilViewMode = $perfilViewMode ?? 'perfil';
$isConfigView = $perfilViewMode === 'configuracoes';
$isProfileView = !$isConfigView;
?>

<div class="profile-page">
<?php include __DIR__ . '/sections/header.php'; ?>
<?php include __DIR__ . '/sections/tabs.php'; ?>

    <form id="profileForm" autocomplete="off">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

<?php if ($isProfileView): ?>
<?php include __DIR__ . '/sections/panel-dados.php'; ?>
<?php include __DIR__ . '/sections/panel-endereco.php'; ?>
<?php endif; ?>

<?php if ($isConfigView): ?>
<?php include __DIR__ . '/sections/panel-seguranca.php'; ?>
<?php endif; ?>
    </form>

<?php if ($isProfileView): ?>
<?php include __DIR__ . '/sections/config-shortcut.php'; ?>
<?php endif; ?>

<?php if ($isConfigView): ?>
<?php include __DIR__ . '/sections/panel-plano.php'; ?>
<?php include __DIR__ . '/sections/panel-integracoes.php'; ?>
<?php include __DIR__ . '/sections/panel-perigo.php'; ?>
<?php endif; ?>

<?php include __DIR__ . '/sections/customize-modal.php'; ?>
</div>

<!-- JS carregado via Vite (loadPageJs) -->
