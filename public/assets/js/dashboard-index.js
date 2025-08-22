// Dashboard JavaScript - Lukrato
class DashboardManager {
    constructor() {
        this.currentMonth = window.dashboardChart.currentMonth;
        this.chart = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupChart();
        this.setupAnimations();
    }

    setupEventListeners() {
        // Navegação de mês
        document.getElementById('prev-month')?.addEventListener('click', () => {
            this.navigateMonth(-1);
        });

        document.getElementById('next-month')?.addEventListener('click', () => {
            this.navigateMonth(1);
        });

        // Botão de exportar
        document.getElementById('export-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleExport();
        });

        // Botão de novo lançamento
        document.getElementById('novo-lancamento-btn')?.addEventListener('click', () => {
            this.handleNewTransaction();
        });

        document.getElementById('primeiro-lancamento-btn')?.addEventListener('click', () => {
            this.handleNewTransaction();
        });

        // Ripple effect nos botões
        this.setupRippleEffect();
    }

    setupChart() {
        // O gráfico já é inicializado no HTML, só precisamos manter a referência
        this.chart = Chart.getChart('financialChart');
    }

    navigateMonth(direction) {
        if (this.isLoading) return;

        const currentDate = new Date(this.currentMonth + '-01');
        currentDate.setMonth(currentDate.getMonth() + direction);

        const newMonth = currentDate.getFullYear() + '-' +
            String(currentDate.getMonth() + 1).padStart(2, '0');

        this.loadMonth(newMonth);
    }

    async loadMonth(month) {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch(`/admin/${this.getUsername()}/home/dashboard/metrics?mes=${month}`);
            const data = await response.json();

            if (data.ok) {
                this.currentMonth = month;
                this.updateUI(data);
                this.updateURL(month);
            } else {
                this.showError('Erro ao carregar dados do mês');
            }
        } catch (error) {
            console.error('Erro ao buscar métricas:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    updateUI(data) {
        // Atualiza KPIs
        this.updateKPIs(data.kpis);

        // Atualiza gráfico
        this.updateChart(data.chart);

        // Atualiza tabela de transações
        this.updateTransactions(data.ultimos);

        // Atualiza seletor de mês
        this.updateMonthSelector();
    }

    updateKPIs(kpis) {
        // Receitas
        const receitasEl = document.getElementById('receitas-value');
        if (receitasEl) {
            receitasEl.textContent = this.formatMoney(kpis.receitas);
        }

        // Despesas
        const despesasEl = document.getElementById('despesas-value');
        if (despesasEl) {
            despesasEl.textContent = this.formatMoney(kpis.despesas);
        }

        // Saldo total
        const saldoEl = document.getElementById('saldo-value');
        const saldoChangeEl = document.getElementById('saldo-change');
        if (saldoEl) {
            saldoEl.textContent = this.formatMoney(kpis.saldo_total);

            // Atualiza classe e ícone do saldo
            if (saldoChangeEl) {
                const isPositive = kpis.saldo_total >= 0;
                saldoChangeEl.className = `kpi-change ${isPositive ? 'positive' : 'negative'}`;
                saldoChangeEl.innerHTML = `
                    <i class="fas fa-${isPositive ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${isPositive ? 'Saldo positivo' : 'Saldo negativo'}</span>
                `;
            }
        }

        // Atualiza resumo mensal
        this.updateSummary(kpis);
    }

    updateSummary(kpis) {
        const summaryReceitas = document.getElementById('summary-receitas');
        const summaryDespesas = document.getElementById('summary-despesas');
        const summaryResultado = document.getElementById('summary-resultado');
        const summarySaldo = document.getElementById('summary-saldo');

        if (summaryReceitas) {
            summaryReceitas.textContent = this.formatMoney(kpis.receitas);
        }

        if (summaryDespesas) {
            summaryDespesas.textContent = this.formatMoney(kpis.despesas);
        }

        if (summaryResultado) {
            const resultado = kpis.resultado_mes;
            summaryResultado.textContent = this.formatMoney(resultado);
            summaryResultado.style.color = resultado >= 0 ? 'var(--verde-claro)' : '#E74C3C';
        }

        if (summarySaldo) {
            const saldo = kpis.saldo_total;
            summarySaldo.textContent = this.formatMoney(saldo);
            summarySaldo.style.color = saldo >= 0 ? 'var(--verde-claro)' : '#E74C3C';
        }
    }

    updateChart(chartData) {
        if (!this.chart) return;

        // Atualiza dados do gráfico sem recriar
        this.chart.data.labels = chartData.labels;
        this.chart.data.datasets[0].data = chartData.data;
        this.chart.update('active');
    }

    updateTransactions(transactions) {
        const container = document.getElementById('transactions-container');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h4>Nenhum lançamento encontrado</h4>
                    <p>Comece adicionando suas primeiras receitas e despesas para acompanhar sua evolução financeira.</p>
                    <button class="action-button" style="margin-top: 1rem;" onclick="dashboardManager.handleNewTransaction()">
                        <i class="fas fa-plus"></i>
                        Adicionar Primeiro Lançamento
                    </button>
                </div>
            `;
        } else {
            let tableHTML = `
                <div style="overflow-x: auto;">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-alt"></i> Data</th>
                                <th><i class="fas fa-tag"></i> Tipo</th>
                                <th><i class="fas fa-folder-open"></i> Categoria</th>
                                <th><i class="fas fa-dollar-sign"></i> Valor</th>
                                <th><i class="fas fa-comment-alt"></i> Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            transactions.forEach(t => {
                const tipoClass = t.tipo === 'receita' ? 'receita' : 'despesa';
                const tipoIcon = t.tipo === 'receita' ? 'arrow-up' : 'arrow-down';
                const tipoLabel = t.tipo === 'receita' ? 'Receita' : 'Despesa';
                const valorColor = t.tipo === 'receita' ? 'var(--verde-claro)' : '#E74C3C';

                tableHTML += `
                    <tr>
                        <td style="font-weight: 500;">${this.formatDate(t.data)}</td>
                        <td>
                            <span class="tipo-badge tipo-${tipoClass}">
                                <i class="fas fa-${tipoIcon}"></i>
                                ${tipoLabel}
                            </span>
                        </td>
                        <td style="font-weight: 500;">${t.categoria || '—'}</td>
                        <td style="font-weight: 700; color: ${valorColor}">
                            ${this.formatMoney(t.valor)}
                        </td>
                        <td style="color: var(--texto-secundario);">${t.descricao || '—'}</td>
                    </tr>
                `;
            });

            tableHTML += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = tableHTML;
        }
    }

    updateMonthSelector() {
        const monthText = document.getElementById('current-month-text');
        if (monthText) {
            const date = new Date(this.currentMonth + '-01');
            const monthName = date.toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
            monthText.textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);
        }
    }

    updateURL(month) {
        const url = new URL(window.location);
        url.searchParams.set('mes', month);
        window.history.pushState({}, '', url);
    }

    showLoading() {
        const kpiContainer = document.getElementById('kpi-container');
        if (kpiContainer) {
            kpiContainer.classList.add('loading-animation');
        }

        // Desabilita botões de navegação
        const prevBtn = document.getElementById('prev-month');
        const nextBtn = document.getElementById('next-month');
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;
    }

    hideLoading() {
        const kpiContainer = document.getElementById('kpi-container');
        if (kpiContainer) {
            kpiContainer.classList.remove('loading-animation');
        }

        // Reabilita botões de navegação
        const prevBtn = document.getElementById('prev-month');
        const nextBtn = document.getElementById('next-month');
        if (prevBtn) prevBtn.disabled = false;
        if (nextBtn) nextBtn.disabled = false;
    }

    handleExport() {
        const exportUrl = `/export/csv?mes=${this.currentMonth}`;
        window.open(exportUrl, '_blank');
    }

    handleNewTransaction() {
        Swal.fire({
            title: 'Novo Lançamento',
            text: 'A funcionalidade de criação de lançamentos será implementada na próxima etapa do projeto.',
            icon: 'info',
            confirmButtonText: 'Entendi',
            confirmButtonColor: '#E67E22',
            background: '#2C3E50',
            color: '#FFFFFF'
        });
    }

    showError(message) {
        Swal.fire({
            title: 'Erro',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#E67E22',
            background: '#2C3E50',
            color: '#FFFFFF'
        });
    }

    setupAnimations() {
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', () => {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
        });
    }

    setupRippleEffect() {
        document.querySelectorAll('.action-button, .month-nav-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                // Efeito de ripple
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.pointerEvents = 'none';

                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    formatDate(dateString) {
        if (!dateString) return '—';

        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        } catch (e) {
            return '—';
        }
    }

    getUsername() {
        // Extrai username da URL atual
        const pathParts = window.location.pathname.split('/');
        const adminIndex = pathParts.indexOf('admin');
        return adminIndex !== -1 && pathParts[adminIndex + 1] ? pathParts[adminIndex + 1] : 'user';
    }
}

// CSS para animação de ripple (adiciona dinamicamente)
if (!document.getElementById('dashboard-styles')) {
    const style = document.createElement('style');
    style.id = 'dashboard-styles';
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .loading-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        .month-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .swal2-popup {
            border-radius: 20px !important;
        }
        
        .swal2-confirm {
            border-radius: 12px !important;
            font-weight: 600 !important;
        }
    `;
    document.head.appendChild(style);
}

// Inicializa o gerenciador do dashboard
const dashboardManager = new DashboardManager();