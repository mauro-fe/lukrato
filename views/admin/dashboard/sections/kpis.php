<!-- ============================================================
         KPI ROW — 3 cards: Entradas · Saídas · Resultado
         ============================================================ -->
<section class="dash-kpis" role="region" aria-label="Indicadores do mês">
    <div class="dash-kpi dash-kpi--income surface-card surface-card--interactive" id="receitasCard">
        <div class="dash-kpi__icon dash-kpi__icon--income">
            <i data-lucide="arrow-down-left"></i>
        </div>
        <div class="dash-kpi__body">
            <span class="dash-kpi__label">Entradas</span>
            <span class="dash-kpi__value income loading" id="receitasValue">R$ 0,00</span>
        </div>
    </div>

    <div class="dash-kpi dash-kpi--expense surface-card surface-card--interactive" id="despesasCard">
        <div class="dash-kpi__icon dash-kpi__icon--expense">
            <i data-lucide="arrow-up-right"></i>
        </div>
        <div class="dash-kpi__body">
            <span class="dash-kpi__label">Saídas</span>
            <span class="dash-kpi__value expense loading" id="despesasValue">R$ 0,00</span>
        </div>
    </div>

    <div class="dash-kpi dash-kpi--result surface-card surface-card--interactive" id="saldoMesCard">
        <div class="dash-kpi__icon dash-kpi__icon--result">
            <i data-lucide="scale"></i>
        </div>
        <div class="dash-kpi__body">
            <span class="dash-kpi__label">Resultado</span>
            <span class="dash-kpi__value loading" id="saldoMesValue">R$ 0,00</span>
        </div>
    </div>
</section>