<!-- CSS carregado via loadPageCss() no header -->

<div
    class="profile-page profile-page--perfil"
    data-account-root
    data-profile-root>
    <?php include __DIR__ . '/sections/header.php'; ?>

    <?php include __DIR__ . '/sections/tabs.php'; ?>

    <form id="profileForm" autocomplete="off">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

        <?php include __DIR__ . '/sections/panel-dados.php'; ?>
        <?php include __DIR__ . '/sections/panel-endereco.php'; ?>
    </form>

    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</div>

<!-- JS carregado via Vite (loadPageJs) -->
