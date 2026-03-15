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
    escapeHtml,
    getCSRFToken
} from './state.js';

// ==================== API ====================
// Usa LK.api (facade unificada) quando disponível, com fallback local

export const API = {
    fetch: async (url) => {
        if (window.LK?.api) {
            const res = await LK.api.get(url);
            if (!res.ok) throw new Error(res.message || 'Erro na API');
            return res.raw || res.data;
        }
        // fallback para fetch nativo
        const response = await fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const json = await response.json();
        if (json?.success === false) throw new Error(json?.message || 'Erro na API');
        return json;
    },

    getMetrics: async (month) => {
        return await API.fetch(
            `${CONFIG.API_URL}dashboard/metrics?month=${encodeURIComponent(month)}`
        );
    },

    getAccountsBalances: async (month) => {
        return await API.fetch(
            `${CONFIG.API_URL}contas?with_balances=1&month=${encodeURIComponent(month)}&only_active=1`
        );
    },

    getTransactions: async (month, limit) => {
        const url1 = `${CONFIG.API_URL}lancamentos?month=${encodeURIComponent(month)}&limit=${limit}`;
        try {
            const data = await API.fetch(url1);
            return Array.isArray(data) ? data : (data.items || data.data || data.lancamentos || []);
        } catch {
            const url2 = `${CONFIG.API_URL}dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`;
            return await API.fetch(url2);
        }
    },

    deleteTransaction: async (id) => {
        if (window.LK?.api) {
            // Tenta o endpoint primário via facade
            const res = await LK.api.delete(`${CONFIG.API_URL}lancamentos/${id}`);
            if (res.ok) return res.raw || res.data;
            throw new Error(res.message || 'Erro ao excluir');
        }
        // Fallback com múltiplos endpoints
        const csrf = getCSRFToken();
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(csrf ? { 'X-CSRF-Token': csrf } : {})
        };
        const endpoints = [
            { url: `${CONFIG.API_URL}lancamentos/${id}`, method: 'DELETE' },
            { url: `${CONFIG.API_URL}lancamentos/${id}/delete`, method: 'POST' },
            { url: `${CONFIG.API_URL}lancamentos/delete`, method: 'POST', body: JSON.stringify({ id }) }
        ];
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint.url, { credentials: 'include', headers, method: endpoint.method, body: endpoint.body });
                if (response.ok) return await response.json();
                if (response.status !== 404) {
                    const json = await response.json().catch(() => ({}));
                    throw new Error(json?.message || `HTTP ${response.status}`);
                }
            } catch (err) {
                if (endpoint === endpoints[endpoints.length - 1]) throw err;
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

            // SEMPRE usar a soma dos saldos das contas
            const saldoFinal = totalSaldoContas;

            if (DOM.saldoValue) {
                DOM.saldoValue.textContent = Utils.money(saldoFinal);
            }

            Utils.removeLoadingClass();
        } catch (err) {
            console.error('Erro ao renderizar KPIs:', err);
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
            console.error('Erro ao renderizar transações:', err);

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

            const results = await Promise.allSettled(
                months.map(m => API.getMetrics(m))
            );

            const data = results.map(result =>
                result.status === 'fulfilled' ? Number(result.value?.resultado || 0) : 0
            );

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
            console.error('Erro ao renderizar gráfico:', err);
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
            Notifications.error('Erro', err.message || 'Falha ao excluir lançamento');
        }
    }
};

// ==================== PREVISÃO FINANCEIRA ====================

export const Provisao = {
    isProUser: null,

    checkProStatus: async () => {
        // Ensure PlanLimits has finished loading before checking
        if (window.PlanLimits?.init) {
            try {
                await window.PlanLimits.init();
            } catch { /* ignore */ }
        }

        if (window.PlanLimits?.isPro) {
            Provisao.isProUser = window.PlanLimits.isPro();
            return Provisao.isProUser;
        }

        // Fallback: fetch directly from API
        try {
            const data = await API.fetch(`${CONFIG.API_URL}plan/limits`);
            Provisao.isProUser = data?.is_pro === true;
        } catch {
            try {
                const data = await API.fetch(`${CONFIG.API_URL}gamification/progress`);
                Provisao.isProUser = data?.data?.is_pro === true;
            } catch {
                Provisao.isProUser = false;
            }
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
            const data = await API.fetch(
                `${CONFIG.API_URL}dashboard/provisao?month=${encodeURIComponent(month)}&is_pro=${isPro ? '1' : '0'}`
            );
            Provisao.renderData(data, isPro);
        } catch (err) {
            console.error('Erro ao carregar provisão:', err);
        }
    },

    renderData: (data, isPro = true) => {
        if (!data) return;

        const p = data.provisao || {};
        const money = Utils.money;

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
    refresh: async () => {
        if (STATE.isLoading) return;

        STATE.isLoading = true;
        const month = Utils.getCurrentMonth();
        STATE.currentMonth = month;

        try {
            Renderers.updateMonthLabel(month);

            await Promise.allSettled([
                Renderers.renderKPIs(month),
                Renderers.renderTable(month),
                Renderers.renderChart(month),
                Provisao.render(month)
            ]);
        } catch (err) {
            console.error('Erro ao atualizar dashboard:', err);
        } finally {
            STATE.isLoading = false;
        }
    },

    init: async () => {
        await DashboardManager.refresh();
    }
};

// ==================== EVENT LISTENERS ====================

export const EventListeners = {
    init: () => {
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
            DashboardManager.refresh();
        });

        document.addEventListener('lukrato:month-changed', () => {
            DashboardManager.refresh();
        });

        document.addEventListener('lukrato:theme-changed', () => {
            DashboardManager.refresh();
        });
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
