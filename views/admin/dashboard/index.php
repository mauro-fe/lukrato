<?php
$onboardingCompleted = !empty($currentUser?->onboarding_completed_at);
?>

<?php if (!empty($showOnboardingCongrats)): ?>
    <script>
        window.__lkFirstVisit = true;
    </script>
<?php endif; ?>

<section class="modern-dashboard">
    <section class="dashboard-hero-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-hero-card" id="saldoCard">
            <div class="dashboard-hero-header">
                <div class="dashboard-hero-copy">
                    <span class="dashboard-hero-eyebrow">Resumo do mes</span>
                    <h1 class="dashboard-hero-title">Quanto voce tem hoje</h1>
                    <div class="dashboard-balance-value kpi-value loading" id="saldoValue">R$ 0,00</div>
                    <div class="dashboard-status-chip" id="dashboardHeroStatus">Carregando seu mes</div>
                    <p class="dashboard-hero-message" id="dashboardHeroMessage">
                        Seus principais numeros aparecem aqui para voce entender seu momento em segundos.
                    </p>
                </div>

                <aside class="dashboard-hero-side">
                    <div id="greetingContainer" class="dashboard-hero-greeting-slot"></div>

                    <?php if (!$onboardingCompleted): ?>
                        <div class="lk-onboarding-widget" id="onboardingChecklist" style="display:none;">
                            <div class="lk-onboarding-widget-head">
                                <a href="<?= BASE_URL ?>onboarding" class="lk-onboarding-widget-title" id="checklistPrimaryLink">
                                    <span>Complete seu setup</span>
                                    <strong id="checklistBadge">0/0</strong>
                                </a>
                                <button class="lk-onboarding-widget-dismiss" id="checklistDismiss" type="button" title="Ocultar widget">
                                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                                </button>
                            </div>
                            <div class="lk-onboarding-widget-progress">
                                <div class="lk-onboarding-widget-progress-fill" id="checklistProgressFill"></div>
                            </div>
                            <div class="lk-onboarding-widget-items" id="checklistItems">
                                <a href="<?= BASE_URL ?>contas" class="lk-onboarding-widget-item">
                                    <span class="lk-onboarding-widget-item-label">Adicione sua primeira conta</span>
                                    <span class="lk-onboarding-widget-item-desc">Conecte sua base financeira.</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>

            <div class="kpi-grid dashboard-summary-grid" role="region" aria-label="Indicadores principais">
                <div class="modern-kpi dashboard-summary-card dashboard-summary-card--income" id="receitasCard">
                    <div class="kpi-header">
                        <div class="kpi-icon income">
                            <i data-lucide="arrow-down-left"></i>
                        </div>
                        <span class="kpi-label">Entrou esse mes</span>
                    </div>
                    <div class="kpi-value income loading" id="receitasValue">R$ 0,00</div>
                </div>

                <div class="modern-kpi dashboard-summary-card dashboard-summary-card--expense" id="despesasCard">
                    <div class="kpi-header">
                        <div class="kpi-icon expense">
                            <i data-lucide="arrow-up-right"></i>
                        </div>
                        <span class="kpi-label">Voce gastou</span>
                    </div>
                    <div class="kpi-value expense loading" id="despesasValue">R$ 0,00</div>
                </div>

                <div class="modern-kpi dashboard-summary-card dashboard-summary-card--result" id="saldoMesCard">
                    <div class="kpi-header">
                        <div class="kpi-icon balance">
                            <i data-lucide="scale" style="color: white"></i>
                        </div>
                        <span class="kpi-label">Sobrou no mes</span>
                    </div>
                    <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-alerts-section" id="dashboardAlertsSection" style="display:none;" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-section-eyebrow">Alertas</span>
                <h2 class="dashboard-section-title">O que precisa da sua atencao agora</h2>
            </div>
        </div>
        <div class="dashboard-alerts-list">
            <div id="dashboardAlertsOverview"></div>
            <div id="dashboardAlertsBudget"></div>
        </div>
    </section>

    <div id="healthScoreContainer"></div>
    <div id="healthScoreInsights" class="health-score-insights-section"></div>

    <section class="provisao-section" id="provisaoSection" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-section-eyebrow">Previsao financeira</span>
                <h2 class="dashboard-section-title" id="provisaoTitle">Se continuar assim, voce termina o mes com R$ 0,00</h2>
                <p class="dashboard-section-copy" id="provisaoHeadline">
                    O Lukrato considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair.
                </p>
            </div>
        </div>

        <div class="provisao-alerts-container" id="provisaoAlertsContainer">
            <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="triangle-alert" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertDespesasCount">0</strong> conta(s) vencida(s) somando
                    <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>lancamentos?tipo=despesa&pago=0" class="provisao-alert-link">Ver <i
                        data-lucide="arrow-right"></i></a>
            </div>
            <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="info" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertReceitasCount">0</strong> recebimento(s) atrasado(s) somando
                    <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>lancamentos?tipo=receita&pago=0" class="provisao-alert-link">Ver <i
                        data-lucide="arrow-right"></i></a>
            </div>
            <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="credit-card" style="color:#fff"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) somando
                    <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">Ver <i data-lucide="arrow-right"></i></a>
            </div>
        </div>

        <div class="provisao-grid">
            <div class="provisao-card pagar">
                <div class="provisao-card-icon"><i data-lucide="arrow-up"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Vai sair</span>
                    <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPagarCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card receber">
                <div class="provisao-card-icon"><i data-lucide="arrow-down"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Vai entrar</span>
                    <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoReceberCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card projetado">
                <div class="provisao-card-icon"><i data-lucide="line-chart" style="color:#fff"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Como voce fecha o mes</span>
                    <span class="provisao-card-value" id="provisaoProjetado">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoProjetadoLabel">saldo atual + previsao</span>
                </div>
            </div>
        </div>

        <div class="provisao-proximos">
            <div class="provisao-proximos-header">
                <span class="provisao-proximos-title" id="provisaoProximosTitle"><i data-lucide="clock"></i> Proximos
                    vencimentos</span>
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

        <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
            <div class="provisao-parcelas-icon"><i data-lucide="layers"></i></div>
            <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
            <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mes</span>
        </div>

        <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
            <div class="provisao-pro-content">
                <div class="provisao-pro-icon">
                    <i data-lucide="gem"></i>
                </div>
                <h3>Previsao financeira</h3>
                <p>Veja quanto ainda vai pagar, receber e como seu mes pode terminar. Disponivel no plano <strong>Pro</strong>.</p>
                <a href="<?= BASE_URL ?>planos" class="provisao-pro-btn">
                    <i data-lucide="rocket"></i> Conhecer o Pro
                </a>
            </div>
        </div>
    </section>

    <div id="financeOverviewContainer"></div>

    <section class="chart-section" id="chart-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-section-eyebrow">Historico</span>
                <h2 class="dashboard-section-title">Evolucao financeira</h2>
                <p class="dashboard-section-copy" id="chartInsight">Seu pior mes aparece aqui assim que o historico carregar.</p>
            </div>
        </div>
        <div class="chart-wrapper">
            <div class="chart-loading" id="chartLoading"></div>
            <div id="evolutionChart" role="img" aria-label="Grafico de evolucao do saldo"></div>
        </div>
    </section>

    <section class="table-section" id="table-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-section-eyebrow">Movimentacao recente</span>
                <h2 class="dashboard-section-title">Seus ultimos gastos</h2>
                <p class="dashboard-section-copy">Inclui seus registros mais recentes do mes para voce agir rapido.</p>
            </div>
        </div>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">
                <i data-lucide="receipt" style="color: var(--color-primary)"></i>
            </div>
            <h3>Nenhum gasto recente encontrado</h3>
            <p>Comece registrando sua primeira movimentacao para acompanhar melhor seu dinheiro.</p>
            <div class="empty-actions"
                style="display: flex; gap: 12px; margin-top: 20px; justify-content: center; flex-wrap: wrap;">
                <button class="btn-primary"
                    onclick="if (window.fab) { window.fab.open(); } else { window.location.href = window.BASE_URL + 'lancamentos?tipo=receita'; }"
                    style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    Adicionar registro
                </button>
            </div>
            <p class="empty-hint"
                style="margin-top: 12px; font-size: 12px; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; gap: 6px;">
                <i data-lucide="lightbulb" style="width:14px;height:14px;"></i>
                Dica: use o botao flutuante no canto inferior direito.
            </p>
        </div>

        <div class="transactions-cards" id="transactionsCards"></div>

        <div class="table-wrapper">
            <table class="modern-table" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Conta</th>
                        <th>Descricao</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Acoes</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr class="lk-loading-row">
                        <td colspan="7" style="text-align:center;padding:2rem 1rem;">
                            <div class="lk-loading-state">
                                <i data-lucide="loader-2"></i>
                                <p>Carregando movimentacoes...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="gamification-section" data-aos="fade-up" data-aos-duration="500">
        <div class="gamification-header">
            <div class="gamification-title">
                <i data-lucide="trophy"></i>
                <span>Seu progresso</span>
                <span class="pro-badge" id="proBadge" style="display: none;">
                    <i data-lucide="gem"></i> PRO
                </span>
            </div>
            <div class="level-badge" id="userLevel">
                <i data-lucide="star" style="color: white"></i>
                <span>Nivel 1</span>
            </div>
        </div>

        <div class="gamification-grid">
            <div class="streak-card">
                <div class="streak-icon"><i data-lucide="flame"
                        style="width:28px;height:28px;color:var(--color-warning,#f59e0b);"></i></div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias ativos</div>
                <div class="streak-protection" id="streakProtection" style="display: none;">
                    <i data-lucide="shield"></i>
                    <span>Protecao disponivel</span>
                </div>
            </div>

            <div class="level-progress-card">
                <div class="level-progress-header">
                    <div class="level-progress-label">
                        <i data-lucide="bar-chart-3"></i>
                        <span>Progresso para o proximo nivel</span>
                    </div>

                    <span class="level-progress-points" id="levelProgressPoints">0 / 300 pontos</span>
                </div>
                <div class="level-progress-bar-container">
                    <div class="level-progress-bar" id="levelProgressBar" style="width: 0%"></div>
                </div>
                <div class="level-progress-text" id="levelProgressText">Ganhe mais pontos para avancar.</div>
            </div>

            <div class="badges-card">
                <div class="badges-title">
                    <i data-lucide="medal"></i>
                    <span>Conquistas</span>
                    <a href="<?= BASE_URL ?>gamification" class="btn-view-all">Ver todas</a>
                </div>
                <div class="badges-grid" id="badgesGrid">
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                    <div class="lk-skeleton lk-skeleton--badge"></div>
                </div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalLancamentos">0</div>
                <div class="stat-mini-label">Registros</div>
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

        <div class="pro-cta-card" id="proCTA" style="display: none;">
            <div class="pro-cta-content">
                <div class="pro-cta-icon">
                    <i data-lucide="rocket"></i>
                </div>
                <div class="pro-cta-text">
                    <h3>Acelere seu progresso com o Plano Pro</h3>
                    <p>Ganhe 1.5x mais pontos, protecao de streak e conquistas exclusivas.</p>
                </div>
                <button class="btn-pro-upgrade">
                    <i data-lucide="gem"></i>
                    Conhecer o Pro
                </button>
            </div>
        </div>
    </section>
</section>

<?= vite_scripts('admin/gamification-dashboard/index.js') ?>
