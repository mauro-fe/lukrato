
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
        cardsContainer: document.getElementById('transactionsCards'),
        emptyState: document.getElementById('emptyState'),

        monthLabel: document.getElementById('currentMonthText'),

        // Gamificação
        streakDays: document.getElementById('streakDays'),
        organizationPercentage: document.getElementById('organizationPercentage'),
        organizationBar: document.getElementById('organizationBar'),
        organizationText: document.getElementById('organizationText'),
        badgesGrid: document.getElementById('badgesGrid'),
        userLevel: document.getElementById('userLevel'),
        totalLancamentos: document.getElementById('totalLancamentos'),
        totalCategorias: document.getElementById('totalCategorias'),
        mesesAtivos: document.getElementById('mesesAtivos'),
        pontosTotal: document.getElementById('pontosTotal')
    };

    const STATE = {
        chartInstance: null,
        currentMonth: null,
        isLoading: false,
        gamificationData: null,
        allTransactions: []
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
    // Usa LK.api (facade unificada) quando disponível, com fallback local
    const API = {
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
            if (json?.error || json?.status === 'error') throw new Error(json?.message || 'Erro na API');
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
            const csrf = Utils.getCsrfToken();
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

                const diffDays = Math.floor((checkDate - transactionDate) / (1000 * 60 * 60 * 24));

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

            const receitas = Number(metrics?.receitasMes || 0);
            const despesas = Number(metrics?.despesasMes || 0);
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
        },

        render: (gamificationData) => {
            // NOTA: A renderização de gamificação (streak, nível, conquistas) 
            // é feita pelo gamification-dashboard.js que usa dados da API.
            // Este método apenas renderiza estatísticas básicas.
            if (!gamificationData) return;

            // Stats básicos (totalLancamentos, categorias, meses ativos)
            // são atualizados pelo gamification-dashboard.js via API
            // Não sobrescrever para evitar conflitos
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
                            ? categoriaNome
                            : '<span class="categoria-empty">Sem categoria</span>';

                        const contaNome = Utils.getContaLabel(transaction);
                        const descricao = transaction.descricao || transaction.observacao || '--';
                        const valor = Number(transaction.valor) || 0;
                        const dataBR = Utils.dateBR(transaction.data);

                        // TABELA DESKTOP
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', transaction.id);

                        tr.innerHTML = `
              <td data-label="Data">${dataBR}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${tipoClass}">${tipoLabel}</span>
              </td>
              <td data-label="Categoria">${categoriaDisplay}</td>
              <td data-label="Conta">${contaNome}</td>
              <td data-label="Descrição">${descricao}</td>
              <td data-label="Valor" class="valor-cell ${tipoClass}">${Utils.money(valor)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${transaction.id}" title="Excluir">
                    <i class="fas fa-trash-alt"></i>
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
                    <span class="transaction-badge tipo-${tipoClass}">${tipoLabel}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${categoriaNome}</span>
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

    // ==================== PREVISÃO FINANCEIRA ====================
    const Provisao = {
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

        renderPlaceholder: () => {
            const pagar = document.getElementById('provisaoPagar');
            const receber = document.getElementById('provisaoReceber');
            const projetado = document.getElementById('provisaoProjetado');
            const pagarCount = document.getElementById('provisaoPagarCount');
            const receberCount = document.getElementById('provisaoReceberCount');

            if (pagar) pagar.textContent = 'R$ 1.250,00';
            if (receber) receber.textContent = 'R$ 3.800,00';
            if (projetado) projetado.textContent = 'R$ 5.430,00';
            if (pagarCount) pagarCount.textContent = '4 agendamentos';
            if (receberCount) receberCount.textContent = '2 agendamentos';

            // Show sample upcoming items
            const list = document.getElementById('provisaoProximosList');
            if (list) {
                list.innerHTML = `
                    <div class="provisao-item">
                        <div class="provisao-item-dot despesa"></div>
                        <div class="provisao-item-info">
                            <div class="provisao-item-titulo">Aluguel</div>
                            <div class="provisao-item-meta"><span class="provisao-item-badge recorrente">Mensal</span></div>
                        </div>
                        <span class="provisao-item-valor despesa">R$ 800,00</span>
                        <span class="provisao-item-data">15/02</span>
                    </div>
                    <div class="provisao-item">
                        <div class="provisao-item-dot receita"></div>
                        <div class="provisao-item-info">
                            <div class="provisao-item-titulo">Salário</div>
                            <div class="provisao-item-meta"><span class="provisao-item-badge recorrente">Mensal</span></div>
                        </div>
                        <span class="provisao-item-valor receita">R$ 3.800,00</span>
                        <span class="provisao-item-data">05/02</span>
                    </div>
                `;
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
                    ? '<i class="fas fa-clock"></i> Próximos Vencimentos'
                    : '<i class="fas fa-credit-card"></i> Próximas Faturas';
            }
            if (verTodosEl) {
                verTodosEl.href = isPro 
                    ? `${window.BASE_URL || '/'}agendamentos`
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
                    let pagarText = `${countAgend} agendamento${countAgend !== 1 ? 's' : ''}`;
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
                if (receberCount) receberCount.textContent = `${p.count_receber || 0} agendamento${(p.count_receber || 0) !== 1 ? 's' : ''}`;
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
                            badges += '<span class="provisao-item-badge fatura"><i class="fas fa-credit-card"></i> Fatura</span>';
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
                                badges += `<span>${item.categoria}</span>`;
                            }
                        }

                        const tipoClass = isFatura ? 'fatura' : tipo;
                        const el = document.createElement('div');
                        el.className = 'provisao-item' + (isFatura ? ' is-fatura' : '');
                        el.innerHTML = `
                            <div class="provisao-item-dot ${tipoClass}"></div>
                            <div class="provisao-item-info">
                                <div class="provisao-item-titulo">${item.titulo || 'Sem título'}</div>
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

    const DashboardManager = {
        refresh: async () => {
            if (STATE.isLoading) return;

            STATE.isLoading = true;
            const month = Utils.getCurrentMonth();
            STATE.currentMonth = month;

            try {
                Renderers.updateMonthLabel(month);

                // Buscar dados para gamificação (todas as transações do usuário)
                const allTransactionsPromise = fetch(`${CONFIG.API_URL}lancamentos?limit=1000`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).then(res => res.ok ? res.json() : []).catch(() => []);

                const metricsPromise = API.getMetrics(month);

                const [allTransactionsData, metrics] = await Promise.all([
                    allTransactionsPromise,
                    metricsPromise
                ]);

                // Processar transações
                const allTransactions = Array.isArray(allTransactionsData)
                    ? allTransactionsData
                    : (allTransactionsData?.data || allTransactionsData?.lancamentos || []);

                STATE.allTransactions = allTransactions;

                // Calcular e renderizar gamificação
                const gamificationData = Gamification.calculateData(allTransactions, metrics);
                STATE.gamificationData = gamificationData;
                Gamification.render(gamificationData);

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

    const EventListeners = {
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

            document.addEventListener('lukrato:data-changed', () => {
                DashboardManager.refresh();
            });

            document.addEventListener('lukrato:month-changed', () => {
                DashboardManager.refresh();
            });

            document.addEventListener('lukrato:theme-changed', () => {
                DashboardManager.refresh();
            });

            // Fallback para quando o evento não vier: observa o atributo data-theme
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
