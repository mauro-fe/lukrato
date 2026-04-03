    <!-- ==================== CARDS DE RESUMO RÁPIDO ==================== -->
    <div class="quick-stats-grid" id="relQuickStats">
        <div class="stat-card stat-receitas surface-card surface-card--interactive surface-card--clip"
            title="Total de entradas financeiras registradas neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Receitas do Mês</span>
                <span class="stat-value" id="totalReceitas">R$ 0,00</span>
                <span class="stat-hint">Total de entradas no período</span>
                <span class="stat-trend" id="trendReceitas"></span>
            </div>
        </div>

        <div class="stat-card stat-despesas surface-card surface-card--interactive surface-card--clip"
            title="Total de saídas e gastos registrados neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-down"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Despesas do Mês</span>
                <span class="stat-value" id="totalDespesas">R$ 0,00</span>
                <span class="stat-hint">Total de saídas no período</span>
                <span class="stat-trend" id="trendDespesas"></span>
            </div>
        </div>

        <div class="stat-card stat-saldo surface-card surface-card--interactive surface-card--clip"
            title="Diferença entre receitas e despesas (receitas - despesas)" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="wallet" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Saldo do Mês</span>
                <span class="stat-value" id="saldoMes">R$ 0,00</span>
                <span class="stat-hint">Receitas menos despesas</span>
                <span class="stat-trend" id="trendSaldo"></span>
            </div>
        </div>

        <div class="stat-card stat-cartoes surface-card surface-card--interactive surface-card--clip"
            title="Soma de todas as faturas de cartões de crédito neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="credit-card" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Faturas Cartões</span>
                <span class="stat-value" id="totalCartoes">R$ 0,00</span>
                <span class="stat-hint">Gastos em cartões de crédito</span>
                <span class="stat-trend" id="trendCartoes"></span>
            </div>
        </div>
    </div>
