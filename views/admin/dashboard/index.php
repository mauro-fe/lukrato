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
         ALERTAS — toggled via personalização
         ============================================================ -->
    <section class="dash-prominent-section" id="sectionAlertas" style="display:none;">
        <div id="dashboardAlertsOverview"></div>
        <div id="dashboardAlertsBudget"></div>
    </section>

    <!-- ============================================================
         SAÚDE FINANCEIRA + DICA IA — lado a lado (duo-row)
         ============================================================ -->
    <div class="dash-duo-row dash-duo-row--flexible" id="rowHealthAi">
        <section id="sectionHealthScore" style="display:none;">
            <div id="healthScoreContainer"></div>
        </section>
        <section id="sectionAiTip" style="display:none;">
            <div id="aiTipContainer"></div>
        </section>
    </div>

    <!-- Health Score Insights (full-width, below duo-row) -->
    <div id="healthScoreInsights" class="dash-prominent-section"></div>

    <!-- ============================================================
         EVOLUÇÃO FINANCEIRA — Mensal · Anual
         ============================================================ -->
    <section class="dash-prominent-section" id="sectionEvolucao" style="display:none;">
        <div id="evolucaoChartsContainer"></div>
    </section>

    <!-- ============================================================
         PREVISÃO + GAMIFICAÇÃO — lado a lado
         ============================================================ -->
    <div class="dash-duo-row dash-duo-row--flexible">

        <!-- PREVISÃO FINANCEIRA — toggled via personalização -->
        <section class="provisao-section" id="sectionPrevisao" style="display:none;">
            <h2 class="provisao-title" id="provisaoTitle">Previsão financeira</h2>
            <p class="provisao-headline" id="provisaoHeadline"></p>

            <!-- Alertas de vencidos -->
            <div class="provisao-alerts-container">
                <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                    <div class="provisao-alert-icon"><i data-lucide="alert-triangle"></i></div>
                    <span class="provisao-alert-text">
                        <strong id="provisaoAlertDespesasCount">0</strong> despesa(s) vencida(s) totalizando
                        <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                    </span>
                    <a href="<?= BASE_URL ?>lancamentos?status=vencido" class="provisao-alert-link">
                        Ver <i data-lucide="arrow-right"></i>
                    </a>
                </div>
                <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                    <div class="provisao-alert-icon"><i data-lucide="alert-circle"></i></div>
                    <span class="provisao-alert-text">
                        <strong id="provisaoAlertReceitasCount">0</strong> receita(s) não recebida(s) totalizando
                        <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                    </span>
                    <a href="<?= BASE_URL ?>lancamentos?tipo=receita&status=vencido" class="provisao-alert-link">
                        Ver <i data-lucide="arrow-right"></i>
                    </a>
                </div>
                <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                    <div class="provisao-alert-icon"><i data-lucide="credit-card"></i></div>
                    <span class="provisao-alert-text">
                        <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) totalizando
                        <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                    </span>
                    <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">
                        Ver <i data-lucide="arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Cards de resumo -->
            <div class="provisao-grid">
                <div class="provisao-card pagar">
                    <div class="provisao-card-icon"><i data-lucide="arrow-up-right"></i></div>
                    <div class="provisao-card-body">
                        <span class="provisao-card-label">A pagar</span>
                        <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                        <span class="provisao-card-count" id="provisaoPagarCount">0 pendentes</span>
                    </div>
                </div>
                <div class="provisao-card receber">
                    <div class="provisao-card-icon"><i data-lucide="arrow-down-left"></i></div>
                    <div class="provisao-card-body">
                        <span class="provisao-card-label">A receber</span>
                        <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                        <span class="provisao-card-count" id="provisaoReceberCount">0 pendentes</span>
                    </div>
                </div>
                <div class="provisao-card projetado">
                    <div class="provisao-card-icon"><i data-lucide="trending-up"></i></div>
                    <div class="provisao-card-body">
                        <span class="provisao-card-label">Saldo projetado</span>
                        <span class="provisao-card-value" id="provisaoProjetado">R$ 0,00</span>
                        <span class="provisao-card-count" id="provisaoProjetadoLabel">saldo atual: R$ 0,00</span>
                    </div>
                </div>
            </div>

            <!-- Próximos vencimentos -->
            <div class="provisao-proximos">
                <div class="provisao-proximos-header">
                    <span class="provisao-proximos-title" id="provisaoProximosTitle">
                        <i data-lucide="clock"></i> Próximos Vencimentos
                    </span>
                    <a href="<?= BASE_URL ?>lancamentos" class="provisao-ver-todos" id="provisaoVerTodos">
                        Ver todos <i data-lucide="arrow-right"></i>
                    </a>
                </div>
                <div class="provisao-proximos-list" id="provisaoProximosList">
                    <div class="provisao-empty" id="provisaoEmpty" style="display:none;">
                        <i data-lucide="check-circle"></i>
                        <span>Nenhum vencimento pendente</span>
                    </div>
                </div>
            </div>

            <!-- Parcelas ativas -->
            <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
                <div class="provisao-parcelas-icon"><i data-lucide="layers"></i></div>
                <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
                <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mês</span>
            </div>

            <!-- PRO overlay -->
            <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
                <div class="provisao-pro-content">
                    <div class="provisao-pro-icon"><i data-lucide="crown"></i></div>
                    <h3>Previsão completa</h3>
                    <p>Veja receitas, despesas e saldo projetado com o plano Pro.</p>
                    <a href="<?= BASE_URL ?>billing" class="provisao-pro-btn">
                        <i data-lucide="gem"></i> Assinar Pro
                    </a>
                </div>
            </div>
        </section>

        <!-- ============================================================
         GAMIFICAÇÃO — toggled via personalização
         ============================================================ -->
        <section class="gamification-section" id="sectionGamificacao" style="display:none;">
            <div class="gamification-header">
                <h2 class="gamification-title"><i data-lucide="trophy"></i> Gamificação</h2>
                <div class="level-badge" id="userLevel"><i data-lucide="star"></i> <span>Nível 1</span></div>
            </div>

            <div class="gamification-grid">
                <div class="streak-card">
                    <div class="streak-icon">&#128293;</div>
                    <div class="streak-number" id="streakDays">0</div>
                    <div class="streak-label">Dias seguidos</div>
                    <div class="streak-protection" id="streakProtection" style="display:none;">
                        <i data-lucide="shield"></i> Proteção ativa
                    </div>
                </div>

                <div class="level-progress-card progress-card">
                    <div class="progress-header">
                        <span class="progress-title"><i data-lucide="bar-chart-3"></i> Progresso</span>
                        <span class="progress-percentage" id="levelProgressText"></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" id="levelProgressBar" style="width:0%;"></div>
                    </div>
                    <div class="progress-text" id="levelProgressPoints">0 / 300 pontos</div>
                </div>

                <div class="badges-card">
                    <h3 class="badges-title"><i data-lucide="medal"></i> Conquistas</h3>
                    <div class="badges-grid" id="badgesGrid"></div>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-value" id="totalLancamentos">0</div>
                    <div class="stat-mini-label">Lançamentos</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" id="totalCategorias">0</div>
                    <div class="stat-mini-label">Categorias</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" id="mesesAtivos">0</div>
                    <div class="stat-mini-label">Meses ativos</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" id="pontosTotal">0</div>
                    <div class="stat-mini-label">Pontos</div>
                </div>
            </div>

            <div id="proCTA" style="display:none;">
                <button class="btn-pro-upgrade">
                    <i data-lucide="gem"></i> Desbloqueie mais com o Pro
                </button>
            </div>
            <span id="proBadge" style="display:none;" class="pro-badge-inline">PRO</span>
        </section>

    </div><!-- /dash-duo-row--flexible -->

    <!-- ============================================================
         GRID — Gráfico + Transações lado a lado
         ============================================================ -->
    <div class="dash-duo-row">
        <section class="dash-chart-section" id="chart-section">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Despesas por categoria</h2>
            </div>
            <div class="dash-chart-wrap">
                <div class="chart-loading" id="chartLoading"></div>
                <div id="categoryChart" role="img" aria-label="Gráfico de despesas por categoria"></div>
            </div>
        </section>

        <section class="dash-transactions-section" id="table-section">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Últimas transações</h2>
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
    </div>

    <!-- ============================================================
         SEÇÕES OPCIONAIS — toggled via modal de personalização
         ============================================================ -->
    <div class="dash-optional-grid" id="optionalGrid" style="display:none;">
        <section class="dash-optional-section" id="sectionMetas" style="display:none;">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Metas</h2>
            </div>
            <div class="dash-optional-body" id="sectionMetasBody">
                <p class="dash-placeholder">Suas metas financeiras aparecerão aqui em breve.</p>
            </div>
        </section>

        <section class="dash-optional-section" id="sectionCartoes" style="display:none;">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Cartões</h2>
            </div>
            <div class="dash-optional-body" id="sectionCartoesBody">
                <p class="dash-placeholder">Resumo dos seus cartões aparecerá aqui em breve.</p>
            </div>
        </section>

        <section class="dash-optional-section" id="sectionContas" style="display:none;">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Contas</h2>
            </div>
            <div class="dash-optional-body" id="sectionContasBody">
                <p class="dash-placeholder">Saldos das suas contas aparecerão aqui em breve.</p>
            </div>
        </section>

        <section class="dash-optional-section" id="sectionOrcamentos" style="display:none;">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Orçamentos</h2>
            </div>
            <div class="dash-optional-body" id="sectionOrcamentosBody">
                <p class="dash-placeholder">Seus limites de categorias aparecerão aqui.</p>
            </div>
        </section>

        <section class="dash-optional-section" id="sectionFaturas" style="display:none;">
            <div class="dash-section-header">
                <h2 class="dash-section-title">Faturas</h2>
            </div>
            <div class="dash-optional-body" id="sectionFaturasBody">
                <p class="dash-placeholder">Resumo das suas faturas de cartão aparecerá aqui.</p>
            </div>
        </section>
    </div>

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

                <div class="dash-toggle-group">
                    <span class="dash-toggle-group__title">Principais</span>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleAlertas" checked>
                        <span class="dash-toggle__label">Alertas</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleHealthScore" checked>
                        <span class="dash-toggle__label">Saúde financeira</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleAiTip" checked>
                        <span class="dash-toggle__label">Dicas do Lukrato</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleEvolucao" checked>
                        <span class="dash-toggle__label">Evolução financeira</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="togglePrevisao" checked>
                        <span class="dash-toggle__label">Previsão financeira</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleGrafico" checked>
                        <span class="dash-toggle__label">Gráfico de categorias</span>
                    </label>
                </div>

                <div class="dash-toggle-group">
                    <span class="dash-toggle-group__title">Extras</span>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleMetas">
                        <span class="dash-toggle__label">Metas</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleCartoes">
                        <span class="dash-toggle__label">Cartões</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleContas">
                        <span class="dash-toggle__label">Contas</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleOrcamentos">
                        <span class="dash-toggle__label">Orçamentos</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleFaturas">
                        <span class="dash-toggle__label">Faturas de cartão</span>
                    </label>
                    <label class="dash-toggle">
                        <input type="checkbox" id="toggleGamificacao">
                        <span class="dash-toggle__label">Gamificação</span>
                    </label>
                </div>
            </div>
            <div class="dash-modal__footer">
                <button class="dash-btn dash-btn--primary" id="btnSaveCustomize" type="button">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Containers ocultos para compatibilidade com JS antigos -->
    <div id="greetingContainer" style="display:none;"></div>
    <div id="financeOverviewContainer" style="display:none;"></div>

    <!-- Container oculto para tabela desktop (compatibilidade com app.js) -->
    <table id="transactionsTable" style="display:none;">
        <tbody id="transactionsTableBody"></tbody>
    </table>
    <div id="transactionsCards" style="display:none;"></div>
</section>

<?= vite_scripts('admin/gamification-dashboard/index.js') ?>