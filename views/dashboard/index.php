<!-- Base URL e CSRF para o JS -->
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token('default') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<script>
// BASE_URL sempre com / no final
window.BASE_URL = "<?= rtrim(BASE_URL ?? '/', '/') . '/'; ?>";
</script>


<!-- Header -->
<header class="lk-header">
    <div class="header-left">
        <div class="month-selector">
            <button class="month-nav-btn" id="prevMonth" aria-label="Mês anterior">
                <i class="fas fa-chevron-left"></i>
            </button>

            <div class="month-display">
                <button class="month-dropdown-btn" id="monthDropdownBtn" aria-haspopup="true" aria-expanded="false">
                    <span id="currentMonthText"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="month-dropdown" id="monthDropdown" role="menu"></div>
            </div>

            <button class="month-nav-btn" id="nextMonth" aria-label="Próximo mês">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

</header>

<section>
    <!-- Conteúdo -->
    <div class="container">
        <!-- KPIs -->
        <section class="kpi-grid" role="region" aria-label="Indicadores principais">
            <div class="card kpi-card" id="saldoCard">
                <div class="card-header">
                    <div class="kpi-icon saldo"><i class="fas fa-wallet"></i></div>
                    <span class="kpi-title">Saldo Atual</span>
                </div>
                <div class="kpi-value" id="saldoValue">R$ 0,00</div>
            </div>

            <div class="card kpi-card" id="receitasCard">
                <div class="card-header">
                    <div class="kpi-icon receitas"><i class="fas fa-arrow-up"></i></div>
                    <span class="kpi-title">Receitas do Mês</span>
                </div>
                <div class="kpi-value receitas" id="receitasValue">R$ 0,00</div>
            </div>

            <div class="card kpi-card" id="despesasCard">
                <div class="card-header">
                    <div class="kpi-icon despesas"><i class="fas fa-arrow-down"></i></div>
                    <span class="kpi-title">Despesas do Mês</span>
                </div>
                <div class="kpi-value despesas" id="despesasValue">R$ 0,00</div>
            </div>
        </section>

        <!-- Gráfico + Resumo -->
        <section class="charts-grid">
            <div class="card chart-card">
                <div class="card-header">
                    <h2 class="card-title">Evolução Financeira</h2>
                </div>
                <div class="chart-container"><canvas id="evolutionChart" role="img"
                        aria-label="Gráfico de evolução do saldo"></canvas></div>
            </div>

            <div class="card summary-card">
                <div class="card-header">
                    <h2 class="card-title">Resumo Mensal</h2>
                </div>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Total Receitas</span><span
                            class="summary-value receitas" id="totalReceitas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Total Despesas</span><span
                            class="summary-value despesas" id="totalDespesas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Resultado</span><span class="summary-value"
                            id="resultadoMes">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Saldo Acumulado</span><span
                            class="summary-value" id="saldoAcumulado">R$ 0,00</span></div>
                </div>
            </div>
        </section>

        <!-- Tabela -->
        <section class="card table-card">
            <div class="card-header">
                <h2 class="card-title">Últimos Lançamentos</h2>
            </div>
            <div class="table-container">
                <div class="empty-state" id="emptyState" style="display:none;">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>Nenhum lançamento encontrado</h3>
                    <p>Adicione sua primeira transação clicando no botão + no canto inferior direito</p>
                </div>
                <table class="table" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Conta</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody"></tbody>
                </table>
            </div>
        </section>
    </div>

</section>