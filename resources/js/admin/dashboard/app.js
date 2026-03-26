/**
 * ============================================================================
 * LUKRATO â€” Dashboard / App (Main Application Logic)
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
import { initDashboardTour } from './guided-tour.js';

// ==================== API ====================
// Usa LK.api (facade unificada) quando disponÃ­vel, com fallback local

export const API = {
    getOverview: async (month, options = {}) => {
        const response = await getDashboardOverview(month, options);
        return getApiPayload(response, {});
    },

    fetch: async (url) => {
        if (window.LK?.api) {
            const res = await LK.api.get(url);
            if (!res.ok) throw new Error(res.message || 'Erro na API');
            return res.data;
        }
        const json = await apiGet(url);
        if (json?.success === false) throw new Error(getErrorMessage({ data: json }, 'Erro na API'));
        return json?.data ?? json;
    },

    getMetrics: async (month) => {
        const overview = await API.getOverview(month);
        return overview.metrics || {};
    },

    getAccountsBalances: async (month) => {
        const overview = await API.getOverview(month);
        return Array.isArray(overview.accounts_balances) ? overview.accounts_balances : [];
    },

    getTransactions: async (month, limit) => {
        const overview = await API.getOverview(month, { limit });
        return Array.isArray(overview.recent_transactions) ? overview.recent_transactions : [];
    },

    getChartData: async (month) => {
        const overview = await API.getOverview(month);
        return Array.isArray(overview.chart) ? overview.chart : [];
    },

    getFinanceSummary: async (month) => {
        const match = String(month || '').match(/^(\d{4})-(\d{2})$/);
        if (!match) return {};

        const response = await apiGet(`${CONFIG.API_URL}financas/resumo`, {
            ano: Number(match[1]),
            mes: Number(match[2]),
        });

        return getApiPayload(response, {});
    },

    getCardsSummary: async () => {
        const response = await apiGet(`${CONFIG.API_URL}cartoes/resumo`);
        return getApiPayload(response, {});
    },

    deleteTransaction: async (id) => {
        if (window.LK?.api) {
            // Tenta o endpoint primÃ¡rio via facade
            const res = await LK.api.delete(`${CONFIG.API_URL}lancamentos/${id}`);
            if (res.ok) return res.data;
            throw new Error(res.message || 'Erro ao excluir');
        }
        // Fallback com mÃºltiplos endpoints
        const endpoints = [
            { request: () => apiDelete(`${CONFIG.API_URL}lancamentos/${id}`) },
            { request: () => apiPost(`${CONFIG.API_URL}lancamentos/${id}/delete`, {}) },
            { request: () => apiPost(`${CONFIG.API_URL}lancamentos/delete`, { id }) }
        ];
        for (const endpoint of endpoints) {
            try {
                return await endpoint.request();
            } catch (error) {
                if (error?.status !== 404) {
                    throw new Error(getErrorMessage(error, 'Erro ao excluir'));
                }
            }
        }
        throw new Error('Endpoint de exclusÃ£o nÃ£o encontrado.');
    }
};

// ==================== NOTIFICATIONS ====================
// Delega para LK.toast / LK.confirm / LK.loading (facade unificada)

export const Notifications = {
    ensureSwal: async () => {
        // SweetAlert2 jÃ¡ Ã© carregado globalmente no header
        if (window.Swal) return;
    },

    toast: (icon, title) => {
        if (window.LK?.toast) {
            return LK.toast[icon]?.(title) || LK.toast.info(title);
        }
        window.Swal?.fire({ toast: true, position: 'top-end', timer: 2500, timerProgressBar: true, showConfirmButton: false, icon, title });
    },

    loading: (title = 'Processando...') => {
        if (window.LK?.loading) return LK.loading(title);
        window.Swal?.fire({ title, didOpen: () => window.Swal.showLoading(), allowOutsideClick: false, showConfirmButton: false });
    },

    close: () => {
        if (window.LK?.hideLoading) return LK.hideLoading();
        window.Swal?.close();
    },

    confirm: async (title, text) => {
        if (window.LK?.confirm) return LK.confirm({ title, text, confirmText: 'Sim, confirmar', danger: true });
        const result = await window.Swal?.fire({
            title, text, icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sim, confirmar', cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--color-danger)', cancelButtonColor: 'var(--color-text-muted)'
        });
        return result?.isConfirmed;
    },

    error: (title, text) => {
        if (window.LK?.toast) return LK.toast.error(text || title);
        window.Swal?.fire({ icon: 'error', title, text, confirmButtonColor: 'var(--color-primary)' });
    }
};

// ==================== GAMIFICATION ====================

export const Gamification = {
    badges: [
        { id: 'first', icon: 'target', name: 'Inicio', condition: (data) => data.totalTransactions >= 1 },
        { id: 'week', icon: 'bar-chart-3', name: '7 Dias', condition: (data) => data.streak >= 7 },
        { id: 'month', icon: 'gem', name: '30 Dias', condition: (data) => data.streak >= 30 },
        { id: 'saver', icon: 'coins', name: 'Economia', condition: (data) => data.savingsRate >= 10 },
        { id: 'diverse', icon: 'palette', name: 'Diverso', condition: (data) => data.uniqueCategories >= 5 },
        { id: 'master', icon: 'crown', name: 'Mestre', condition: (data) => data.totalTransactions >= 100 }
    ],
    calculateStreak: (transactions) => {
        if (!Array.isArray(transactions) || transactions.length === 0) return 0;

        const dates = transactions
            .map(t => t.data_lancamento || t.data)
            .filter(Boolean)
            .map(d => {
                const match = String(d).match(/^(\d{4})-(\d{2})-(\d{2})/);
                return match ? `${match[1]}-${match[2]}-${match[3]}` : null;
            })
            .filter(Boolean)
            .sort()
            .reverse();

        if (dates.length === 0) return 0;

        const uniqueDates = [...new Set(dates)];
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let streak = 0;
        let checkDate = new Date(today);

        for (const dateStr of uniqueDates) {
            const [y, m, d] = dateStr.split('-').map(Number);
            const transactionDate = new Date(y, m - 1, d);
            transactionDate.setHours(0, 0, 0, 0);

            const diffDays = Math.round((checkDate - transactionDate) / (1000 * 60 * 60 * 24));

            if (diffDays === 0 || diffDays === 1) {
                streak++;
                checkDate = new Date(transactionDate);
                checkDate.setDate(checkDate.getDate() - 1);
            } else if (diffDays > 1) {
                break;
            }
        }

        return streak;
    },

    calculateLevel: (points) => {
        if (points < 100) return 1;
        if (points < 300) return 2;
        if (points < 600) return 3;
        if (points < 1000) return 4;
        if (points < 1500) return 5;
        if (points < 2500) return 6;
        if (points < 5000) return 7;
        if (points < 10000) return 8;
        if (points < 20000) return 9;
        return 10;
    },

    calculatePoints: (data) => {
        let points = 0;
        points += data.totalTransactions * 10;
        points += data.streak * 50;
        points += data.activeMonths * 100;
        points += data.uniqueCategories * 20;
        points += Math.floor(data.savingsRate) * 30;
        return points;
    },

    calculateData: (transactions, metrics) => {
        const totalTransactions = transactions.length;
        const streak = Gamification.calculateStreak(transactions);

        const uniqueCategories = new Set(
            transactions
                .map(t => t.categoria_id || t.categoria)
                .filter(Boolean)
        ).size;

        const months = new Set(
            transactions
                .map(t => {
                    const date = t.data_lancamento || t.data;
                    if (!date) return null;
                    const match = String(date).match(/^(\d{4}-\d{2})/);
                    return match ? match[1] : null;
                })
                .filter(Boolean)
        );

        const activeMonths = months.size;

        const receitas = Number(metrics?.receitas || 0);
        const despesas = Number(metrics?.despesas || 0);
        const savingsRate = receitas > 0 ? ((receitas - despesas) / receitas) * 100 : 0;

        const data = {
            totalTransactions,
            streak,
            uniqueCategories,
            activeMonths,
            savingsRate: Math.max(0, savingsRate)
        };

        const points = Gamification.calculatePoints(data);
        const level = Gamification.calculateLevel(points);

        return { ...data, points, level };
    }
};

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
            insightEl.textContent = 'Seu historico aparece aqui conforme voce usa o Lukrato mais vezes.';
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
            const [metrics, accounts] = await Promise.all([
                API.getMetrics(month),
                API.getAccountsBalances(month)
            ]);

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
                    const descricao = escapeHtml(transaction.descricao || transaction.observacao || '--');
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
              <td data-label="DescriÃ§Ã£o">${descricao}</td>
              <td data-label="Valor" class="valor-cell ${tipoClass}">${Utils.money(valor)}</td>
              <td data-label="AÃ§Ãµes" class="text-end">
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
                    <span class="transaction-label">DescriÃ§Ã£o</span>
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
                    const descricao = escapeHtml(tx.descricao || tx.observacao || '--');
                    const categoriaNome = tx.categoria_nome ??
                        (typeof tx.categoria === 'string' ? tx.categoria : tx.categoria?.nome) ?? 'Sem categoria';
                    const valor = Number(tx.valor) || 0;
                    const isPago = Boolean(tx.pago);
                    const catIcon = tx.categoria_icone || (isIncome ? 'arrow-down-left' : 'arrow-up-right');

                    const el = document.createElement('div');
                    el.className = 'dash-tx-item';
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
                DOM.categoryChart.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--color-text-muted);">Sem despesas no período</p>';
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

export const OptionalWidgets = {
    getContainer: (sectionId, bodyId) => {
        const existing = document.getElementById(bodyId);
        if (existing) return existing;

        const section = document.getElementById(sectionId);
        if (!section) return null;

        const legacyBody = section.querySelector('.dash-optional-body');
        if (legacyBody) {
            if (!legacyBody.id) legacyBody.id = bodyId;
            return legacyBody;
        }

        const body = document.createElement('div');
        body.className = 'dash-optional-body';
        body.id = bodyId;

        const header = section.querySelector('.dash-section-header');
        const orphanPlaceholders = Array.from(section.children).filter((child) =>
            child.classList?.contains('dash-placeholder')
        );

        if (header?.nextSibling) {
            section.insertBefore(body, header.nextSibling);
        } else {
            section.appendChild(body);
        }

        orphanPlaceholders.forEach((node) => body.appendChild(node));

        return body;
    },

    renderLoading: (container) => {
        if (!container) return;

        container.innerHTML = `
            <div class="dash-widget dash-widget--loading" aria-hidden="true">
                <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
            </div>
        `;
    },

    renderEmpty: (container, message, href, cta) => {
        if (!container) return;

        container.innerHTML = `
            <div class="dash-widget-empty">
                <p>${message}</p>
                ${href && cta ? `<a href="${href}" class="dash-widget-link">${cta}</a>` : ''}
            </div>
        `;
    },

    getUsageColor: (percent) => {
        if (percent >= 85) return '#ef4444';
        if (percent >= 60) return '#f59e0b';
        return '#10b981';
    },

    getAccountBalance: (account) => {
        const candidates = [
            account?.saldoAtual,
            account?.saldo_atual,
            account?.saldo,
            account?.saldoInicial,
            account?.saldo_inicial,
        ];

        const value = candidates.find((item) => Number.isFinite(Number(item)));
        return Number(value || 0);
    },

    renderMetas: async (month) => {
        const container = OptionalWidgets.getContainer('sectionMetas', 'sectionMetasBody');
        if (!container) return;

        OptionalWidgets.renderLoading(container);

        try {
            const summary = await API.getFinanceSummary(month);
            const metas = summary?.metas ?? null;

            if (!metas || Number(metas.total_metas || 0) === 0) {
                OptionalWidgets.renderEmpty(
                    container,
                    'Você ainda não tem metas ativas neste momento.',
                    `${CONFIG.BASE_URL}financas#metas`,
                    'Criar meta'
                );
                return;
            }

            const proxima = metas.proxima_concluir || null;
            const pctGeral = Math.round(Number(metas.progresso_geral || 0));

            if (!proxima) {
                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Metas ativas</span>
                        <strong class="dash-widget-value">${Number(metas.total_metas || 0)}</strong>
                        <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                        <div class="dash-widget-meta">
                            <span>Progresso geral</span>
                            <strong>${pctGeral}%</strong>
                        </div>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(pctGeral, 100)}%; background:var(--color-primary);"></span>
                        </div>
                        <a href="${CONFIG.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `;
                return;
            }

            const titulo = escapeHtml(String(proxima.titulo || 'Sua meta principal'));
            const valorAtual = Number(proxima.valor_atual || 0);
            const valorAlvo = Number(proxima.valor_alvo || 0);
            const faltam = Math.max(valorAlvo - valorAtual, 0);
            const pct = Math.round(Number(proxima.progresso || 0));
            const cor = proxima.cor || 'var(--color-primary)';

            container.innerHTML = `
                <div class="dash-widget">
                    <span class="dash-widget-label">Próxima meta</span>
                    <strong class="dash-widget-value">${titulo}</strong>
                    <p class="dash-widget-caption">Faltam ${Utils.money(faltam)} para concluir.</p>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(pct, 100)}%; background:${cor};"></span>
                    </div>
                    <div class="dash-widget-meta">
                        <span>${Utils.money(valorAtual)} de ${Utils.money(valorAlvo)}</span>
                        <strong style="color:${cor};">${pct}%</strong>
                    </div>
                    <a href="${CONFIG.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                </div>
            `;
        } catch (error) {
            logClientError('Erro ao carregar widget de metas', error, 'Falha ao carregar metas');
            OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas metas agora.', `${CONFIG.BASE_URL}financas#metas`, 'Tentar nas finanças');
        }
    },

    renderCartoes: async () => {
        const container = OptionalWidgets.getContainer('sectionCartoes', 'sectionCartoesBody');
        if (!container) return;

        OptionalWidgets.renderLoading(container);

        try {
            const summary = await API.getCardsSummary();
            const totalCartoes = Number(summary?.total_cartoes || 0);

            if (!summary || totalCartoes === 0) {
                OptionalWidgets.renderEmpty(
                    container,
                    'Você ainda não tem cartões ativos no dashboard.',
                    `${CONFIG.BASE_URL}cartoes`,
                    'Cadastrar cartão'
                );
                return;
            }

            const limiteDisponivel = Number(summary.limite_disponivel || 0);
            const limiteTotal = Number(summary.limite_total || 0);
            const percentualUso = Math.round(Number(summary.percentual_uso || 0));
            const usoColor = OptionalWidgets.getUsageColor(percentualUso);

            container.innerHTML = `
                <div class="dash-widget">
                    <span class="dash-widget-label">Limite disponível</span>
                    <strong class="dash-widget-value">${Utils.money(limiteDisponivel)}</strong>
                    <p class="dash-widget-caption">${totalCartoes} cartão(ões) ativo(s) com ${percentualUso}% de uso consolidado.</p>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(percentualUso, 100)}%; background:${usoColor};"></span>
                    </div>
                    <div class="dash-widget-meta">
                        <span>Limite total ${Utils.money(limiteTotal)}</span>
                        <strong style="color:${usoColor};">${percentualUso}% usado</strong>
                    </div>
                    <a href="${CONFIG.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                </div>
            `;
        } catch (error) {
            logClientError('Erro ao carregar widget de cartões', error, 'Falha ao carregar cartões');
            OptionalWidgets.renderEmpty(container, 'Não foi possível carregar seus cartões agora.', `${CONFIG.BASE_URL}cartoes`, 'Criar cartões');
        }
    },

    renderContas: async (month) => {
        const container = OptionalWidgets.getContainer('sectionContas', 'sectionContasBody');
        if (!container) return;

        OptionalWidgets.renderLoading(container);

        try {
            const accounts = await API.getAccountsBalances(month);
            const contas = Array.isArray(accounts) ? accounts : [];

            if (contas.length === 0) {
                OptionalWidgets.renderEmpty(
                    container,
                    'Você ainda não tem contas ativas conectadas.',
                    `${CONFIG.BASE_URL}contas`,
                    'Adicionar conta'
                );
                return;
            }

            const sorted = contas
                .map((account) => ({
                    ...account,
                    __saldo: OptionalWidgets.getAccountBalance(account),
                }))
                .sort((left, right) => right.__saldo - left.__saldo);

            const totalSaldo = sorted.reduce((sum, account) => sum + account.__saldo, 0);
            const principal = sorted[0] || null;
            const principalNome = escapeHtml(String(
                principal?.nome ||
                principal?.nome_conta ||
                principal?.instituicao ||
                principal?.banco_nome ||
                'Conta principal'
            ));
            const principalSaldo = principal ? Utils.money(principal.__saldo) : Utils.money(0);

            container.innerHTML = `
                <div class="dash-widget">
                    <span class="dash-widget-label">Saldo consolidado</span>
                    <strong class="dash-widget-value">${Utils.money(totalSaldo)}</strong>
                    <p class="dash-widget-caption">${sorted.length} conta(s) ativa(s) no painel.</p>
                    <div class="dash-widget-list">
                        ${sorted.slice(0, 3).map((account) => {
                const label = escapeHtml(String(
                    account.nome ||
                    account.nome_conta ||
                    account.instituicao ||
                    account.banco_nome ||
                    'Conta'
                ));
                return `
                                <div class="dash-widget-list-item">
                                    <span>${label}</span>
                                    <strong>${Utils.money(account.__saldo)}</strong>
                                </div>
                            `;
            }).join('')}
                    </div>
                    <div class="dash-widget-meta">
                        <span>Maior saldo em ${principalNome}</span>
                        <strong>${principalSaldo}</strong>
                    </div>
                    <a href="${CONFIG.BASE_URL}contas" class="dash-widget-link">Abrir contas</a>
                </div>
            `;
        } catch (error) {
            logClientError('Erro ao carregar widget de contas', error, 'Falha ao carregar contas');
            OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas contas agora.', `${CONFIG.BASE_URL}contas`, 'Abrir contas');
        }
    },

    renderOrcamentos: async (month) => {
        const container = OptionalWidgets.getContainer('sectionOrcamentos', 'sectionOrcamentosBody');
        if (!container) return;

        OptionalWidgets.renderLoading(container);

        try {
            const summary = await API.getFinanceSummary(month);
            const orcamento = summary?.orcamento ?? null;

            if (!orcamento || Number(orcamento.total_categorias || 0) === 0) {
                OptionalWidgets.renderEmpty(
                    container,
                    'Você ainda não definiu limites para categorias.',
                    `${CONFIG.BASE_URL}financas#orcamentos`,
                    'Definir limite'
                );
                return;
            }

            const pctGeral = Math.round(Number(orcamento.percentual_geral || 0));
            const usageColor = OptionalWidgets.getUsageColor(pctGeral);
            const top3 = (orcamento.orcamentos || [])
                .slice()
                .sort((a, b) => Number(b.percentual || 0) - Number(a.percentual || 0))
                .slice(0, 3);

            const itemsHtml = top3.map(orc => {
                const color = OptionalWidgets.getUsageColor(orc.percentual);
                return `
                    <div class="dash-widget-list-item">
                        <span>${escapeHtml(orc.categoria_nome || 'Categoria')}</span>
                        <strong style="color:${color};">${Math.round(orc.percentual || 0)}%</strong>
                    </div>
                `;
            }).join('');

            container.innerHTML = `
                <div class="dash-widget">
                    <span class="dash-widget-label">Uso geral dos limites</span>
                    <strong class="dash-widget-value" style="color:${usageColor};">${pctGeral}%</strong>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(pctGeral, 100)}%; background:${usageColor};"></span>
                    </div>
                    <p class="dash-widget-caption">${Utils.money(orcamento.total_gasto || 0)} de ${Utils.money(orcamento.total_limite || 0)}</p>
                    ${itemsHtml ? `<div class="dash-widget-list">${itemsHtml}</div>` : ''}
                    <a href="${CONFIG.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                </div>
            `;
        } catch (error) {
            logClientError('Erro ao carregar widget de orçamentos', error, 'Falha ao carregar orçamentos');
            OptionalWidgets.renderEmpty(container, 'Não foi possível carregar seus orçamentos.', `${CONFIG.BASE_URL}financas#orcamentos`, 'Abrir orçamentos');
        }
    },

    renderFaturas: async () => {
        const container = OptionalWidgets.getContainer('sectionFaturas', 'sectionFaturasBody');
        if (!container) return;

        OptionalWidgets.renderLoading(container);

        try {
            const summary = await API.getCardsSummary();
            const totalCartoes = Number(summary?.total_cartoes || 0);

            if (!summary || totalCartoes === 0) {
                OptionalWidgets.renderEmpty(
                    container,
                    'Você não tem cartões com faturas abertas.',
                    `${CONFIG.BASE_URL}faturas`,
                    'Ver faturas'
                );
                return;
            }

            const faturaAberta = Number(summary.fatura_aberta ?? summary.limite_utilizado ?? 0);
            const limiteTotal = Number(summary.limite_total || 0);
            const pctUso = limiteTotal > 0
                ? Math.round((faturaAberta / limiteTotal) * 100)
                : Number(summary.percentual_uso || 0);
            const usageColor = OptionalWidgets.getUsageColor(pctUso);

            container.innerHTML = `
                <div class="dash-widget">
                    <span class="dash-widget-label">Fatura atual</span>
                    <strong class="dash-widget-value">${Utils.money(faturaAberta)}</strong>
                    ${limiteTotal > 0 ? `
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(pctUso, 100)}%; background:${usageColor};"></span>
                        </div>
                        <p class="dash-widget-caption">${pctUso}% do limite utilizado</p>
                    ` : `
                        <p class="dash-widget-caption">${totalCartoes} cartão(ões) ativo(s)</p>
                    `}
                    <a href="${CONFIG.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                </div>
            `;
        } catch (error) {
            logClientError('Erro ao carregar widget de faturas', error, 'Falha ao carregar faturas');
            OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas faturas.', `${CONFIG.BASE_URL}faturas`, 'Ver faturas');
        }
    },

    render: async (month) => {
        await Promise.allSettled([
            OptionalWidgets.renderMetas(month),
            OptionalWidgets.renderCartoes(),
            OptionalWidgets.renderContas(month),
            OptionalWidgets.renderOrcamentos(month),
            OptionalWidgets.renderFaturas(),
        ]);
    },
};

// ==================== TRANSACTION MANAGER ====================

export const TransactionManager = {
    delete: async (id, rowElement) => {
        try {
            await Notifications.ensureSwal();

            const confirmed = await Notifications.confirm(
                'Excluir lanÃ§amento?',
                'Esta aÃ§Ã£o nÃ£o pode ser desfeita.'
            );

            if (!confirmed) return;

            Notifications.loading('Excluindo...');

            await API.deleteTransaction(Number(id));

            Notifications.close();
            Notifications.toast('success', 'LanÃ§amento excluÃ­do com sucesso!');

            if (rowElement) {
                rowElement.style.opacity = '0';
                rowElement.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    rowElement.remove();

                    if (DOM.tableBody.children.length === 0) {
                        if (DOM.emptyState) DOM.emptyState.style.display = 'block';
                        if (DOM.table) DOM.table.style.display = 'none';
                    }
                }, 300);
            }

            // Refresh will be triggered by the lukrato:data-changed event listener
            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'delete',
                    id: Number(id)
                }
            }));
        } catch (err) {
            console.error('Erro ao excluir lanÃ§amento:', err);
            await Notifications.ensureSwal();
            Notifications.error('Erro', getErrorMessage(err, 'Falha ao excluir lanÃ§amento'));
        }
    }
};

// ==================== PREVISÃƒO FINANCEIRA ====================

export const Provisao = {
    isProUser: null,

    checkProStatus: async () => {
        try {
            const overview = await API.getOverview(Utils.getCurrentMonth());
            Provisao.isProUser = overview?.plan?.is_pro === true;
        } catch {
            Provisao.isProUser = false;
        }

        return Provisao.isProUser;
    },

    render: async (month) => {
        const section = document.getElementById('sectionPrevisao');
        if (!section) return;

        // Always re-check Pro status to avoid stale cache
        await Provisao.checkProStatus();

        const overlay = document.getElementById('provisaoProOverlay');
        const isPro = Provisao.isProUser;

        // Sempre carrega dados reais (Free mostra sÃ³ faturas, Pro mostra tudo)
        section.classList.remove('is-locked');
        if (overlay) overlay.style.display = 'none';

        try {
            const overview = await API.getOverview(month);
            Provisao.renderData(overview.provisao || null, isPro);
        } catch (err) {
            logClientError('Erro ao carregar provisÃ£o', err, 'Falha ao carregar previsÃ£o');
        }
    },

    renderData: (data, isPro = true) => {
        if (!data) return;

        const p = data.provisao || {};
        const money = Utils.money;
        const titleSummaryEl = document.getElementById('provisaoTitle');
        const headlineEl = document.getElementById('provisaoHeadline');

        if (titleSummaryEl) {
            titleSummaryEl.textContent = `Se continuar assim, voce termina o mes com ${money(p.saldo_projetado || 0)}`;
        }

        if (headlineEl) {
            headlineEl.textContent = (p.saldo_projetado || 0) >= 0
                ? 'A previsao abaixo considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair.'
                : 'A previsao indica aperto no fim do mes se o ritmo atual continuar.';
        }

        // Atualizar tÃ­tulo e link conforme plano
        const titleEl = document.getElementById('provisaoProximosTitle');
        const verTodosEl = document.getElementById('provisaoVerTodos');
        if (titleEl) {
            titleEl.innerHTML = isPro
                ? '<i data-lucide="clock"></i> Próximos Vencimentos'
                : '<i data-lucide="credit-card"></i> PrÃ³ximas Faturas';
        }
        if (verTodosEl) {
            verTodosEl.href = isPro
                ? `${window.BASE_URL || '/'}lancamentos`
                : `${window.BASE_URL || '/'}faturas`;
        }

        // Cards
        const pagar = document.getElementById('provisaoPagar');
        const receber = document.getElementById('provisaoReceber');
        const projetado = document.getElementById('provisaoProjetado');
        const pagarCount = document.getElementById('provisaoPagarCount');
        const receberCount = document.getElementById('provisaoReceberCount');
        const projetadoLabel = document.getElementById('provisaoProjetadoLabel');

        // Card A Receber - sÃ³ mostra dados para Pro
        const receberCard = receber?.closest('.provisao-card');

        if (pagar) pagar.textContent = money(p.a_pagar || 0);

        if (isPro) {
            if (receber) receber.textContent = money(p.a_receber || 0);
            if (receberCard) receberCard.style.opacity = '1';
        } else {
            // Free: esconde o card A Receber ou mostra como bloqueado
            if (receber) receber.textContent = 'R$ --';
            if (receberCard) receberCard.style.opacity = '0.5';
        }

        if (projetado) {
            projetado.textContent = money(p.saldo_projetado || 0);
            projetado.style.color = (p.saldo_projetado || 0) >= 0 ? '' : 'var(--color-danger)';
        }

        // Contador de A Pagar com faturas de cartÃ£o
        if (pagarCount) {
            const countAgend = p.count_pagar || 0;
            const countFat = p.count_faturas || 0;

            if (isPro) {
                let pagarText = `${countAgend} pendente${countAgend !== 1 ? 's' : ''}`;
                if (countFat > 0) {
                    pagarText += ` â€¢ ${countFat} fatura${countFat !== 1 ? 's' : ''}`;
                }
                pagarCount.textContent = pagarText;
            } else {
                // Free: mostra apenas faturas
                pagarCount.textContent = `${countFat} fatura${countFat !== 1 ? 's' : ''}`;
            }
        }

        if (isPro) {
            if (receberCount) receberCount.textContent = `${p.count_receber || 0} pendente${(p.count_receber || 0) !== 1 ? 's' : ''}`;
        } else {
            if (receberCount) receberCount.textContent = 'Pro';
        }

        if (projetadoLabel) projetadoLabel.textContent = `saldo atual: ${money(p.saldo_atual || 0)}`;

        // Alertas de vencidos (separados por tipo)
        const vencidos = data.vencidos || {};

        // Alerta de despesas vencidas (sÃ³ Pro)
        const alertDespesas = document.getElementById('provisaoAlertDespesas');
        if (alertDespesas) {
            const despesas = vencidos.despesas || {};
            if (isPro && (despesas.count || 0) > 0) {
                alertDespesas.style.display = 'flex';
                const countEl = document.getElementById('provisaoAlertDespesasCount');
                const totalEl = document.getElementById('provisaoAlertDespesasTotal');
                if (countEl) countEl.textContent = despesas.count;
                if (totalEl) totalEl.textContent = money(despesas.total || 0);
            } else {
                alertDespesas.style.display = 'none';
            }
        }

        // Alerta de receitas vencidas (nÃ£o recebidas) - sÃ³ Pro
        const alertReceitas = document.getElementById('provisaoAlertReceitas');
        if (alertReceitas) {
            const receitas = vencidos.receitas || {};
            if (isPro && (receitas.count || 0) > 0) {
                alertReceitas.style.display = 'flex';
                const countEl = document.getElementById('provisaoAlertReceitasCount');
                const totalEl = document.getElementById('provisaoAlertReceitasTotal');
                if (countEl) countEl.textContent = receitas.count;
                if (totalEl) totalEl.textContent = money(receitas.total || 0);
            } else {
                alertReceitas.style.display = 'none';
            }
        }

        // Alerta de faturas vencidas
        const alertFaturas = document.getElementById('provisaoAlertFaturas');
        if (alertFaturas) {
            const countFat = vencidos.count_faturas || 0;
            if (countFat > 0) {
                alertFaturas.style.display = 'flex';
                const countEl = document.getElementById('provisaoAlertFaturasCount');
                const totalEl = document.getElementById('provisaoAlertFaturasTotal');
                if (countEl) countEl.textContent = countFat;
                if (totalEl) totalEl.textContent = money(vencidos.total_faturas || 0);
            } else {
                alertFaturas.style.display = 'none';
            }
        }

        // PrÃ³ximos vencimentos
        const list = document.getElementById('provisaoProximosList');
        const emptyEl = document.getElementById('provisaoEmpty');
        let proximos = data.proximos || [];

        // Free: filtra para mostrar apenas faturas
        if (!isPro) {
            proximos = proximos.filter(item => item.is_fatura === true);
        }

        if (list) {
            if (proximos.length === 0) {
                list.innerHTML = '';
                if (emptyEl) {
                    // Ajusta mensagem conforme plano
                    const emptyText = emptyEl.querySelector('span');
                    if (emptyText) {
                        emptyText.textContent = isPro ? 'Nenhum vencimento pendente' : 'Nenhuma fatura pendente';
                    }
                    list.appendChild(emptyEl);
                    emptyEl.style.display = 'flex';
                }
            } else {
                list.innerHTML = '';
                const today = new Date().toISOString().slice(0, 10);

                proximos.forEach(item => {
                    const tipo = (item.tipo || '').toLowerCase();
                    const isFatura = item.is_fatura === true;
                    const dataParts = (item.data_pagamento || '').split(/[T\s]/)[0];
                    const isHoje = dataParts === today;
                    const dateDisplay = Provisao.formatDateShort(dataParts);

                    let badges = '';
                    if (isHoje) badges += '<span class="provisao-item-badge vence-hoje">Hoje</span>';

                    if (isFatura) {
                        // Badge especial para fatura de cartÃ£o
                        badges += '<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>';
                        if (item.cartao_ultimos_digitos) {
                            badges += `<span>****${item.cartao_ultimos_digitos}</span>`;
                        }
                    } else {
                        if (item.eh_parcelado && item.numero_parcelas > 1) {
                            badges += `<span class="provisao-item-badge parcela">${item.parcela_atual}/${item.numero_parcelas}</span>`;
                        }
                        if (item.recorrente) {
                            badges += '<span class="provisao-item-badge recorrente">Recorrente</span>';
                        }
                        if (item.categoria) {
                            badges += `<span>${escapeHtml(item.categoria)}</span>`;
                        }
                    }

                    const tipoClass = isFatura ? 'fatura' : tipo;
                    const el = document.createElement('div');
                    el.className = 'provisao-item' + (isFatura ? ' is-fatura' : '');
                    el.innerHTML = `
                            <div class="provisao-item-dot ${tipoClass}"></div>
                            <div class="provisao-item-info">
                                <div class="provisao-item-titulo">${escapeHtml(item.titulo || 'Sem tÃ­tulo')}</div>
                                <div class="provisao-item-meta">${badges}</div>
                            </div>
                            <span class="provisao-item-valor ${tipoClass}">${money(item.valor || 0)}</span>
                            <span class="provisao-item-data">${dateDisplay}</span>
                        `;

                    // Adicionar link para faturas
                    if (isFatura && item.cartao_id) {
                        el.style.cursor = 'pointer';
                        el.addEventListener('click', () => {
                            const dataVenc = (item.data_pagamento || '').split(/[T\s]/)[0];
                            const [ano, mes] = dataVenc.split('-');
                            window.location.href = `${window.BASE_URL || '/'}faturas?cartao_id=${item.cartao_id}&mes=${parseInt(mes)}&ano=${ano}`;
                        });
                    }

                    list.appendChild(el);
                });
            }
        }

        // Parcelas ativas (sÃ³ Pro)
        const parcelasEl = document.getElementById('provisaoParcelas');
        const parcelas = data.parcelas || {};
        if (parcelasEl) {
            if (isPro && (parcelas.ativas || 0) > 0) {
                parcelasEl.style.display = 'flex';
                const textEl = document.getElementById('provisaoParcelasText');
                const valorEl = document.getElementById('provisaoParcelasValor');
                if (textEl) textEl.textContent = `${parcelas.ativas} parcelamento${parcelas.ativas !== 1 ? 's' : ''} ativo${parcelas.ativas !== 1 ? 's' : ''}`;
                if (valorEl) valorEl.textContent = `${money(parcelas.total_mensal || 0)}/mÃªs`;
            } else {
                parcelasEl.style.display = 'none';
            }
        }
    },

    formatDateShort: (dateStr) => {
        if (!dateStr) return '-';
        try {
            const m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}` : '-';
        } catch {
            return '-';
        }
    }
};

// ==================== DASHBOARD MANAGER ====================

export const DashboardManager = {
    refresh: async ({ force = false } = {}) => {
        if (STATE.isLoading) return;

        STATE.isLoading = true;
        const month = Utils.getCurrentMonth();
        STATE.currentMonth = month;

        if (force) {
            invalidateDashboardOverview(month);
        }

        try {
            Renderers.updateMonthLabel(month);

            await Promise.allSettled([
                Renderers.renderKPIs(month),
                Renderers.renderTable(month),
                Renderers.renderTransactionsList(month),
                Renderers.renderChart(month),
                Provisao.render(month),
                OptionalWidgets.render(month)
            ]);
        } catch (err) {
            logClientError('Erro ao atualizar dashboard', err, 'Falha ao atualizar dashboard');
        } finally {
            STATE.isLoading = false;
        }
    },

    init: async () => {
        await DashboardManager.refresh({ force: false });
    }
};

// ==================== EVENT LISTENERS ====================

export const EventListeners = {
    init: () => {
        if (STATE.eventListenersInitialized) {
            return;
        }
        STATE.eventListenersInitialized = true;

        // Event listener para tabela desktop
        DOM.tableBody?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-del');
            if (!btn) return;

            const row = e.target.closest('tr');
            const id = btn.getAttribute('data-id');

            if (!id) return;

            btn.disabled = true;
            await TransactionManager.delete(id, row);
            btn.disabled = false;
        });

        // Event listener para cards mobile
        DOM.cardsContainer?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-del');
            if (!btn) return;

            const card = e.target.closest('.transaction-card');
            const id = btn.getAttribute('data-id');

            if (!id) return;

            btn.disabled = true;
            await TransactionManager.delete(id, card);
            btn.disabled = false;
        });

        // Event listener para lista de transações (novo layout)
        DOM.transactionsList?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-del');
            if (!btn) return;

            const item = e.target.closest('.dash-tx-item');
            const id = btn.getAttribute('data-id');

            if (!id) return;

            btn.disabled = true;
            await TransactionManager.delete(id, item);
            btn.disabled = false;
        });

        document.addEventListener('lukrato:data-changed', () => {
            invalidateDashboardOverview(STATE.currentMonth || Utils.getCurrentMonth());
            DashboardManager.refresh({ force: false });
        });

        document.addEventListener('lukrato:month-changed', () => {
            DashboardManager.refresh({ force: false });
        });

        document.addEventListener('lukrato:theme-changed', () => {
            Renderers.renderChart(STATE.currentMonth || Utils.getCurrentMonth());
        });

        // Chart mode toggle (donut vs compare)
        const chartToggle = document.getElementById('chartToggle');
        if (chartToggle) {
            chartToggle.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-mode]');
                if (!btn) return;
                const mode = btn.getAttribute('data-mode');
                chartToggle.querySelectorAll('.dash-chart-toggle__btn').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                Renderers.renderChart(STATE.currentMonth || Utils.getCurrentMonth(), mode);
            });
        }

        // Initialize guided tour for first-time visitors
        initDashboardTour();
    }
};

// â”€â”€â”€ Register as Modules.App â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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





