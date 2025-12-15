
(() => {
    'use strict';

    if (window.__LK_DASHBOARD_LOADER__) return;
    window.__LK_DASHBOARD_LOADER__ = true;

    const CONFIG = {
        BASE_URL: (() => {
            const meta = document.querySelector('meta[name="base-url"]')?.content || '';
            let base = meta;
            if (!base) {
                const m = location.pathname.match(/^(.*\/public\/)/);
                base = m ? (location.origin + m[1]) : (location.origin + '/');
            }
            if (base && !/\/public\/?$/.test(base)) {
                const m2 = location.pathname.match(/^(.*\/public\/)/);
                if (m2) base = location.origin + m2[1];
            }
            return base.replace(/\/?$/, '/');
        })(),
        TRANSACTIONS_LIMIT: 5,
        CHART_MONTHS: 6,
        ANIMATION_DELAY: 300
    };

    CONFIG.API_URL = `${CONFIG.BASE_URL}api/`;

    const DOM = {
        // KPIs
        saldoValue: document.getElementById('saldoValue'),
        receitasValue: document.getElementById('receitasValue'),
        despesasValue: document.getElementById('despesasValue'),
        saldoMesValue: document.getElementById('saldoMesValue'),

        chartCanvas: document.getElementById('evolutionChart'),
        chartLoading: document.getElementById('chartLoading'),

        tableBody: document.getElementById('transactionsTableBody'),
        table: document.getElementById('transactionsTable'),
        emptyState: document.getElementById('emptyState'),

        monthLabel: document.getElementById('currentMonthText')
    };

    const STATE = {
        chartInstance: null,
        currentMonth: null,
        isLoading: false
    };

    const getThemeColors = () => {
        const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
            || Utils?.isLightTheme?.();
        return {
            isLightTheme,
            axisColor: isLightTheme
                ? (Utils.getCssVar('--color-primary', '#e67e22') || '#e67e22')
                : 'rgba(255, 255, 255, 0.6)',
            yTickColor: isLightTheme ? '#000' : '#fff',
            xTickColor: isLightTheme
                ? (Utils.getCssVar('--color-text-muted', '#6c757d') || '#6c757d')
                : 'rgba(255, 255, 255, 0.6)',
            gridColor: isLightTheme
                ? 'rgba(0, 0, 0, 0.08)'
                : 'rgba(255, 255, 255, 0.05)',
            tooltipBg: isLightTheme ? 'rgba(255, 255, 255, 0.92)' : 'rgba(0, 0, 0, 0.85)',
            tooltipColor: isLightTheme ? '#0f172a' : '#f8fafc',
            labelColor: isLightTheme ? '#0f172a' : '#f8fafc'
        };
    };

    const Utils = {
        money: (n) => {
            try {
                return Number(n || 0).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
            } catch {
                return 'R$ 0,00';
            }
        },

        dateBR: (iso) => {
            if (!iso) return '-';
            try {
                const d = String(iso).split(/[T\s]/)[0];
                const m = d.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                return m ? `${m[3]}/${m[2]}/${m[1]}` : '-';
            } catch {
                return '-';
            }
        },

        formatMonth: (monthStr) => {
            try {
                const [y, m] = String(monthStr).split('-').map(Number);
                return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                    month: 'long',
                    year: 'numeric'
                });
            } catch {
                return '-';
            }
        },

        formatMonthShort: (monthStr) => {
            try {
                const [y, m] = String(monthStr).split('-').map(Number);
                return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                    month: 'short'
                });
            } catch {
                return '-';
            }
        },

        getCsrfToken: () => {
            return document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="csrf_token"]')?.value || '';
        },

        getCurrentMonth: () => {
            return window.LukratoHeader?.getMonth?.() ||
                new Date().toISOString().slice(0, 7);
        },

        getPreviousMonths: (currentMonth, count) => {
            const months = [];
            const [y, m] = currentMonth.split('-').map(Number);

            for (let i = count - 1; i >= 0; i--) {
                const date = new Date(y, m - 1 - i, 1);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                months.push(`${year}-${month}`);
            }

            return months;
        },

        getCssVar: (name, fallback = '') => {
            try {
                const value = getComputedStyle(document.documentElement).getPropertyValue(name);
                return (value || '').trim() || fallback;
            } catch {
                return fallback;
            }
        },

        isLightTheme: () => {
            try {
                return (document.documentElement?.getAttribute('data-theme') || 'dark') === 'light';
            } catch {
                return false;
            }
        },

        getContaLabel: (transaction) => {
            if (typeof transaction.conta === 'string' && transaction.conta.trim()) {
                return transaction.conta.trim();
            }

            const origem = transaction.conta_instituicao ??
                transaction.conta_nome ??
                transaction.conta?.instituicao ??
                transaction.conta?.nome ?? null;

            const destino = transaction.conta_destino_instituicao ??
                transaction.conta_destino_nome ??
                transaction.conta_destino?.instituicao ??
                transaction.conta_destino?.nome ?? null;

            if (transaction.eh_transferencia && (origem || destino)) {
                return `${origem || '-'}${destino || '-'}`;
            }

            if (transaction.conta_label && String(transaction.conta_label).trim()) {
                return String(transaction.conta_label).trim();
            }

            return origem || '-';
        },

        getTipoClass: (tipo) => {
            const normalized = String(tipo || '').toLowerCase();
            if (normalized === 'receita') return 'receita';
            if (normalized.includes('despesa')) return 'despesa';
            if (normalized.includes('transferencia')) return 'transferencia';
            return '';
        },

        removeLoadingClass: () => {
            setTimeout(() => {
                document.querySelectorAll('.kpi-value.loading').forEach(el => {
                    el.classList.remove('loading');
                });
            }, CONFIG.ANIMATION_DELAY);
        }
    };

    // ==================== API ====================
    const API = {
        fetch: async (url) => {
            const response = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const json = await response.json();

            if (json?.error || json?.status === 'error') {
                throw new Error(json?.message || json?.error || 'Erro na API');
            }

            return json;
        },

        getMetrics: async (month) => {
            return await API.fetch(
                `${CONFIG.API_URL}dashboard/metrics?month=${encodeURIComponent(month)}`
            );
        },

        getAccountsBalances: async (month) => {
            return await API.fetch(
                `${CONFIG.API_URL}accounts?with_balances=1&month=${encodeURIComponent(month)}&only_active=1`
            );
        },

        getTransactions: async (month, limit) => {
            const url1 = `${CONFIG.API_URL}lancamentos?month=${encodeURIComponent(month)}&limit=${limit}`;

            try {
                const data = await API.fetch(url1);
                return Array.isArray(data) ? data : (data.items || data.data || data.lancamentos || []);
            } catch {
                const url2 =
                    `${CONFIG.API_URL}dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`;
                return await API.fetch(url2);
            }
        },

        deleteTransaction: async (id) => {
            const csrf = Utils.getCsrfToken();
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? {
                    'X-CSRF-Token': csrf
                } : {})
            };

            const endpoints = [{
                url: `${CONFIG.API_URL}lancamentos/${id}`,
                method: 'DELETE'
            },
            {
                url: `${CONFIG.API_URL}lancamentos/${id}/delete`,
                method: 'POST'
            },
            {
                url: `${CONFIG.API_URL}lancamentos/delete`,
                method: 'POST',
                body: JSON.stringify({
                    id
                })
            }
            ];

            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(endpoint.url, {
                        credentials: 'include',
                        headers,
                        method: endpoint.method,
                        body: endpoint.body
                    });

                    if (response.ok) {
                        return await response.json();
                    }

                    if (response.status !== 404) {
                        const json = await response.json().catch(() => ({}));
                        throw new Error(json?.message || `HTTP ${response.status}`);
                    }
                } catch (err) {
                    if (endpoint === endpoints[endpoints.length - 1]) {
                        throw err;
                    }
                }
            }

            throw new Error('Endpoint de exclusão encontrado.');
        }
    };

    const Notifications = {
        ensureSwal: async () => {
            if (window.Swal) return;
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        toast: (icon, title) => {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: false,
                icon,
                title
            });
        },

        loading: (title = 'Processando...') => {
            window.Swal.fire({
                title,
                didOpen: () => window.Swal.showLoading(),
                allowOutsideClick: false,
                showConfirmButton: false
            });
        },

        close: () => {
            window.Swal.close();
        },

        confirm: async (title, text) => {
            const result = await window.Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--color-danger)',
                cancelButtonColor: 'var(--color-text-muted)'
            });
            return result.isConfirmed;
        },

        error: (title, text) => {
            window.Swal.fire({
                icon: 'error',
                title,
                text,
                confirmButtonColor: 'var(--color-primary)'
            });
        }
    };

    const Renderers = {
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

                const saldoFinal = Number.isFinite(saldoAcumulado) ? saldoAcumulado : totalSaldoContas;

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

                DOM.tableBody.innerHTML = '';

                const hasData = Array.isArray(transactions) && transactions.length > 0;

                if (DOM.emptyState) {
                    DOM.emptyState.style.display = hasData ? 'none' : 'block';
                }

                if (DOM.table) {
                    DOM.table.style.display = hasData ? 'table' : 'none';
                }

                if (hasData) {
                    transactions.forEach(transaction => {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', transaction.id);

                        const tipo = String(transaction.tipo || '').toLowerCase();
                        const tipoClass = Utils.getTipoClass(tipo);
                        const tipoLabel = String(transaction.tipo || '').replace(/_/g, ' ');

                        const categoriaNome = transaction.categoria_nome ??
                            (typeof transaction.categoria === 'string' ? transaction.categoria :
                                transaction.categoria?.nome) ??
                            '-';

                        const contaNome = Utils.getContaLabel(transaction);
                        const descricao = transaction.descricao || transaction.observacao || '--';
                        const valor = Number(transaction.valor) || 0;

                        tr.innerHTML = `
              <td data-label="Data">${Utils.dateBR(transaction.data)}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${tipoClass}">${tipoLabel}</span>
              </td>
              <td data-label="Categoria">${categoriaNome}</td>
              <td data-label="Conta">${contaNome}</td>
              <td data-label="Descrição">${descricao}</td>
              <td data-label="Valor" class="valor-cell ${tipoClass}">${Utils.money(valor)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${transaction.id}" title="Excluir">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            `;

                        DOM.tableBody.appendChild(tr);
                    });
                }
            } catch (err) {
                console.error('Erro ao renderizar tabela:', err);

                if (DOM.emptyState) {
                    DOM.emptyState.style.display = 'block';
                }

                if (DOM.table) {
                    DOM.table.style.display = 'none';
                }
            }
        },

        renderChart: async (month) => {
            if (!DOM.chartCanvas || typeof Chart === 'undefined') return;

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

                const ctx = DOM.chartCanvas.getContext('2d');

                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(230, 126, 34, 0.35)');
                gradient.addColorStop(1, 'rgba(230, 126, 34, 0.05)');

                const chartData = {
                    labels,
                    datasets: [{
                        label: 'Resultado do Mês',
                        data,
                        borderColor: '#E67E22',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#E67E22',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.35
                    }]
                };

                const {
                    axisColor,
                    yTickColor,
                    xTickColor,
                    gridColor,
                    tooltipBg,
                    tooltipColor,
                    labelColor
                } = getThemeColors();

                const options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false,
                            labels: {
                                color: labelColor
                            }
                        },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            titleColor: tooltipColor,
                            bodyColor: tooltipColor,
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: (context) => {
                                    return `Resultado: ${Utils.money(context.parsed.y)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: yTickColor,
                                callback: (value) => Utils.money(value)
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: xTickColor
                            }
                        }
                    }
                };

                if (STATE.chartInstance) {
                    STATE.chartInstance.destroy();
                }

                STATE.chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options
                });
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

    const TransactionManager = {
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

                await DashboardManager.refresh();

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

    const DashboardManager = {
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
                    Renderers.renderChart(month)
                ]);
            } catch (err) {
                console.error('Erro ao atualizar dashboard:', err);
            } finally {
                STATE.isLoading = false;
            }
        },

        init: async () => {
            console.log('Inicializando Dashboard...');
            await DashboardManager.refresh();
            console.log('Dashboard carregado com sucesso!');
        }
    };

    const EventListeners = {
        init: () => {
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

            document.addEventListener('lukrato:data-changed', () => {
                DashboardManager.refresh();
            });

            document.addEventListener('lukrato:month-changed', () => {
                DashboardManager.refresh();
            });

            document.addEventListener('lukrato:theme-changed', () => {
                DashboardManager.refresh();
            });

            // Fallback para quando o evento n‹o vier: observa o atributo data-theme
            const observer = new MutationObserver(() => {
                DashboardManager.refresh();
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        }
    };

    const init = () => {
        EventListeners.init();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', DashboardManager.init);
        } else {
            DashboardManager.init();
        }
    };

    window.refreshDashboard = DashboardManager.refresh;
    window.LK = window.LK || {};
    window.LK.refreshDashboard = DashboardManager.refresh;

    init();
})();
