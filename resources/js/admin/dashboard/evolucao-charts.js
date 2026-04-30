/**
 * Evolução Financeira — Dashboard Widget
 * Dois charts: Mensal (barras diárias) e Anual (área 12 meses)
 */

import { apiGet } from '../shared/api.js';
import { resolveDashboardEvolutionEndpoint } from '../api/endpoints/dashboard.js';

class EvolucaoCharts {
    constructor(containerId = 'evolucaoChartsContainer') {
        this.container = document.getElementById(containerId);
        this._chartMensal = null;
        this._chartAnual = null;
        this._activeTab = 'mensal';
        this._currentMonth = null;
    }

    // ─── Público ───────────────────────────────────────────────────────────────

    init() {
        if (!this.container || this._initialized) return;
        this._initialized = true;

        this._render();
        this._loadAndDraw();

        document.addEventListener('lukrato:month-changed', (e) => {
            this._currentMonth = e?.detail?.month ?? null;
            this._loadAndDraw();
        });
        document.addEventListener('lukrato:data-changed', () => {
            this._loadAndDraw();
        });
    }

    // ─── Render shell ─────────────────────────────────────────────────────────

    _render() {
        this.container.innerHTML = `
      <div class="evo-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="evo-header">
          <div class="evo-title-group">
            <i data-lucide="trending-up" class="evo-title-icon"></i>
                        <div class="evo-title-stack">
                            <h2 class="evo-title">Fluxo do período</h2>
                            <p class="evo-subtitle">Entradas, saídas e resultado em contexto.</p>
                        </div>
          </div>
          <div class="evo-tabs" role="tablist">
            <button class="evo-tab evo-tab--active" data-tab="mensal" role="tab" aria-selected="true">Mensal</button>
            <button class="evo-tab" data-tab="anual" role="tab" aria-selected="false">Anual</button>
          </div>
        </div>

        <div class="evo-stats" id="evoStats">
          <div class="evo-stat">
            <span class="evo-stat__label">Entradas</span>
            <span class="evo-stat__value evo-stat__value--income" id="evoStatReceitas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Saídas</span>
            <span class="evo-stat__value evo-stat__value--expense" id="evoStatDespesas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Resultado</span>
            <span class="evo-stat__value" id="evoStatResultado">–</span>
          </div>
        </div>

        <div class="evo-chart-wrap">
          <div id="evoChartMensal" class="evo-chart"></div>
          <div id="evoChartAnual"  class="evo-chart" style="display:none;"></div>
        </div>
      </div>
    `;

        this.container.querySelectorAll('.evo-tab').forEach(btn => {
            btn.addEventListener('click', () => this._switchTab(btn.dataset.tab));
        });

        if (typeof window.lucide !== 'undefined') {
            window.lucide.createIcons({ attrs: { class: ['lucide'] } });
        }
    }

    // ─── Data ─────────────────────────────────────────────────────────────────

    async _loadAndDraw() {
        const month = this._currentMonth || this._detectMonth();

        try {
            const response = await apiGet(resolveDashboardEvolutionEndpoint(), { month });
            const data = response?.data ?? response;

            if (!data?.mensal) return;

            this._data = data;
            this._drawMensal(data.mensal);
            this._drawAnual(data.anual);
            this._updateStats(data);
        } catch {
            // silently ignore
        }
    }

    _detectMonth() {
        const sel = document.getElementById('monthSelector') || document.querySelector('[data-month]');
        return sel?.value || sel?.dataset?.month || new Date().toISOString().slice(0, 7);
    }

    // ─── Charts ───────────────────────────────────────────────────────────────

