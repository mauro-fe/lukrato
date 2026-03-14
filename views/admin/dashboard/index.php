<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/onboarding-checklist.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/floating-action-button.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/progressive-disclosure.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/celebration-animations.css">

<?php if (!empty($showOnboardingCongrats)): ?>
    <script>
        window.__lkFirstVisit = true;
    </script>
<?php endif; ?>

<!-- Onboarding Checklist (persistent) -->
<div class="lk-checklist" id="onboardingChecklist">
    <div class="lk-checklist-accent"></div>
    <div class="lk-checklist-body">
        <div class="lk-checklist-header">
            <div class="lk-checklist-icon-box"><i data-lucide="rocket" style="color:#fff;width:24px;height:24px;"></i>
            </div>
            <div class="lk-checklist-title">
                <h2>Primeiros passos</h2>
                <p>Complete as etapas para aproveitar o melhor do Lukrato</p>
            </div>
            <button class="lk-checklist-dismiss" id="checklistDismiss" title="Pular etapas">
                <span>Pular</span>
                <i data-lucide="x" style="width:12px;height:12px;"></i>
            </button>
            <div class="lk-checklist-badge" id="checklistBadge">0/6</div>
        </div>

        <div class="lk-checklist-progress">
            <div class="lk-checklist-progress-fill" id="checklistProgressFill"></div>
        </div>

        <div class="lk-checklist-items" id="checklistItems">
            <!-- JS populates -->
        </div>
    </div>
</div>

