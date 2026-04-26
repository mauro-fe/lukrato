    <div class="rel-section-bar">
        <nav class="rel-section-tabs surface-card surface-card--clip" role="tablist" aria-label="Seções de relatórios">
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft active" data-section="overview"
                role="tab" aria-selected="true" aria-controls="section-overview">
                <span class="tab-icon"><i data-lucide="layout-dashboard" style="color:#3b82f6"></i></span>
                <span class="tab-label">Visão Geral</span>
            </button>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft" data-section="relatorios"
                role="tab" aria-selected="false" aria-controls="section-relatorios">
                <span class="tab-icon"><i data-lucide="bar-chart-3" style="color:#e67e22"></i></span>
                <span class="tab-label">Relatórios</span>
            </button>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft" data-section="insights"
                role="tab" aria-selected="false" aria-controls="section-insights">
                <span class="tab-icon"><i data-lucide="lightbulb" style="color:#facc15"></i></span>
                <span class="tab-label">Insights Inteligentes</span>
                <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"><i
                            data-lucide="crown"></i> PRO</span><?php endif; ?>
            </button>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft<?= !$isPro ? ' pro-tab-locked' : '' ?>"
                data-section="comparativos" role="tab" aria-selected="false" aria-controls="section-comparativos">
                <span class="tab-icon"><i data-lucide="git-compare" style="color:#3b82f6"></i></span>
                <span class="tab-label">Comparativos</span>
                <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"><i
                            data-lucide="crown"></i> PRO</span><?php endif; ?>
            </button>
        </nav>

        <button class="rel-customize-open surface-card" id="btnCustomizeRelatorios" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>
