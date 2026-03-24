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
import { initDashboardTour } from './guided-tour.js';

// ==================== API ====================
// Usa LK.api (facade unificada) quando disponível, com fallback local

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

    deleteTransaction: async (id) => {
        if (window.LK?.api) {
            // Tenta o endpoint primário via facade
            const res = await LK.api.delete(`${CONFIG.API_URL}lancamentos/${id}`);
            if (res.ok) return res.data;
            throw new Error(res.message || 'Erro ao excluir');
        }
        // Fallback com múltiplos endpoints
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
        throw new Error('Endpoint de exclusão não encontrado.');
    }
};

// ==================== NOTIFICATIONS ====================
// Delega para LK.toast / LK.confirm / LK.loading (facade unificada)

export const Notifications = {
    ensureSwal: async () => {
        // SweetAlert2 já é carregado globalmente no header
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
        { id: 'first', emoji: '🎯', name: 'Início', condition: (data) => data.totalTransactions >= 1 },
        { id: 'week', emoji: '📊', name: '7 Dias', condition: (data) => data.streak >= 7 },
        { id: 'month', emoji: '💎', name: '30 Dias', condition: (data) => data.streak >= 30 },
        { id: 'saver', emoji: '💰', name: 'Economia', condition: (data) => data.savingsRate >= 10 },
        { id: 'diverse', emoji: '🎨', name: 'Diverso', condition: (data) => data.uniqueCategories >= 5 },
        { id: 'master', emoji: '👑', name: 'Mestre', condition: (data) => data.totalTransactions >= 100 }
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
        const section = document.getElementById('dashboardAlertsSection');
        const overview = document.getElementById('dashboardAlertsOverview');
        const budget = document.getElementById('dashboardAlertsBudget');

        if (!section) return;

        const hasOverview = overview && overview.innerHTML.trim() !== '';
        const hasBudget = budget && budget.innerHTML.trim() !== '';
        section.style.display = hasOverview || hasBudget ? 'block' : 'none';
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

    renderOverviewAlerts: ({ receitas, despesas }) => {
        const container = document.getElementById('dashboardAlertsOverview');
        if (!container) return;

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

            // Limpar ambos
            if (DOM.tableBody) DOM.tableBody.innerHTML = '';
            if (DOM.cardsContainer) DOM.cardsContainer.innerHTML = '';

            const hasData = Array.isArray(transactions) && transactions.length > 0;

            if (DOM.emptyState) {
                DOM.emptyState.style.display = hasData ? 'none' : 'block';
            }

            if (DOM.table) {
                DOM.table.style.display = hasData ? 'table' : 'none';
            }

            if (DOM.cardsContainer) {
                DOM.cardsContainer.style.display = hasData ? 'flex' : 'none';
            }

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

            if (DOM.emptyState) {
                DOM.emptyState.style.display = 'block';
            }

            if (DOM.table) {
                DOM.table.style.display = 'none';
            }

            if (DOM.cardsContainer) {
                DOM.cardsContainer.style.display = 'none';
            }
        }
    },

    renderChart: async (month) => {
        if (!DOM.chartContainer || typeof ApexCharts === 'undefined') return;

        if (DOM.chartLoading) {
            DOM.chartLoading.style.display = 'flex';
        }

        try {
            const months = Utils.getPreviousMonths(month, CONFIG.CHART_MONTHS);
            const labels = months.map(m => Utils.formatMonthShort(m));

            const chartData = await API.getChartData(month);
            const chartMap = new Map(chartData.map((item) => [item.month, Number(item.resultado || 0)]));
            const data = months.map((itemMonth) => chartMap.get(itemMonth) || 0);
            Renderers.renderChartInsight(months, data);

            const {
                xTickColor,
                yTickColor,
                gridColor,
                isLightTheme
            } = getThemeColors();

            const themeMode = isLightTheme ? 'light' : 'dark';

            if (STATE.chartInstance) {
                STATE.chartInstance.destroy();
                STATE.chartInstance = null;
            }

            STATE.chartInstance = new ApexCharts(DOM.chartContainer, {
                chart: {
                    type: 'area',
                    height: 300,
                    width: '100%',
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'Inter, Arial, sans-serif',
                },
                series: [{ name: 'Resultado do Mês', data }],
                xaxis: {
                    categories: labels,
                    labels: { style: { colors: xTickColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: yTickColor },
                        formatter: (value) => Utils.money(value),
                    },
                },
                colors: ['#E67E22'],
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 100],
                    },
                },
                markers: {
                    size: 5,
                    colors: ['#E67E22'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: { size: 7 },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                },
                tooltip: {
                    theme: themeMode,
                    y: { formatter: (v) => `Resultado: ${Utils.money(v)}` },
                },
                legend: { show: false },
                dataLabels: { enabled: false },
                theme: { mode: themeMode },
            });
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

// ==================== TRANSACTION MANAGER ====================

export const TransactionManager = {
    delete: async (id, rowElement) => {
        try {
            await Notifications.ensureSwal();

            const confirmed = await Notifications.confirm(
                'Excluir lançamento?',
                'Esta ação não pode ser desfeita.'
            );

            if (!confirmed) return;

            Notifications.loading('Excluindo...');

            await API.deleteTransaction(Number(id));

            Notifications.close();
            Notifications.toast('success', 'Lançamento excluído com sucesso!');

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
            console.error('Erro ao excluir lançamento:', err);
            await Notifications.ensureSwal();
            Notifications.error('Erro', getErrorMessage(err, 'Falha ao excluir lançamento'));
        }
    }
};

// ==================== PREVISÃO FINANCEIRA ====================

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
        const section = document.getElementById('provisaoSection');
        if (!section) return;

        // Always re-check Pro status to avoid stale cache
        await Provisao.checkProStatus();

        const overlay = document.getElementById('provisaoProOverlay');
        const isPro = Provisao.isProUser;

        // Sempre carrega dados reais (Free mostra só faturas, Pro mostra tudo)
        section.classList.remove('is-locked');
        if (overlay) overlay.style.display = 'none';

        try {
            const overview = await API.getOverview(month);
            Provisao.renderData(overview.provisao || null, isPro);
        } catch (err) {
            logClientError('Erro ao carregar provisão', err, 'Falha ao carregar previsão');
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

        // Atualizar título e link conforme plano
        const titleEl = document.getElementById('provisaoProximosTitle');
        const verTodosEl = document.getElementById('provisaoVerTodos');
        if (titleEl) {
            titleEl.innerHTML = isPro
                ? '<i data-lucide="clock"></i> Próximos Vencimentos'
                : '<i data-lucide="credit-card"></i> Próximas Faturas';
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

        // Card A Receber - só mostra dados para Pro
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

        // Contador de A Pagar com faturas de cartão
        if (pagarCount) {
            const countAgend = p.count_pagar || 0;
            const countFat = p.count_faturas || 0;

            if (isPro) {
                let pagarText = `${countAgend} pendente${countAgend !== 1 ? 's' : ''}`;
                if (countFat > 0) {
                    pagarText += ` • ${countFat} fatura${countFat !== 1 ? 's' : ''}`;
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

        // Alerta de despesas vencidas (só Pro)
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

        // Alerta de receitas vencidas (não recebidas) - só Pro
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

        // Próximos vencimentos
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
                        // Badge especial para fatura de cartão
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
                                <div class="provisao-item-titulo">${escapeHtml(item.titulo || 'Sem título')}</div>
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

        // Parcelas ativas (só Pro)
        const parcelasEl = document.getElementById('provisaoParcelas');
        const parcelas = data.parcelas || {};
        if (parcelasEl) {
            if (isPro && (parcelas.ativas || 0) > 0) {
                parcelasEl.style.display = 'flex';
                const textEl = document.getElementById('provisaoParcelasText');
                const valorEl = document.getElementById('provisaoParcelasValor');
                if (textEl) textEl.textContent = `${parcelas.ativas} parcelamento${parcelas.ativas !== 1 ? 's' : ''} ativo${parcelas.ativas !== 1 ? 's' : ''}`;
                if (valorEl) valorEl.textContent = `${money(parcelas.total_mensal || 0)}/mês`;
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
                Renderers.renderChart(month),
                Provisao.render(month)
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

        // Initialize guided tour for first-time visitors
        initDashboardTour();
    }
};

// ─── Register as Modules.App ─────────────────────────────────────────────────
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