<section class="modern-dashboard">

    <!-- Gamificação -->
    <section class="gamification-section" data-aos="fade-up" data-aos-duration="500">
        <div class="gamification-header">
            <div class="gamification-title">
                <i data-lucide="trophy"></i>
                <span>Seu Progresso</span>
                <span class="pro-badge" id="proBadge" style="display: none;">
                    <i data-lucide="gem"></i> PRO
                </span>
            </div>
            <div class="level-badge" id="userLevel">
                <i data-lucide="star" style="color: white"></i>
                <span>Nível 1</span>
            </div>
        </div>

        <div class="gamification-grid">
            <!-- Streak -->
            <div class="streak-card">
                <div class="streak-icon"><i data-lucide="flame"
                        style="width:28px;height:28px;color:var(--color-warning,#f59e0b);"></i></div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias Ativos</div>
                <div class="streak-protection" id="streakProtection" style="display: none;">
                    <i data-lucide="shield"></i>
                    <span>Proteção disponível</span>
                </div>
            </div>

            <!-- Progresso -->
            <div class="level-progress-card">
                <div class="level-progress-header">
                    <div class="level-progress-label">
                        <i data-lucide="bar-chart-3"></i>
                        <span>Progresso para próximo nível</span>
                    </div>


                    <span class="level-progress-points" id="levelProgressPoints">0 / 300 pontos</span>
                </div>
                <div class="level-progress-bar-container">
                    <div class="level-progress-bar" id="levelProgressBar" style="width: 0%"></div>
                </div>
                <div class="level-progress-text" id="levelProgressText">Ganhe mais pontos para avançar!</div>
            </div>

            <!-- Badges -->
            <div class="badges-card">
                <div class="badges-title">
                    <i data-lucide="medal"></i>
                    <span>Conquistas</span>
                    <a href="<?= BASE_URL ?>gamification" class="btn-view-all">Ver todas</a>
                </div>
                <div class="badges-grid" id="badgesGrid">
                    <!-- Preenchido via JS -->
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                </div>
            </div>
        </div>

        <!-- Mini Stats -->
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
                <div class="stat-mini-label">Meses Ativos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="pontosTotal">0</div>
                <div class="stat-mini-label">Pontos</div>
            </div>
        </div>

        <!-- Call to Action Pro (apenas para usuários Free) -->
        <div class="pro-cta-card" id="proCTA" style="display: none;">
            <div class="pro-cta-content">
                <div class="pro-cta-icon">
                    <i data-lucide="rocket"></i>
                </div>
                <div class="pro-cta-text">
                    <h3>Acelere seu progresso com o Plano Pro</h3>
                    <p>Ganhe 1.5x mais pontos, proteção de streak e conquistas exclusivas!</p>
                </div>
                <button class="btn-pro-upgrade">
                    <i data-lucide="gem"></i>
                    Conhecer o Pro
                </button>
            </div>
        </div>
    </section>

    <!-- KPI Cards -->
    <section class="kpi-grid" role="region" aria-label="Indicadores principais">
        <div data-aos="fade-up" data-aos-duration="500">
            <div class="modern-kpi" id="saldoCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <span class="kpi-label">Saldo Atual</span>
                </div>
                <div class="kpi-value loading" id="saldoValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="modern-kpi" id="receitasCard">
                <div class="kpi-header">
                    <div class="kpi-icon income">
                        <i data-lucide="arrow-up"></i>
                    </div>
                    <span class="kpi-label">Receitas do Mês</span>
                </div>
                <div class="kpi-value income loading" id="receitasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="modern-kpi" id="despesasCard">
                <div class="kpi-header">
                    <div class="kpi-icon expense">
                        <i data-lucide="arrow-down"></i>
                    </div>
                    <span class="kpi-label">Despesas do Mês</span>
                </div>
                <div class="kpi-value expense loading" id="despesasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
            <div class="modern-kpi" id="saldoMesCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i data-lucide="scale" style="color: white"></i>
                    </div>
                    <span class="kpi-label">Saldo do Mês</span>
                </div>
                <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <!-- Previsão Financeira -->
    <section class="provisao-section" id="provisaoSection" data-aos="fade-up" data-aos-duration="500">
        <h2 class="provisao-title">
            <i data-lucide="calendar-check"></i>
            Previsão Financeira
        </h2>

        <!-- Alertas de vencidos -->
        <div class="provisao-alerts-container" id="provisaoAlertsContainer">
            <!-- Alerta de despesas vencidas -->
            <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="triangle-alert" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertDespesasCount">0</strong> despesa(s) vencida(s) totalizando
                    <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>lancamentos?tipo=despesa&pago=0" class="provisao-alert-link">Ver <i
                        data-lucide="arrow-right"></i></a>
            </div>
            <!-- Alerta de receitas vencidas (não recebidas) -->
            <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="info" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertReceitasCount">0</strong> recebimento(s) atrasado(s) totalizando
                    <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>lancamentos?tipo=receita&pago=0" class="provisao-alert-link">Ver <i
                        data-lucide="arrow-right"></i></a>
            </div>
            <!-- Alerta de faturas vencidas -->
            <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="credit-card" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) totalizando
                    <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">Ver <i data-lucide="arrow-right"></i></a>
            </div>
        </div>

        <!-- Cards de Provisão -->
        <div class="provisao-grid">
            <div class="provisao-card pagar">
                <div class="provisao-card-icon"><i data-lucide="arrow-up"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Pagar</span>
                    <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPagarCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card receber">
                <div class="provisao-card-icon"><i data-lucide="arrow-down"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Receber</span>
                    <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoReceberCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card projetado">
                <div class="provisao-card-icon"><i data-lucide="line-chart" style="color:#fff"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Saldo Projetado</span>
                    <span class="provisao-card-value" id="provisaoProjetado">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoProjetadoLabel">saldo atual + previsão</span>
                </div>
            </div>
        </div>

        <!-- Próximos Vencimentos -->
        <div class="provisao-proximos">
            <div class="provisao-proximos-header">
                <span class="provisao-proximos-title" id="provisaoProximosTitle"><i data-lucide="clock"></i> Próximos
                    Vencimentos</span>
                <a href="<?= BASE_URL ?>lancamentos" class="provisao-ver-todos" id="provisaoVerTodos">Ver todos <i
                        data-lucide="arrow-right"></i></a>
            </div>
            <div class="provisao-proximos-list" id="provisaoProximosList">
                <div class="provisao-empty" id="provisaoEmpty">
                    <i data-lucide="circle-check"></i>
                    <span>Nenhum vencimento pendente</span>
                </div>
            </div>
        </div>

        <!-- Parcelas Ativas -->
        <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
            <div class="provisao-parcelas-icon"><i data-lucide="layers"></i></div>
            <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
            <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mês</span>
        </div>

        <!-- Overlay PRO (para free users) -->
        <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
            <div class="provisao-pro-content">
                <div class="provisao-pro-icon">
                    <i data-lucide="gem"></i>
                </div>
                <h3>Previsão Financeira</h3>
                <p>Veja quanto vai pagar, receber e como ficará seu saldo. Disponível no plano <strong>Pro</strong>.</p>
                <a href="<?= BASE_URL ?>planos" class="provisao-pro-btn">
                    <i data-lucide="rocket"></i> Conhecer o Pro
                </a>
            </div>
        </div>
    </section>

    <!-- Chart -->
    <section class="chart-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="chart-title">Evolução Financeira</h2>
        <div class="chart-wrapper">
            <div class="chart-loading" id="chartLoading"></div>
            <div id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></div>
        </div>
    </section>

    <!-- Table -->
    <section class="table-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="table-title">Últimos Lançamentos</h2>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">
                <i data-lucide="receipt" style="color: var(--color-primary)"></i>
            </div>
            <h3>Nenhum lançamento encontrado</h3>
            <p>Comece adicionando sua primeira transação para acompanhar suas finanças</p>
            <div class="empty-actions" style="display: flex; gap: 12px; margin-top: 20px; justify-content: center; flex-wrap: wrap;">
                <button class="btn-primary" onclick="if (window.fab) { window.fab.open(); } else { window.location.href = window.BASE_URL + 'lancamentos?tipo=receita'; }" style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    Adicionar Lançamento
                </button>
            </div>
            <p class="empty-hint" style="margin-top: 12px; font-size: 12px; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; gap: 6px;">
                <i data-lucide="lightbulb" style="width:14px;height:14px;"></i>
                Dica: Use o botão flutuante no canto inferior direito 👉
            </p>
        </div>

        <!-- Cards Mobile -->
        <div class="transactions-cards" id="transactionsCards"></div>

        <!-- Tabela Desktop -->
        <div class="table-wrapper">
            <table class="modern-table" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Conta</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr class="lk-loading-row">
                        <td colspan="7" style="text-align:center;padding:2rem 1rem;">
                            <div class="lk-loading-state">
                                <i data-lucide="loader-2"></i>
                                <p>Carregando transações...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</section>

<!-- Sprint 2: Health Score and Greeting Components -->
<?= vite_scripts('admin/dashboard/health-score.js') ?>
<?= vite_scripts('admin/dashboard/greeting.js') ?>
<?= vite_scripts('admin/dashboard/sprint2-loader.js') ?>
<?= vite_scripts('admin/dashboard/floating-action-button.js') ?>
<?= vite_scripts('admin/dashboard/progressive-disclosure.js') ?>
<?= vite_scripts('admin/dashboard/celebration.js') ?>

<!-- Gamification Dashboard JS (Vite) -->
<?= vite_scripts('admin/gamification-dashboard/index.js') ?>