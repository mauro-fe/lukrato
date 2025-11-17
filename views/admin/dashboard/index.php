<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<section class="dashboard-page">
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- KPIs -->
    <section class="kpi-grid" role="region" aria-label="Indicadores principais">
        <div data-aos="flip-left">
            <div class="card kpi-card" id="saldoCard">
                <div class="card-header">
                    <div class="kpi-icon saldo">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="kpi-title">Saldo Atual</span>
                </div>
                <div class="kpi-value loading" id="saldoValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="flip-left" data-aos-delay="100">
            <div class="card kpi-card" id="receitasCard">
                <div class="card-header">
                    <div class="kpi-icon receitas">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <span class="kpi-title">Receitas do Mês</span>
                </div>
                <div class="kpi-value receitas loading" id="receitasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="flip-right" data-aos-delay="200">
            <div class="card kpi-card" id="despesasCard">
                <div class="card-header">
                    <div class="kpi-icon despesas">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <span class="kpi-title">Despesas do Mês</span>
                </div>
                <div class="kpi-value despesas loading" id="despesasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="flip-right" data-aos-delay="300">
            <div class="card kpi-card" id="saldoMesCard">
                <div class="card-header">
                    <div class="kpi-icon saldo">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <span class="kpi-title">Saldo do Mês</span>
                </div>
                <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <!-- Gráfico -->
    <section class="charts-grid" data-aos="zoom-in">
        <div class="card chart-card">
            <div class="card-header">
                <h2 class="card-title">Evolução Financeira</h2>
            </div>
            <div class="chart-container">
                <div class="chart-loading" id="chartLoading"></div>
                <canvas id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></canvas>
            </div>
        </div>
    </section>

    <!-- Últimos Lançamentos -->
    <section class="card table-card mb-5" data-aos="fade-up">
        <div class="card-header">
            <h2 class="card-title">Últimos Lançamentos</h2>
        </div>

        <div class="table-container">
            <div class="empty-state" id="emptyState" style="display:none;">
                <div class="empty-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3>Nenhum lançamento encontrado</h3>
                <p>Comece adicionando sua primeira transação para acompanhar suas finanças</p>
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
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody"></tbody>
            </table>
        </div>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>