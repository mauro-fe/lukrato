<!-- ============================================================
         HERO — Saldo principal + variação do mês
         ============================================================ -->
<section class="dashboard-hero-section surface-card surface-card--interactive" id="saldoCard">
    <span class="dash-hero__label">Saldo atual</span>
    <div class="dash-hero__balance kpi-value loading" id="saldoValue">R$ 0,00</div>
    <div class="dash-hero__variation" id="dashboardHeroStatus"></div>

    <!-- Mini sparkline — evolução do saldo 6 meses -->
    <div class="dash-hero__sparkline" id="heroSparkline"></div>

    <!-- Frase contextual de economia -->
    <p class="dash-hero__context" id="heroContext" style="display:none;"></p>

    <!-- Mensagem oculta — usada pelo JS para narrativa -->
    <p class="dash-hero__message" id="dashboardHeroMessage" style="display:none;"></p>
</section>