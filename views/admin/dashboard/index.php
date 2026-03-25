<?php
$onboardingCompleted = !empty($currentUser?->onboarding_completed_at);
?>

<?php if (!empty($showOnboardingCongrats)): ?>
    <script>
        window.__lkFirstVisit = true;
    </script>
<?php endif; ?>

<section class="modern-dashboard">

    <!-- ============================================================
         HERO — Saldo principal + variação do mês
         ============================================================ -->
    <section class="dashboard-hero-section" id="saldoCard">
        <span class="dash-hero__label">Saldo atual</span>
        <div class="dash-hero__balance kpi-value loading" id="saldoValue">R$ 0,00</div>
        <div class="dash-hero__variation" id="dashboardHeroStatus"></div>

        <!-- Mensagem oculta — usada pelo JS para narrativa -->
        <p class="dash-hero__message" id="dashboardHeroMessage" style="display:none;"></p>
    </section>

    <!-- ============================================================
         KPI ROW — 3 cards: Entradas · Saídas · Resultado
         ============================================================ -->
    <section class="dash-kpis" role="region" aria-label="Indicadores do mês">
        <div class="dash-kpi dash-kpi--income" id="receitasCard">
            <div class="dash-kpi__icon dash-kpi__icon--income">
                <i data-lucide="arrow-down-left"></i>
            </div>
            <div class="dash-kpi__body">
                <span class="dash-kpi__label">Entradas</span>
                <span class="dash-kpi__value income loading" id="receitasValue">R$ 0,00</span>
            </div>
        </div>

        <div class="dash-kpi dash-kpi--expense" id="despesasCard">
            <div class="dash-kpi__icon dash-kpi__icon--expense">
                <i data-lucide="arrow-up-right"></i>
            </div>
            <div class="dash-kpi__body">
                <span class="dash-kpi__label">Saídas</span>
                <span class="dash-kpi__value expense loading" id="despesasValue">R$ 0,00</span>
            </div>
        </div>

        <div class="dash-kpi dash-kpi--result" id="saldoMesCard">
            <div class="dash-kpi__icon dash-kpi__icon--result">
                <i data-lucide="scale"></i>
            </div>
            <div class="dash-kpi__body">
                <span class="dash-kpi__label">Resultado</span>
                <span class="dash-kpi__value loading" id="saldoMesValue">R$ 0,00</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
         GRÁFICO — Despesas por categoria (donut)
         ============================================================ -->
    <section class="dash-chart-section" id="chart-section">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Despesas por categoria</h2>
        </div>
        <div class="dash-chart-wrap">
            <div class="chart-loading" id="chartLoading"></div>
            <div id="categoryChart" role="img" aria-label="Gráfico de despesas por categoria"></div>
        </div>
    </section>

    <!-- ============================================================
         TRANSAÇÕES RECENTES — Lista simples
         ============================================================ -->
    <section class="dash-transactions-section" id="table-section">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Transações recentes</h2>
            <a href="<?= BASE_URL ?>lancamentos" class="dash-section-link">
                Ver todas <i data-lucide="arrow-right"></i>
            </a>
        </div>

        <!-- Estado vazio -->
        <div class="dash-empty" id="emptyState" style="display:none;">
            <i data-lucide="receipt"></i>
            <p>Nenhuma movimentação recente</p>
            <button class="dash-btn dash-btn--primary"
                onclick="if (window.fab) { window.fab.open(); } else { window.location.href = window.BASE_URL + 'lancamentos'; }">
                <i data-lucide="plus"></i> Adicionar
            </button>
        </div>

        <!-- Lista renderizada pelo JS -->
        <div class="dash-transactions-list" id="transactionsList"></div>
    </section>

    <!-- ============================================================
         SEÇÕES OPCIONAIS — toggled via modal de personalização
         ============================================================ -->
    <section class="dash-optional-section" id="sectionMetas" style="display:none;">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Metas</h2>
        </div>
        <p class="dash-placeholder">Suas metas financeiras aparecerão aqui em breve.</p>
    </section>

    <section class="dash-optional-section" id="sectionCartoes" style="display:none;">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Cartões</h2>
        </div>
        <p class="dash-placeholder">Resumo dos seus cartões aparecerá aqui em breve.</p>
    </section>

    <section class="dash-optional-section" id="sectionContas" style="display:none;">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Contas</h2>
        </div>
        <p class="dash-placeholder">Saldos das suas contas aparecerão aqui em breve.</p>
    </section>

    <!-- ============================================================
         PERSONALIZAR DASHBOARD — Botão + Modal
         ============================================================ -->
    <div class="dash-customize-trigger">
        <button class="dash-btn dash-btn--ghost" id="btnCustomizeDashboard" type="button">
            <i data-lucide="sliders-horizontal"></i> Personalizar dashboard
        </button>
    </div>

    <!-- Modal de personalização -->
    <div class="dash-modal-overlay" id="customizeModalOverlay" style="display:none;">
        <div class="dash-modal" role="dialog" aria-modal="true" aria-labelledby="customizeModalTitle">
            <div class="dash-modal__header">
                <h3 class="dash-modal__title" id="customizeModalTitle">Personalizar dashboard</h3>
                <button class="dash-modal__close" id="btnCloseCustomize" type="button" title="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="dash-modal__body">
                <p class="dash-modal__desc">Escolha o que deseja ver no seu dashboard.</p>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleGrafico" checked>
                    <span class="dash-toggle__label">Mostrar gráfico</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleMetas">
                    <span class="dash-toggle__label">Mostrar metas</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleCartoes">
                    <span class="dash-toggle__label">Mostrar cartões</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleContas">
                    <span class="dash-toggle__label">Mostrar contas</span>
                </label>
            </div>
            <div class="dash-modal__footer">
                <button class="dash-btn dash-btn--primary" id="btnSaveCustomize" type="button">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Containers ocultos para compatibilidade com JS antigos -->
    <div id="greetingContainer" style="display:none;"></div>
    <div id="healthScoreContainer" style="display:none;"></div>
    <div id="healthScoreInsights" style="display:none;"></div>
    <div id="financeOverviewContainer" style="display:none;"></div>
    <div id="dashboardAlertsSection" style="display:none;">
        <div id="dashboardAlertsOverview"></div>
        <div id="dashboardAlertsBudget"></div>
    </div>

    <!-- Container oculto para tabela desktop (compatibilidade com app.js) -->
    <table id="transactionsTable" style="display:none;">
        <tbody id="transactionsTableBody"></tbody>
    </table>
    <div id="transactionsCards" style="display:none;"></div>
</section>

<?= vite_scripts('admin/gamification-dashboard/index.js') ?>
