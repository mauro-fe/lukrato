<section class="modern-dashboard">
    <?php include __DIR__ . '/sections/first-run.php'; ?>
    <div class="dashboard-stage dashboard-stage--overview">
        <div class="dashboard-overview-top">
            <?php include __DIR__ . '/sections/hero.php'; ?>
            <?php include __DIR__ . '/sections/kpis.php'; ?>
        </div>

        <div class="dashboard-overview-bottom">
            <?php include __DIR__ . '/sections/alerts.php'; ?>
            <?php include __DIR__ . '/sections/health-ai.php'; ?>
        </div>
    </div>

    <div class="dashboard-stage dashboard-stage--decision">
        <?php include __DIR__ . '/sections/previsao-gamificacao.php'; ?>
        <?php include __DIR__ . '/sections/chart-transactions.php'; ?>
    </div>

    <div class="dashboard-stage dashboard-stage--history">
        <?php include __DIR__ . '/sections/evolucao.php'; ?>
    </div>

    <div class="dashboard-stage dashboard-stage--secondary">
        <?php include __DIR__ . '/sections/optional-grid.php'; ?>
    </div>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
    <?php include __DIR__ . '/sections/compatibility-containers.php'; ?>
</section>

<?= vite_scripts('admin/gamification-dashboard/index.js') ?>