import { resolveCardsSummaryEndpoint, resolveFinanceSummaryEndpoint } from '../api/endpoints/finance.js';
import { resolveLancamentoEndpoint, resolveLancamentosBulkDeleteEndpoint } from '../api/endpoints/lancamentos.js';

/**
 * ============================================================================
 * LUKRATO - Dashboard / Foundation
 * ============================================================================
 * API, Notifications and Gamification extracted from app.js.
 * ============================================================================
 */

export function createDashboardFoundation({
    getDashboardOverview,
    getApiPayload,
    apiGet,
    apiDelete,
    apiPost,
    getErrorMessage,
}) {
    function syncDemoPreviewBanner(_meta) {
        window.LKDemoPreviewBanner?.hide();
    }

    const API = {
        getOverview: async (month, options = {}) => {
            const response = await getDashboardOverview(month, options);
            const payload = getApiPayload(response, {});
            syncDemoPreviewBanner(payload?.meta);
            return payload;
        },

        fetch: async (url) => {
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

            const response = await apiGet(resolveFinanceSummaryEndpoint(), {
                ano: Number(match[1]),
                mes: Number(match[2]),
            });

            return getApiPayload(response, {});
        },

        getCardsSummary: async () => {
            const response = await apiGet(resolveCardsSummaryEndpoint());
            return getApiPayload(response, {});
        },

        deleteTransaction: async (id) => {
            const endpoints = [
                { request: () => apiDelete(resolveLancamentoEndpoint(id)) },
                { request: () => apiPost(resolveLancamentosBulkDeleteEndpoint(), { id }) }
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

    const Notifications = {
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

    const Gamification = {
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

    return {
        API,
        Notifications,
        Gamification,
    };
}