    _theme() {
        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        const style = getComputedStyle(document.documentElement);
        return {
            isDark,
            mode: isDark ? 'dark' : 'light',
            textMuted: style.getPropertyValue('--color-text-muted').trim() || (isDark ? '#94a3b8' : '#666'),
            gridColor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)',
            primary: style.getPropertyValue('--color-primary').trim() || '#E67E22',
            success: style.getPropertyValue('--color-success').trim() || '#2ecc71',
            danger: style.getPropertyValue('--color-danger').trim() || '#e74c3c',
            surface: isDark ? '#0f172a' : '#ffffff',
        };
    }

    _fmt(v) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v ?? 0);
    }

    _chartHeight() {
        return window.matchMedia('(max-width: 768px)').matches ? 176 : 188;
    }

    _drawMensal(mensal) {
        const el = document.getElementById('evoChartMensal');
        if (!el || !Array.isArray(mensal)) return;

        if (this._chartMensal) { this._chartMensal.destroy(); this._chartMensal = null; }

        const t = this._theme();
        const labels = mensal.map(d => d.label);
        const rec = mensal.map(d => +d.receitas);
        const desp = mensal.map(d => +d.despesas);

        this._chartMensal = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: this._chartHeight(),
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Inter, Arial, sans-serif',
                parentHeightOffset: 0,
                sparkline: { enabled: false },
                animations: { enabled: true, speed: 600 },
            },
            series: [
                { name: 'Entradas', data: rec },
                { name: 'Saídas', data: desp },
            ],
            xaxis: {
                categories: labels,
                tickAmount: 7,
                labels: {
                    rotate: 0,
                    style: { colors: t.textMuted, fontSize: '10px' },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: t.textMuted, fontSize: '10px' },
                    formatter: v => this._fmt(v),
                },
            },
            colors: [t.success, t.danger],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '70%',
                    dataLabels: { position: 'top' },
                },
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: t.gridColor,
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
            },
            tooltip: {
                theme: t.mode,
                shared: true,
                intersect: false,
                y: { formatter: v => this._fmt(v) },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: t.textMuted },
                markers: { shape: 'circle', size: 6 },
                fontSize: '12px',
            },
            theme: { mode: t.mode },
        });

        this._chartMensal.render();
    }

    _drawAnual(anual) {
        const el = document.getElementById('evoChartAnual');
        if (!el || !Array.isArray(anual)) return;

        if (this._chartAnual) { this._chartAnual.destroy(); this._chartAnual = null; }

        const t = this._theme();
        const labels = anual.map(d => d.label);
        const rec = anual.map(d => +d.receitas);
        const desp = anual.map(d => +d.despesas);
        const saldo = anual.map(d => +d.saldo);

        this._chartAnual = new ApexCharts(el, {
            chart: {
                type: 'line',
                height: this._chartHeight(),
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Inter, Arial, sans-serif',
                parentHeightOffset: 0,
                animations: { enabled: true, speed: 600 },
            },
            series: [
                { name: 'Entradas', type: 'column', data: rec },
                { name: 'Saídas', type: 'column', data: desp },
                { name: 'Saldo', type: 'area', data: saldo },
            ],
            xaxis: {
                categories: labels,
                labels: { style: { colors: t.textMuted, fontSize: '10px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: t.textMuted, fontSize: '10px' },
                    formatter: v => this._fmt(v),
                },
            },
            colors: [t.success, t.danger, t.primary],
            plotOptions: {
                bar: { borderRadius: 4, columnWidth: '55%' },
            },
            stroke: { curve: 'smooth', width: [0, 0, 2.5] },
            fill: {
                type: ['solid', 'solid', 'gradient'],
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.02,
                    stops: [0, 100],
                },
            },
            markers: {
                size: [0, 0, 4],
                hover: { size: 6 },
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: t.gridColor,
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
            },
            tooltip: {
                theme: t.mode,
                shared: true,
                intersect: false,
                y: { formatter: v => this._fmt(v) },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: t.textMuted },
                markers: { shape: 'circle', size: 6 },
                fontSize: '12px',
            },
            theme: { mode: t.mode },
        });

        this._chartAnual.render();
    }

    // ─── Stats strip ──────────────────────────────────────────────────────────

    _updateStats(data) {
        const isAnual = this._activeTab === 'anual';

        let rec = 0;
        let desp = 0;

        if (isAnual && data.anual?.length) {
            data.anual.forEach(d => { rec += +d.receitas; desp += +d.despesas; });
        } else if (data.mensal?.length) {
            data.mensal.forEach(d => { rec += +d.receitas; desp += +d.despesas; });
        }

        const resultado = rec - desp;

        const elRec = document.getElementById('evoStatReceitas');
        const elDesp = document.getElementById('evoStatDespesas');
        const elRes = document.getElementById('evoStatResultado');

        if (elRec) elRec.textContent = this._fmt(rec);
        if (elDesp) elDesp.textContent = this._fmt(desp);
        if (elRes) {
            elRes.textContent = this._fmt(resultado);
            elRes.className = 'evo-stat__value ' + (resultado >= 0 ? 'evo-stat__value--income' : 'evo-stat__value--expense');
        }
    }

    // ─── Tabs ─────────────────────────────────────────────────────────────────

    _switchTab(tab) {
        if (this._activeTab === tab) return;
        this._activeTab = tab;

        this.container.querySelectorAll('.evo-tab').forEach(btn => {
            const active = btn.dataset.tab === tab;
            btn.classList.toggle('evo-tab--active', active);
            btn.setAttribute('aria-selected', String(active));
        });

        const mensal = document.getElementById('evoChartMensal');
        const anual = document.getElementById('evoChartAnual');

        if (mensal) mensal.style.display = tab === 'mensal' ? '' : 'none';
        if (anual) anual.style.display = tab === 'anual' ? '' : 'none';

        if (this._data) this._updateStats(this._data);

        // trigger resize so ApexCharts re-paints correctly
        setTimeout(() => {
            if (tab === 'mensal' && this._chartMensal) this._chartMensal.windowResizeHandler?.();
            if (tab === 'anual' && this._chartAnual) this._chartAnual.windowResizeHandler?.();
        }, 10);
    }
}

window.EvolucaoCharts = EvolucaoCharts;
