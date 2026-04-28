<section class="met-page">
    <section class="met-overview-shell surface-card surface-card--interactive surface-card--clip">
        <div class="met-overview-shell__top">
            <div class="met-overview-shell__intro">
                <?php include __DIR__ . '/sections/header.php'; ?>
                <?php include __DIR__ . '/sections/actions-bar.php'; ?>
            </div>

            <?php include __DIR__ . '/sections/summary-cards.php'; ?>
        </div>

        <?php include __DIR__ . '/sections/toolbar.php'; ?>
    </section>

    <div class="met-workspace-shell">
        <div class="met-workspace-main">
            <section class="met-listing-shell surface-card surface-card--interactive surface-card--clip">
                <header class="met-listing-shell__head" data-aos="fade-up" data-aos-delay="140">
                    <div>
                        <p class="met-section-eyebrow">Objetivos em andamento</p>
                        <h2 class="met-section-title">Mapa das metas</h2>
                        <p class="met-section-copy">Pesquise, filtre e acompanhe cada objetivo por prazo, prioridade e valor restante.</p>
                    </div>
                    <div class="met-listing-shell__meta">
                        <span class="met-listing-shell__meta-label">Metas visíveis</span>
                        <strong class="met-listing-shell__meta-value" id="metVisibleCount">--</strong>
                    </div>
                </header>

                <?php include __DIR__ . '/sections/grid.php'; ?>
                <?php include __DIR__ . '/sections/empty-state.php'; ?>
            </section>
        </div>

        <aside class="met-workspace-side">
            <?php include __DIR__ . '/sections/focus-panel.php'; ?>
            <?php include __DIR__ . '/sections/insights.php'; ?>
        </aside>
    </div>

    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<?php include __DIR__ . '/sections/modal-meta.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->