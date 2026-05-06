<!-- CSS carregado via loadPageCss() no header -->

<div
    class="profile-page profile-page--configuracoes"
    data-account-root>
    <?php include __DIR__ . '/sections/header.php'; ?>
    <?php include __DIR__ . '/sections/panel-aparencia.php'; ?>

    <?php include __DIR__ . '/sections/tabs.php'; ?>

    <form id="profileForm" autocomplete="off">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>
        <?php include __DIR__ . '/sections/panel-seguranca.php'; ?>
    </form>

    <?php include __DIR__ . '/sections/panel-plano.php'; ?>
    <?php include __DIR__ . '/sections/panel-integracoes.php'; ?>
    <?php include __DIR__ . '/sections/panel-perigo.php'; ?>
</div>

<!-- JS carregado via Vite (loadPageJs) -->