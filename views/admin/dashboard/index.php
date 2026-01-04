<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<section class="modern-dashboard">
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- Gamificação -->
    <section class="gamification-section" data-aos="fade-up" data-aos-duration="500">
        <div class="gamification-header">
            <div class="gamification-title">
                <i class="fas fa-trophy"></i>
                <span>Seu Progresso</span>
                <span class="pro-badge" id="proBadge" style="display: none;">
                    <i class="fas fa-gem"></i> PRO
                </span>
            </div>
            <div class="level-badge" id="userLevel">
                <i class="fas fa-star"></i>
                <span>Nível 1</span>
            </div>
        </div>

        <div class="gamification-grid">
            <!-- Streak -->
            <div class="streak-card">
                <div class="streak-icon">🔥</div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias Consecutivos</div>
                <div class="streak-protection" id="streakProtection" style="display: none;">
                    <i class="fas fa-shield-alt"></i>
                    <span>Proteção disponível</span>
                </div>
            </div>

            <!-- Progresso -->
            <div class="level-progress-card">
                <div class="level-progress-header">
                    <span class="level-progress-label">Progresso para próximo nível</span>
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
                    <i class="fas fa-medal"></i>
                    <span>Conquistas</span>
                    <a href="<?= BASE_URL ?>gamification" class="btn-view-all">Ver todas</a>
                </div>
                <div class="badges-grid" id="badgesGrid">
                    <!-- Preenchido via JS -->
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
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
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="pro-cta-text">
                    <h3>Acelere seu progresso com o Plano Pro</h3>
                    <p>Ganhe 1.5x mais pontos, proteção de streak e conquistas exclusivas!</p>
                </div>
                <button class="btn-pro-upgrade">
                    <i class="fas fa-gem"></i>
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
                        <i class="fas fa-wallet"></i>
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
                        <i class="fas fa-arrow-up"></i>
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
                        <i class="fas fa-arrow-down"></i>
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
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <span class="kpi-label">Saldo do Mês</span>
                </div>
                <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <!-- Chart -->
    <section class="chart-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="chart-title">Evolução Financeira</h2>
        <div class="chart-wrapper">
            <div class="chart-loading" id="chartLoading"></div>
            <canvas id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></canvas>
        </div>
    </section>

    <!-- Table -->
    <section class="table-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="table-title">Últimos Lançamentos</h2>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <h3>Nenhum lançamento encontrado</h3>
            <p>Comece adicionando sua primeira transação para acompanhar suas finanças</p>
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
                <tbody id="transactionsTableBody"></tbody>
            </table>
        </div>
    </section>
</section>

<!-- Gamification JS -->
<script>
    // Define BASE_URL global para gamification script
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/gamification-dashboard.js?v=<?= time() ?>"></script>