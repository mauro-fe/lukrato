/* =========================================================
 * Lukrato Dashboard - JS principal (com backend PHP)
 * (versão separada do header)
 * ======================================================= */
(() => {
    // ---------------- Estado Global ----------------
    let currentMonth = window.LukratoHeader?.getMonth?.() || new Date().toISOString().slice(0, 7);
    let chartInstance = null;
    let fabMenuOpen = false;
    let optionsCache = null;

    // Estado do Month Picker
    const monthPickerState = {
        month: currentMonth,
        selectedDate: `${currentMonth}-01`,
    };

    // ---------------- API ----------------
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const BASE_URL = window.BASE_URL || '/';
    const API_URL = (path) => `${BASE_URL}api/${path}`;

    const api = {
        async fetchMetrics(month) {
            return (await fetchAPI(`dashboard/metrics?month=${encodeURIComponent(month)}`)).json();
        },
        async fetchTransactions(month, limit = 50) {
            return (await fetchAPI(`dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`)).json();
        },
        async fetchOptions() {
            return (await fetchAPI('options')).json();
        },
        async createTransaction(payload) {
            return (await fetchAPI('transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify(payload),
            })).json();
        },
    };

    async function fetchAPI(path, options = {}) {
        const response = await fetch(API_URL(path), { ...options, credentials: 'include' });
        if (!response.ok) {
            const errorText = await response.text().catch(() => '');
            throw new Error(`Falha ao buscar dados: ${response.statusText}${errorText ? ` - ${errorText}` : ''}`);
        }
        return response;
    }

    // ---------------- Formatos & Datas ----------------
    const format = {
        money(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
        },
        parseMoney(string) {
            return parseFloat(String(string || '').replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
        },
        date(input) {
            if (!input) return '—';
            const datePart = String(input).split(/[T\s]/)[0];
            const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return match ? `${match[3]}/${match[2]}/${match[1]}` : '—';
        },
    };

    // ---------------- Gráfico ----------------
    async function getMonthlySeries(referenceMonth, count = 6) {
        const months = Array.from({ length: count }, (_, i) => {
            const monthOffset = i - (count - 1);
            return window.LukratoHeader?.clampMonth?.(referenceMonth, monthOffset) ?? new Date(new Date(referenceMonth).getFullYear(), new Date(referenceMonth).getMonth() + monthOffset, 1).toISOString().slice(0, 7);
        });

        const labels = months.map(month => window.LukratoHeader?.monthLabel?.(month) ?? month);
        const results = await Promise.all(months.map(m => api.fetchMetrics(m).catch(() => ({ resultado: 0 }))));
        const series = results.map(k => Number(k.resultado || 0).toFixed(2));
        return { labels, series };
    }

    async function updateChart() {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas) return;

        const { labels, series } = await getMonthlySeries(currentMonth, 6);

        if (!chartInstance) {
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(230,126,34,0.35)');
            gradient.addColorStop(1, 'rgba(230,126,34,0.05)');

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Resultado do Mês',
                        data: series,
                        borderColor: '#E67E22',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#E67E22',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2C3E50',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => format.money(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3' } },
                        y: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3', callback: (v) => format.money(v) } }
                    }
                }
            });
        } else {
            chartInstance.data.labels = labels;
            chartInstance.data.datasets[0].data = series;
            chartInstance.update();
        }
    }

    // ---------------- Renderização ----------------
    async function renderKPIs() {
        try {
            const kpis = await api.fetchMetrics(currentMonth);
            const elements = {
                saldoValue: 'saldo',
                receitasValue: 'receitas',
                despesasValue: 'despesas',
                totalReceitas: 'receitas',
                totalDespesas: 'despesas',
                resultadoMes: 'resultado',
                saldoAcumulado: 'saldoAcumulado'
            };
            Object.entries(elements).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el) el.textContent = format.money(kpis[key]);
            });
        } catch (err) {
            console.error('Erro ao carregar KPIs:', err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar KPIs' });
        }
    }

    async function renderTable() {
        const tbody = document.getElementById('transactionsTableBody');
        const emptyState = document.getElementById('emptyState');
        if (!tbody) return;

        try {
            const transactions = await api.fetchTransactions(currentMonth, 50);
            tbody.innerHTML = '';
            emptyState.style.display = transactions.length ? 'none' : 'block';

            transactions.forEach(t => {
                const tr = document.createElement('tr');
                const valueColor = (t.tipo === 'receita') ? 'var(--verde)' : (String(t.tipo).startsWith('despesa') ? 'var(--vermelho)' : 'var(--laranja)');

                tr.innerHTML = `
                    <td>${format.date(t.data)}</td>
                    <td>${String(t.tipo || '').replace('_', ' ')}</td>
                    <td>${t.categoria?.nome || '—'}</td>
                    <td>—</td>
                    <td>${t.descricao || t.observacao || '—'}</td>
                    <td style="font-weight: 700; text-align: right; color: ${valueColor};">${format.money(Number(t.valor) || 0)}</td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('Erro ao carregar lançamentos:', err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar lançamentos' });
        }
    }

    // ---------------- Month Picker (Modal) ----------------
    function openMonthPicker() {
        Object.assign(monthPickerState, { month: currentMonth, selectedDate: `${currentMonth}-01` });
        renderMonthPicker();
        toggleModal('monthPickerModal', true);
    }
    function closeMonthPicker() {
        toggleModal('monthPickerModal', false);
    }

    function renderMonthPicker() {
        const grid = document.getElementById('calendarGrid');
        const label = document.getElementById('mpLabel');
        if (!grid || !label) return;

        label.textContent = window.LukratoHeader?.monthLabel?.(monthPickerState.month) ?? monthPickerState.month;

        const [year, month] = monthPickerState.month.split('-').map(Number);
        const firstDay = new Date(year, month - 1, 1);
        const firstWeekday = firstDay.getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const cells = [];
        const todayIso = new Date().toISOString().slice(0, 10);

        // Dias do mês anterior
        const prevMonthLastDay = new Date(year, month - 1, 0).getDate();
        for (let i = firstWeekday - 1; i >= 0; i--) {
            cells.push({ date: new Date(year, month - 2, prevMonthLastDay - i).toISOString().slice(0, 10), muted: true });
        }
        // Dias do mês atual
        for (let d = 1; d <= daysInMonth; d++) {
            cells.push({ date: new Date(year, month - 1, d).toISOString().slice(0, 10), muted: false });
        }
        // Dias do próximo mês
        while (cells.length % 7 !== 0) {
            const lastDate = new Date(cells.at(-1).date);
            lastDate.setDate(lastDate.getDate() + 1);
            cells.push({ date: lastDate.toISOString().slice(0, 10), muted: true });
        }

        grid.innerHTML = cells.map(c => `
            <button
                type="button"
                class="calendar-day ${c.muted ? 'muted' : ''} ${c.date === todayIso ? 'today' : ''} ${c.date === monthPickerState.selectedDate ? 'selected' : ''}"
                data-date="${c.date}">
                ${c.date.split('-')[2]}
            </button>
        `).join('');

        grid.querySelectorAll('.calendar-day').forEach(btn => {
            btn.addEventListener('click', () => {
                const date = btn.dataset.date;
                monthPickerState.selectedDate = date;
                monthPickerState.month = date.slice(0, 7);
                grid.querySelector('.selected')?.classList.remove('selected');
                btn.classList.add('selected');
                label.textContent = window.LukratoHeader?.monthLabel?.(monthPickerState.month) ?? monthPickerState.month;
            });
        });
    }

    function bindMonthPickerControls() {
        document.getElementById('mpPrev')?.addEventListener('click', () => {
            monthPickerState.month = window.LukratoHeader?.clampMonth?.(monthPickerState.month, -1) ?? monthPickerState.month;
            renderMonthPicker();
        });
        document.getElementById('mpNext')?.addEventListener('click', () => {
            monthPickerState.month = window.LukratoHeader?.clampMonth?.(monthPickerState.month, 1) ?? monthPickerState.month;
            renderMonthPicker();
        });
        document.getElementById('mpConfirm')?.addEventListener('click', () => {
            window.LukratoHeader?.setMonth?.(monthPickerState.month);
            closeMonthPicker();
        });
        document.querySelectorAll('[data-close-month]').forEach(el => el.addEventListener('click', closeMonthPicker));
    }

    // ---------------- Controles & Modais ----------------
    function toggleModal(id, isOpen) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.toggle('active', isOpen);
        modal.setAttribute('aria-hidden', String(!isOpen));
        if (isOpen) {
            const modalType = id.replace('modal', '').toLowerCase();
            const populators = {
                receita: populateReceita,
                despesa: populateDespesa,
                despesacartao: populateDespesaCartao,
                transferencia: populateTransferencia
            };
            populators[modalType]?.();
            refreshMoneyMasks();
            setDefaultTodayDates(modal);
        }
    }

    function setDefaultTodayDates(scope) {
        const today = new Date().toISOString().slice(0, 10);
        scope.querySelectorAll('input[type="date"]').forEach(inp => {
            if (!inp.value) inp.value = today;
        });
    }

    async function ensureOptions() {
        if (!optionsCache) {
            optionsCache = await api.fetchOptions().catch(err => {
                console.error('Erro ao buscar opções:', err);
                return {};
            });
        }
        return optionsCache;
    }

    async function populateSelect(selectId, categoryType) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const { categorias = {} } = await ensureOptions();
        const options = categorias[categoryType] || [];
        select.innerHTML = `<option value="">Selecione uma categoria</option>${options.map(c => `<option value="${c.id}">${c.nome}</option>`).join('')}`;
    }

    function populateReceita() { populateSelect('receitaCategoria', 'receitas'); }
    function populateDespesa() { populateSelect('despesaCategoria', 'despesas'); }
    function populateDespesaCartao() { populateSelect('despesaCartaoCategoria', 'despesas'); }
    async function populateTransferencia() {
        const { contas = [] } = await ensureOptions();
        const optionsHtml = contas.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
        const selOrigem = document.getElementById('transferenciaOrigem');
        const selDestino = document.getElementById('transferenciaDestino');
        if (selOrigem) { selOrigem.innerHTML = `<option value="">Selecione a origem</option>${optionsHtml}`; }
        if (selDestino) { selDestino.innerHTML = `<option value="">Selecione o destino</option>${optionsHtml}`; }
    }

    function refreshMoneyMasks() {
        window.jQuery?.fn?.inputmask && $('.money-mask').inputmask({ alias: 'currency', prefix: 'R$ ', groupSeparator: '.', radixPoint: ',', digits: 2, autoGroup: true, rightAlign: false });
    }

    // ---------------- Submissão de Formulários ----------------
    function bindForms() {
        document.getElementById('formReceita')?.addEventListener('submit', createTransactionHandler('receita', 'modalReceita', 'Receita salva!'));
        document.getElementById('formDespesa')?.addEventListener('submit', createTransactionHandler('despesa', 'modalDespesa', 'Despesa salva!'));
        document.getElementById('formDespesaCartao')?.addEventListener('submit', createTransactionHandler('despesa', 'modalDespesaCartao', 'Despesa salva!'));
        document.getElementById('formTransferencia')?.addEventListener('submit', (e) => {
            e.preventDefault();
            toggleModal('modalTransferencia', false);
            Swal?.fire?.({ icon: 'info', title: 'Funcionalidade de transferência pendente' });
        });
    }

    function createTransactionHandler(type, modalId, successTitle) {
        return async (e) => {
            e.preventDefault();
            try {
                const form = e.target;
                const payload = {
                    tipo: type,
                    data: form.querySelector(`[data-field="data"]`).value,
                    categoria_id: Number(form.querySelector(`[data-field="categoria_id"]`).value) || null,
                    descricao: form.querySelector(`[data-field="descricao"]`).value.trim(),
                    observacao: (form.querySelector(`[data-field="observacao"]`)?.value || '').trim(),
                    valor: format.parseMoney(form.querySelector(`[data-field="valor"]`).value),
                };
                await api.createTransaction(payload);
                toggleModal(modalId, false);
                await afterDataChange();
                Swal?.fire?.({ icon: 'success', title: successTitle, timer: 1300, showConfirmButton: false });
            } catch (err) {
                console.error('Erro ao salvar transação:', err);
                Swal?.fire?.({ icon: 'error', title: 'Erro ao salvar transação' });
            }
        };
    }

    async function afterDataChange() {
        await Promise.all([renderKPIs(), renderTable(), updateChart()]);
    }

    // ---------------- Exportar CSV ----------------
    async function exportToCSV(month) {
        try {
            const transactions = await api.fetchTransactions(month, 1000);
            const header = ['Data', 'Tipo', 'Categoria', 'Conta/Cartão', 'Descrição', 'Valor'];
            const rows = transactions.map(t => [
                format.date(t.data),
                t.tipo || '',
                t.categoria?.nome || '',
                '', // Campo de conta/cartão não está na API, mantido vazio
                (t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
                (Number(t.valor) || 0).toFixed(2).replace('.', ',')
            ].join(';'));
            const csv = [header.join(';'), ...rows].join('\r\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = Object.assign(document.createElement('a'), { href: url, download: `lukrato-${month}.csv` });
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Erro ao exportar CSV:', err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao exportar CSV' });
        }
    }

    // ---------------- Inicialização ----------------
    function bindControls() {
        document.getElementById('exportBtn')?.addEventListener('click', () => exportToCSV(currentMonth));
        const fab = document.getElementById('fabButton');
        const fabMenu = document.getElementById('fabMenu');
        if (fab && fabMenu) {
            fab.addEventListener('click', () => {
                fabMenuOpen = !fabMenuOpen;
                fab.classList.toggle('active', fabMenuOpen);
                fab.setAttribute('aria-expanded', fabMenuOpen);
                fabMenu.classList.toggle('active', fabMenuOpen);
            });
        }

        document.querySelectorAll('.fab-menu-item[data-modal]').forEach(btn => {
            btn.addEventListener('click', () => toggleModal(`modal${btn.dataset.modal.replace(/(^|-)(\w)/g, (_, a, b) => b.toUpperCase())}`, true));
        });

        document.querySelectorAll('.modal .modal-close, .modal .modal-backdrop, .modal [data-dismiss="modal"]').forEach(el => {
            el.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) toggleModal(modal.id, false);
            });
        });

        document.addEventListener('lukrato:open-month-picker', openMonthPicker);
    }

    async function initDashboard() {
        currentMonth = window.LukratoHeader?.getMonth?.() || currentMonth;
        await Promise.all([renderKPIs(), renderTable(), updateChart()]);

        bindControls();
        bindForms();
        bindMonthPickerControls();
        refreshMoneyMasks();

        document.addEventListener('lukrato:month-changed', async (e) => {
            currentMonth = e.detail?.month || currentMonth;
            await afterDataChange();
        });
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
})();