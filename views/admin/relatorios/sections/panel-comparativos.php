    <!-- ==================== SEÇÃO: COMPARATIVOS ==================== -->
    <div class="rel-section-panel" id="section-comparativos" role="tabpanel">
        <div
            class="modern-card comparatives-card surface-card surface-card--interactive surface-card--clip <?= !$isPro ? 'pro-locked' : '' ?>">
            <div class="card-header">
                <div class="header-left">
                    <i data-lucide="line-chart"></i>
                    <div class="header-text">
                        <h3>Comparativos</h3>
                        <p>Análise de evolução temporal</p>
                    </div>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge"><i data-lucide="crown"></i> PRO</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i data-lucide="crown"></i>
                            <h4>Recurso Premium</h4>
                            <p style="font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Comparativos é exclusivo do <a href="<?= BASE_URL ?>billing"
                                    style="color:#60a5fa;text-decoration:underline">plano Pro</a>.
                            </p>
                            <a href="<?= BASE_URL ?>billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                                <i data-lucide="crown"></i> Fazer Upgrade
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="comparativesContainer" class="comparatives-container">
                        <div class="lk-loading-state">
                            <i data-lucide="loader-2"></i>
                            <p>Carregando comparativos...</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
