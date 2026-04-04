/**
 * ============================================================================
 * LUKRATO — Dashboard / App (Main Application Logic)
 * ============================================================================
 * API layer, Notifications, Gamification, Renderers, TransactionManager,
 * Provisao (financial forecasting), DashboardManager, and EventListeners.
 * Registered as Modules.App.
 * ============================================================================
 */

import {
    CONFIG,
    DOM,
    STATE,
    Utils,
    Modules,
    getThemeColors,
    escapeHtml
} from './state.js';
import { apiDelete, apiGet, apiPost, getApiPayload, getErrorMessage, logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';
import { getDashboardPrimaryActionCopy, openPrimaryAction, resolvePrimaryActionMeta } from '../shared/primary-actions.js';
import { createOptionalWidgets } from './app-optional-widgets.js';
import { createProvisao } from './app-provisao.js';
import { createDashboardFoundation } from './app-foundation.js';
import { createDashboardRuntime } from './app-runtime.js';

// ==================== FOUNDATION ====================

export const { API, Notifications, Gamification } = createDashboardFoundation({
    CONFIG,
    getDashboardOverview,
    getApiPayload,
    apiGet,
    apiDelete,
    apiPost,
    getErrorMessage,
});

// ==================== RENDERERS ====================

export const Renderers = {
    updateMonthLabel: (month) => {
        if (!DOM.monthLabel) return;
        DOM.monthLabel.textContent = Utils.formatMonth(month);
    },

    toggleAlertsSection: () => {
        // Alerts section is hidden in the new fintech layout
        const section = document.getElementById('dashboardAlertsSection');
        if (section) section.style.display = 'none';
    },

    setSignedState: (elementId, cardId, value) => {
        const element = document.getElementById(elementId);
        const card = document.getElementById(cardId);
        if (!element || !card) return;

        element.classList.remove('is-positive', 'is-negative', 'income', 'expense');
        card.classList.remove('is-positive', 'is-negative');

        if (value > 0) {
            element.classList.add('is-positive');
            card.classList.add('is-positive');
        } else if (value < 0) {
            element.classList.add('is-negative');
            card.classList.add('is-negative');
        }
    },

    formatSignedMoney: (value) => {
        const amount = Number(value || 0);
        const prefix = amount >= 0 ? '+' : '-';
        return `${prefix}${Utils.money(Math.abs(amount))}`;
    },

    renderStatusChip: (element, icon, text) => {
        if (!element) return;

        element.innerHTML = `
            <i data-lucide="${icon}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${text}</span>
        `;

        if (typeof window.lucide !== 'undefined') {
            window.lucide.createIcons();
        }
    },

    renderHeroNarrative: ({ saldo, receitas, despesas, resultado }) => {
        const statusEl = document.getElementById('dashboardHeroStatus');
        const messageEl = document.getElementById('dashboardHeroMessage');
        const receitasValue = Number(receitas || 0);
        const despesasValue = Number(despesas || 0);
        const resultadoValue = Number.isFinite(Number(resultado))
            ? Number(resultado)
            : receitasValue - despesasValue;

        if (!statusEl || !messageEl) return;

        statusEl.className = 'dashboard-status-chip';
        messageEl.className = 'dashboard-hero-message';

        if (despesasValue > receitasValue) {
            statusEl.classList.add('dashboard-status-chip--negative');
            messageEl.classList.add('dashboard-hero-message--negative');
            Renderers.renderStatusChip(statusEl, 'triangle-alert', `M\u00eas no vermelho (${Renderers.formatSignedMoney(resultadoValue)})`);
            messageEl.textContent = `Aten\u00e7\u00e3o: voc\u00ea gastou mais do que ganhou (${Renderers.formatSignedMoney(resultadoValue)}).`;
            return;
        }

        if (resultadoValue > 0) {
            statusEl.classList.add('dashboard-status-chip--positive');
            messageEl.classList.add('dashboard-hero-message--positive');
            Renderers.renderStatusChip(
                statusEl,
                saldo >= 0 ? 'piggy-bank' : 'trending-up',
                saldo >= 0
                    ? `M\u00eas positivo (${Renderers.formatSignedMoney(resultadoValue)})`
                    : `Recuperando o m\u00eas (${Renderers.formatSignedMoney(resultadoValue)})`
            );
            messageEl.textContent = `Voc\u00ea est\u00e1 positivo este m\u00eas (${Renderers.formatSignedMoney(resultadoValue)}).`;
            return;
        }

        if (resultadoValue === 0) {
            statusEl.classList.add('dashboard-status-chip--neutral');
            Renderers.renderStatusChip(statusEl, 'scale', 'M\u00eas zerado (R$ 0,00)');
            messageEl.textContent = `Entrou ${Utils.money(receitasValue)} e saiu ${Utils.money(despesasValue)}. Seu saldo do m\u00eas est\u00e1 em R$ 0,00.`;
            return;
        }

        statusEl.classList.add('dashboard-status-chip--negative');
        messageEl.classList.add('dashboard-hero-message--negative');
        Renderers.renderStatusChip(statusEl, 'wallet', `Resultado do m\u00eas ${Renderers.formatSignedMoney(resultadoValue)}`);
        messageEl.textContent = `Seu resultado mensal est\u00e1 em ${Renderers.formatSignedMoney(resultadoValue)}. Vale rever os gastos mais pesados agora.`;
    },

    renderHeroSparkline: async (month) => {
        const container = document.getElementById('heroSparkline');
        if (!container || typeof ApexCharts === 'undefined') return;

        try {
            const overview = await API.getOverview(month);
            const chart = Array.isArray(overview.chart) ? overview.chart : [];
            if (chart.length < 2) { container.innerHTML = ''; return; }

            const series = chart.map(c => Number(c.resultado || 0));
            const { isLightTheme } = getThemeColors();
            const lastVal = series[series.length - 1] || 0;
            const lineColor = lastVal >= 0 ? '#10b981' : '#ef4444';

            if (STATE._heroSparkInstance) {
                STATE._heroSparkInstance.destroy();
                STATE._heroSparkInstance = null;
            }

            STATE._heroSparkInstance = new ApexCharts(container, {
                chart: { type: 'area', height: 48, sparkline: { enabled: true }, background: 'transparent' },
                series: [{ data: series }],
                stroke: { width: 2, curve: 'smooth', colors: [lineColor] },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0, stops: [0, 100], colorStops: [{ offset: 0, color: lineColor, opacity: 0.25 }, { offset: 100, color: lineColor, opacity: 0 }] }
                },
                tooltip: {
                    enabled: true,
                    fixed: { enabled: false },
                    x: { show: false },
                    y: { formatter: (v) => Utils.money(v), title: { formatter: () => '' } },
                    theme: isLightTheme ? 'light' : 'dark',
                },
                colors: [lineColor],
            });
            STATE._heroSparkInstance.render();
        } catch { /* silent */ }
    },

    renderHeroContext: ({ receitas, despesas }) => {
        const el = document.getElementById('heroContext');
        if (!el) return;

        const r = Number(receitas || 0);
        const d = Number(despesas || 0);

        if (r <= 0) { el.style.display = 'none'; return; }

        const rate = ((r - d) / r) * 100;
        let icon, text, cls;

        if (rate >= 20) {
            icon = 'piggy-bank';
            text = `Você está economizando ${Math.round(rate)}% da renda — excelente!`;
            cls = 'dash-hero__context--positive';
        } else if (rate >= 1) {
            icon = 'target';
            text = `Economia de ${Math.round(rate)}% da renda — meta ideal é 20%.`;
            cls = 'dash-hero__context--neutral';
        } else {
            icon = 'alert-triangle';
            text = `Sem margem de economia este mês. Revise seus gastos.`;
            cls = 'dash-hero__context--negative';
        }

        el.className = `dash-hero__context ${cls}`;
        el.innerHTML = `<i data-lucide="${icon}" style="width:14px;height:14px;"></i> ${text}`;
        el.style.display = '';

        if (typeof window.lucide !== 'undefined') window.lucide.createIcons();
    },

    renderOverviewAlerts: ({ receitas, despesas }) => {
        const container = document.getElementById('dashboardAlertsOverview');
        if (!container) return;

        // Keep alerts section hidden in the new fintech layout
        const section = document.getElementById('dashboardAlertsSection');
        if (section) section.style.display = 'none';

        const receitasValue = Number(receitas || 0);
        const despesasValue = Number(despesas || 0);
        const resultadoValue = receitasValue - despesasValue;

        if (despesasValue > receitasValue) {
            container.innerHTML = `
                <a href="${CONFIG.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Aten\u00e7\u00e3o: voc\u00ea gastou mais do que ganhou</strong>
                        <span>Entrou ${Utils.money(receitasValue)} e saiu ${Utils.money(despesasValue)}. Diferen\u00e7a do m\u00eas: ${Renderers.formatSignedMoney(resultadoValue)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `;

            if (typeof window.lucide !== 'undefined') {
                window.lucide.createIcons();
            }
        } else {
            container.innerHTML = '';
        }

        Renderers.toggleAlertsSection();
    },

    renderChartInsight: (months, data) => {
        const insightEl = document.getElementById('chartInsight');
        if (!insightEl) return;

        if (!Array.isArray(data) || data.length === 0 || data.every((value) => Number(value) === 0)) {
            insightEl.textContent = 'Seu historico aparece aqui conforme você usa o Lukrato mais vezes.';
            return;
        }

        let worstIndex = 0;
        data.forEach((value, index) => {
            if (Number(value) < Number(data[worstIndex])) {
                worstIndex = index;
            }
        });

        const worstMonth = months[worstIndex];
        const worstValue = Number(data[worstIndex] || 0);

        if (worstValue < 0) {
            insightEl.textContent = `Seu pior mes foi ${Utils.formatMonth(worstMonth)} (${Utils.money(worstValue)}).`;
            return;
        }

        insightEl.textContent = `Seu pior mes foi ${Utils.formatMonth(worstMonth)} e mesmo assim fechou em ${Utils.money(worstValue)}.`;
    },

    renderKPIs: async (month) => {
        try {
            const overview = await API.getOverview(month);
            const metrics = overview?.metrics || {};
            const accounts = Array.isArray(overview?.accounts_balances) ? overview.accounts_balances : [];
            const previewMeta = overview?.meta || {};

            const kpiMap = {
                receitasValue: metrics.receitas || 0,
                despesasValue: metrics.despesas || 0,
                saldoMesValue: metrics.resultado || 0
            };

            Object.entries(kpiMap).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = Utils.money(value);
                }
            });

            const saldoAcumulado = Number(metrics.saldoAcumulado ?? metrics.saldo ?? 0);
            const totalSaldoContas = (Array.isArray(accounts) ? accounts : []).reduce((sum, account) => {
                const value = (typeof account.saldoAtual === 'number') ?
                    account.saldoAtual :
                    (account.saldoInicial || 0);
                return sum + (isFinite(value) ? Number(value) : 0);
            }, 0);

            const saldoFinal = (Array.isArray(accounts) && accounts.length > 0)
                ? totalSaldoContas
                : saldoAcumulado;

            if (DOM.saldoValue) {
                DOM.saldoValue.textContent = Utils.money(saldoFinal);
            }

            Renderers.setSignedState('saldoValue', 'saldoCard', saldoFinal);
            Renderers.setSignedState('saldoMesValue', 'saldoMesCard', Number(metrics.resultado || 0));
            Renderers.renderHeroNarrative({
                saldo: saldoFinal,
                receitas: Number(metrics.receitas || 0),
                despesas: Number(metrics.despesas || 0),
                resultado: Number(metrics.resultado || 0)
            });
            Renderers.renderHeroSparkline(month);
            Renderers.renderHeroContext({
                receitas: Number(metrics.receitas || 0),
                despesas: Number(metrics.despesas || 0),
            });
            Renderers.renderOverviewAlerts({
                receitas: Number(metrics.receitas || 0),
                despesas: Number(metrics.despesas || 0)
            });

            const realTransactionCount = Number(previewMeta?.real_transaction_count ?? metrics.count ?? 0);
            const realCategoryCount = Number(previewMeta?.real_category_count ?? metrics.categories ?? 0);
            const realAccountCount = Number(previewMeta?.real_account_count ?? accounts.length ?? 0);
            const primaryAction = resolvePrimaryActionMeta(previewMeta, {
                accountCount: realAccountCount,
            });

            document.dispatchEvent(new CustomEvent('lukrato:dashboard-overview-rendered', {
                detail: {
                    month,
                    accountCount: realAccountCount,
                    transactionCount: realTransactionCount,
                    categoryCount: realCategoryCount,
                    hasData: realTransactionCount > 0,
                    primaryAction: primaryAction.actionType,
                    ctaLabel: primaryAction.ctaLabel,
                    ctaUrl: primaryAction.ctaUrl,
                    isDemo: !!previewMeta?.is_demo,
                }
            }));

            Utils.removeLoadingClass();
        } catch (err) {
            logClientError('Erro ao renderizar KPIs', err, 'Falha ao carregar indicadores');
            ['saldoValue', 'receitasValue', 'despesasValue', 'saldoMesValue'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = 'R$ 0,00';
                    element.classList.remove('loading');
                }
            });
        }
    },

    renderTable: async (month) => {
        try {
            const transactions = await API.getTransactions(month, CONFIG.TRANSACTIONS_LIMIT);

            // Limpar ambos (containers ocultos — só populam dados para compatibilidade)
            if (DOM.tableBody) DOM.tableBody.innerHTML = '';
            if (DOM.cardsContainer) DOM.cardsContainer.innerHTML = '';

            const hasData = Array.isArray(transactions) && transactions.length > 0;

            if (hasData) {
                transactions.forEach(transaction => {
                    const tipo = String(transaction.tipo || '').toLowerCase();
                    const tipoClass = Utils.getTipoClass(tipo);
                    const tipoLabel = String(transaction.tipo || '').replace(/_/g, ' ');

                    const categoriaNome = transaction.categoria_nome ??
                        (typeof transaction.categoria === 'string' ? transaction.categoria :
                            transaction.categoria?.nome) ??
                        null;

                    const categoriaDisplay = categoriaNome
                        ? escapeHtml(categoriaNome)
                        : '<span class="categoria-empty">Sem categoria</span>';

                    const contaNome = escapeHtml(Utils.getContaLabel(transaction));
                    const descricao = escapeHtml(transaction.descricao || '--');
                    const tipoLabelSafe = escapeHtml(tipoLabel);
                    const valor = Number(transaction.valor) || 0;
                    const dataBR = Utils.dateBR(transaction.data);

                    // TABELA DESKTOP
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-id', transaction.id);

                    tr.innerHTML = `
              <td data-label="Data">${dataBR}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${tipoClass}">${tipoLabelSafe}</span>
              </td>
              <td data-label="Categoria">${categoriaDisplay}</td>
              <td data-label="Conta">${contaNome}</td>
              <td data-label="Descrição">${descricao}</td>
              <td data-label="Valor" class="valor-cell ${tipoClass}">${Utils.money(valor)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${transaction.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `;

                    if (DOM.tableBody) {
                        DOM.tableBody.appendChild(tr);
                    }

                    // CARDS MOBILE
                    if (DOM.cardsContainer) {
                        const card = document.createElement('div');
                        card.className = 'transaction-card';
                        card.setAttribute('data-id', transaction.id);

                        card.innerHTML = `
                <div class="transaction-card-header">
                  <span class="transaction-date">${dataBR}</span>
                  <span class="transaction-value ${tipoClass}">${Utils.money(valor)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${tipoClass}">${tipoLabelSafe}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${categoriaDisplay}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${contaNome}</span>
                  </div>
                  ${descricao !== '--' ? `
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${descricao}</span>
                  </div>
                  ` : ''}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${transaction.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `;

                        DOM.cardsContainer.appendChild(card);
                    }
                });
            }
        } catch (err) {
            logClientError('Erro ao renderizar transações', err, 'Falha ao carregar transações');
        }
    },

    renderTransactionsList: async (month) => {
        if (!DOM.transactionsList) return;

        try {
            const transactions = await API.getTransactions(month, CONFIG.TRANSACTIONS_LIMIT);
            const hasData = Array.isArray(transactions) && transactions.length > 0;

            DOM.transactionsList.innerHTML = '';

            if (DOM.emptyState) {
                DOM.emptyState.style.display = hasData ? 'none' : 'flex';
            }

            if (!hasData) return;

            // Group transactions by date
            const today = new Date().toISOString().slice(0, 10);
            const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
            const grouped = new Map();

            transactions.forEach(tx => {
                const d = String(tx.data || '').split(/[T\s]/)[0];
                if (!grouped.has(d)) grouped.set(d, []);
                grouped.get(d).push(tx);
            });

            for (const [date, txs] of grouped) {
                let label;
                if (date === today) label = 'Hoje';
                else if (date === yesterday) label = 'Ontem';
                else label = Utils.dateBR(date);

                const header = document.createElement('div');
                header.className = 'dash-tx-date-group';
                header.textContent = label;
                DOM.transactionsList.appendChild(header);

                txs.forEach(tx => {
                    const tipo = String(tx.tipo || '').toLowerCase();
                    const isIncome = tipo === 'receita';
                    const descricao = escapeHtml(tx.descricao || '--');
                    const categoriaNome = tx.categoria_nome ??
                        (typeof tx.categoria === 'string' ? tx.categoria : tx.categoria?.nome) ?? 'Sem categoria';
                    const valor = Number(tx.valor) || 0;
                    const isPago = Boolean(tx.pago);
                    const catIcon = tx.categoria_icone || (isIncome ? 'arrow-down-left' : 'arrow-up-right');

                    const el = document.createElement('div');
                    el.className = 'dash-tx-item surface-card';
                    el.setAttribute('data-id', tx.id);
                    el.innerHTML = `
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${isIncome ? 'income' : 'expense'}">
                                <i data-lucide="${escapeHtml(catIcon)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${descricao}</span>
                                <span class="dash-tx__category">${escapeHtml(categoriaNome)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${isIncome ? 'income' : 'expense'}">${isIncome ? '+' : '-'}${Utils.money(Math.abs(valor))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${isPago ? 'paid' : 'pending'}">${isPago ? 'Pago' : 'Pendente'}</span>
                        </div>
                    `;
                    DOM.transactionsList.appendChild(el);
                });
            }

            if (typeof window.lucide !== 'undefined') {
                window.lucide.createIcons();
            }
        } catch (err) {
            logClientError('Erro ao renderizar lista de transações', err, 'Falha ao carregar transações');
            if (DOM.emptyState) DOM.emptyState.style.display = 'flex';
        }
    },

    renderChart: async (month, mode) => {
        if (!DOM.categoryChart || typeof ApexCharts === 'undefined') return;

        if (!mode) mode = STATE._chartMode || 'donut';
        STATE._chartMode = mode;

        if (DOM.chartLoading) {
            DOM.chartLoading.style.display = 'flex';
        }

        try {
            const overview = await API.getOverview(month);
            const despesasCat = Array.isArray(overview.despesas_por_categoria)
                ? overview.despesas_por_categoria
                : [];

            const { isLightTheme } = getThemeColors();
            const themeMode = isLightTheme ? 'light' : 'dark';

            if (STATE.chartInstance) {
                STATE.chartInstance.destroy();
                STATE.chartInstance = null;
            }

            if (despesasCat.length === 0) {
                const actionCopy = getDashboardPrimaryActionCopy(overview?.meta || {}, {
                    accountCount: Number(overview?.meta?.real_account_count ?? 0),
                });

                DOM.categoryChart.innerHTML = `
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${escapeHtml(actionCopy.chartEmptyTitle)}</strong>
                        <p>${escapeHtml(actionCopy.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${escapeHtml(actionCopy.chartEmptyButton)}
                        </button>
                    </div>
                `;
                document.getElementById('dashboardChartEmptyCta')?.addEventListener('click', () => {
                    openPrimaryAction(overview?.meta || {}, {
                        accountCount: Number(overview?.meta?.real_account_count ?? 0),
                    });
                });

                if (typeof window.lucide !== 'undefined') {
                    window.lucide.createIcons();
                }

                return;
            }

            const palette = ['#E67E22', '#2ecc71', '#e74c3c', '#3498db', '#9b59b6', '#1abc9c', '#f39c12', '#e91e63', '#00bcd4', '#8bc34a'];

            if (mode === 'compare') {
                // Fetch previous month data for comparison
                const prevMonths = Utils.getPreviousMonths(month, 2);
                const prevMonth = prevMonths[0];
                let prevData = [];
                try {
                    const prevOverview = await API.getOverview(prevMonth);
                    prevData = Array.isArray(prevOverview.despesas_por_categoria) ? prevOverview.despesas_por_categoria : [];
                } catch { /* silent */ }

                // Merge categories from both months
                const allCats = new Set([...despesasCat.map(c => c.categoria), ...prevData.map(c => c.categoria)]);
                const labels = [...allCats];
                const currentMap = Object.fromEntries(despesasCat.map(c => [c.categoria, Math.abs(Number(c.valor) || 0)]));
                const prevMap = Object.fromEntries(prevData.map(c => [c.categoria, Math.abs(Number(c.valor) || 0)]));

                const currentSeries = labels.map(l => currentMap[l] || 0);
                const prevSeries = labels.map(l => prevMap[l] || 0);

                STATE.chartInstance = new ApexCharts(DOM.categoryChart, {
                    chart: { type: 'bar', height: 300, background: 'transparent', fontFamily: 'Inter, Arial, sans-serif', toolbar: { show: false } },
                    series: [
                        { name: Utils.formatMonthShort(month), data: currentSeries },
                        { name: Utils.formatMonthShort(prevMonth), data: prevSeries },
                    ],
                    colors: ['#E67E22', 'rgba(230,126,34,0.35)'],
                    xaxis: {
                        categories: labels,
                        labels: { style: { colors: isLightTheme ? '#555' : '#aaa', fontSize: '11px' }, rotate: -35, trim: true, maxHeight: 80 },
                    },
                    yaxis: { labels: { formatter: (v) => Utils.money(v), style: { colors: isLightTheme ? '#555' : '#aaa' } } },
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', fontSize: '12px', labels: { colors: isLightTheme ? '#555' : '#ccc' } },
                    tooltip: { theme: themeMode, y: { formatter: (v) => Utils.money(v) } },
                    grid: { borderColor: isLightTheme ? '#e5e5e5' : 'rgba(255,255,255,0.06)', strokeDashArray: 3 },
                    theme: { mode: themeMode },
                });
            } else {
                const labels = despesasCat.map(item => item.categoria);
                const series = despesasCat.map(item => Math.abs(Number(item.valor) || 0));

                STATE.chartInstance = new ApexCharts(DOM.categoryChart, {
                    chart: {
                        type: 'donut',
                        height: 280,
                        background: 'transparent',
                        fontFamily: 'Inter, Arial, sans-serif',
                    },
                    series,
                    labels,
                    colors: palette.slice(0, labels.length),
                    stroke: { width: 2, colors: [isLightTheme ? '#fff' : '#1e1e1e'] },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%',
                                labels: {
                                    show: true,
                                    value: {
                                        formatter: (val) => Utils.money(Number(val)),
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: (w) => Utils.money(w.globals.seriesTotals.reduce((a, b) => a + b, 0)),
                                    }
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '13px',
                        labels: { colors: isLightTheme ? '#555' : '#ccc' },
                    },
                    tooltip: {
                        theme: themeMode,
                        y: { formatter: (v) => Utils.money(v) },
                    },
                    dataLabels: { enabled: false },
                    theme: { mode: themeMode },
                });
            }
            STATE.chartInstance.render();
        } catch (err) {
            logClientError('Erro ao renderizar gráfico', err, 'Falha ao carregar gráfico');
        } finally {
            if (DOM.chartLoading) {
                setTimeout(() => {
                    DOM.chartLoading.style.display = 'none';
                }, 300);
            }
        }
    }
};

// ==================== OPTIONAL WIDGETS ====================

export const OptionalWidgets = createOptionalWidgets({
    API,
    CONFIG,
    Utils,
    escapeHtml,
    logClientError,
});

// ==================== PREVISÃO FINANCEIRA ====================

export const Provisao = createProvisao({
    API,
    Utils,
    escapeHtml,
    logClientError,
});

// ==================== RUNTIME ====================

export const { TransactionManager, DashboardManager, EventListeners } = createDashboardRuntime({
    STATE,
    DOM,
    Utils,
    API,
    Notifications,
    Renderers,
    Provisao,
    OptionalWidgets,
    invalidateDashboardOverview,
    getErrorMessage,
    logClientError,
});

Modules.App = {
    API,
    Notifications,
    Gamification,
    Renderers,
    TransactionManager,
    Provisao,
    DashboardManager,
    EventListeners
};
