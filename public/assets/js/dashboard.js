/* =========================================================
 * Lukrato Dashboard - JS principal (com backend PHP)
 * (versão separada do header)
 * ======================================================= */
(() => {
    // ---------------- ESTADO GLOBAL ----------------
    // [HEADER SPLIT] O mês vem do header
    let currentMonth = (window.LukratoHeader?.getMonth && window.LukratoHeader.getMonth()) ||
        new Date().toISOString().slice(0, 7);
    let chartInstance = null;
    let fabMenuOpen = false;

    // Estado do Month Picker
    const monthPickerState = {
        month: currentMonth,
        selectedDate: `${currentMonth}-01`,
    };

    // ---------------- API ----------------
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const api = { /* ... (sem mudanças) ... */
        async fetchMetrics(month) { const r = await fetch(`${window.BASE_URL}api/dashboard/metrics?month=${encodeURIComponent(month)}`, { credentials: 'include' }); if (!r.ok) throw new Error('Falha ao buscar métricas'); return r.json(); },
        async fetchTransactions(month, limit = 50) { const r = await fetch(`${window.BASE_URL}api/dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`, { credentials: 'include' }); if (!r.ok) throw new Error('Falha ao buscar lançamentos'); return r.json(); },
        async fetchOptions() { const r = await fetch(`${window.BASE_URL}api/options`, { credentials: 'include' }); if (!r.ok) throw new Error('Falha ao buscar opções'); return r.json(); },
        async createTransaction(payload) { const r = await fetch(`${window.BASE_URL}api/transactions`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, credentials: 'include', body: JSON.stringify(payload) }); if (!r.ok) { const txt = await r.text().catch(() => ''); throw new Error(`Falha ao salvar transação${txt ? `: ${txt}` : ''}`); } return r.json(); },
    };

    // ---------------- FORMAT & DATAS ----------------
    function formatMoneyBR(v) { if (typeof v !== 'number' || isNaN(v)) return 'R$ 0,00'; return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v); }
    function parseMoneyBR(s) { if (!s || typeof s !== 'string') return 0; return parseFloat(s.replace(/[^\d,-]/g, '').replace(',', '.')) || 0; }
    function formatDateBR(input) {
        if (!input) return '—';
        const [datePart] = String(input).trim().split(/[T\s]/);
        const normalized = datePart.replace(/\//g, '-');
        const m = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return '—';
        const [, y, mo, d] = m;
        return `${d}/${mo}/${y}`;
    }
    // [HEADER SPLIT] monthLabel/clampMonth agora ficam no header. Se precisar, use window.LukratoHeader.monthLabel/clampMonth.

    // ---------------- CHART ----------------
    async function buildMonthlySeries(referenceMonth, lastN = 6) {
        const labels = []; const months = [];
        for (let i = lastN - 1; i >= 0; i--) {
            const m = window.LukratoHeader?.clampMonth ? window.LukratoHeader.clampMonth(referenceMonth, -i)
                : (() => { const [y, mm] = referenceMonth.split('-').map(Number); const d = new Date(y, mm - 1 - i, 1); return d.toISOString().slice(0, 7); })();
            labels.push(window.LukratoHeader?.monthLabel ? window.LukratoHeader.monthLabel(m) : m);
            months.push(m);
        }
        const results = await Promise.all(months.map((m) => api.fetchMetrics(m).catch(() => ({ resultado: 0 }))));
        const series = results.map((k) => Number(((k?.resultado) || 0).toFixed(2)));
        return { labels, series };
    }

    async function buildChart() {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const { labels, series } = await buildMonthlySeries(currentMonth, 6);

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(230,126,34,0.35)');
        gradient.addColorStop(1, 'rgba(230,126,34,0.05)');

        if (chartInstance) chartInstance.destroy();

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
                            label: (ctx) => formatMoneyBR(ctx.parsed.y)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(189,195,199,0.16)' },
                        ticks: { color: '#cfd8e3' }
                    },
                    y: {
                        grid: { color: 'rgba(189,195,199,0.16)' },
                        ticks: {
                            color: '#cfd8e3',
                            callback: (v) => formatMoneyBR(v)
                        }
                    }
                }
            }
        });
    }

    async function updateChart() {
        const canvas = document.getElementById('evolutionChart'); if (!canvas) return;
        if (!chartInstance) return buildChart();
        const { labels, series } = await buildMonthlySeries(currentMonth, 6);
        chartInstance.data.labels = labels;
        chartInstance.data.datasets[0].data = series;
        chartInstance.update();
    }

    // ---------------- RENDER ----------------
    async function renderKPIs() { /* ... igual ao seu ... */
        try {
            const k = await api.fetchMetrics(currentMonth);
            const saldo = document.getElementById('saldoValue');
            const rec = document.getElementById('receitasValue');
            const des = document.getElementById('despesasValue');
            if (saldo) saldo.textContent = formatMoneyBR(k.saldo);
            if (rec) rec.textContent = formatMoneyBR(k.receitas);
            if (des) des.textContent = formatMoneyBR(k.despesas);
            const sRec = document.getElementById('totalReceitas');
            const sDes = document.getElementById('totalDespesas');
            const sRes = document.getElementById('resultadoMes');
            const sAc = document.getElementById('saldoAcumulado');
            if (sRec) sRec.textContent = formatMoneyBR(k.receitas);
            if (sDes) sDes.textContent = formatMoneyBR(k.despesas);
            if (sRes) sRes.textContent = formatMoneyBR(k.resultado);
            if (sAc) sAc.textContent = formatMoneyBR(k.saldoAcumulado);
        } catch (err) { console.error(err); Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar KPIs' }); }
    }

    async function renderTable() { /* ... igual ao seu ... */
        const tbody = document.getElementById('transactionsTableBody');
        const empty = document.getElementById('emptyState');
        if (!tbody) return;
        tbody.innerHTML = '';
        try {
            const txs = await api.fetchTransactions(currentMonth, 50);
            if (!txs.length) { if (empty) empty.style.display = 'block'; return; }
            if (empty) empty.style.display = 'none';
            txs.forEach((t) => {
                const tr = document.createElement('tr');
                const tdData = document.createElement('td'); tdData.textContent = formatDateBR(t.data);
                const tdTipo = document.createElement('td'); tdTipo.textContent = String(t.tipo || '').replace('_', ' ');
                const tdCat = document.createElement('td'); tdCat.textContent = t.categoria ? t.categoria.nome : '—';
                const tdConta = document.createElement('td'); tdConta.textContent = '—';
                const tdDesc = document.createElement('td'); tdDesc.textContent = t.descricao || t.observacao || '—';
                const tdValor = document.createElement('td');
                tdValor.style.fontWeight = '700'; tdValor.style.textAlign = 'right';
                tdValor.style.color = (t.tipo === 'receita') ? 'var(--verde)' :
                    (String(t.tipo || '').startsWith('despesa') ? 'var(--vermelho)' : 'var(--laranja)');
                tdValor.textContent = formatMoneyBR(Number(t.valor) || 0);
                tr.append(tdData, tdTipo, tdCat, tdConta, tdDesc, tdValor);
                tbody.appendChild(tr);
            });
        } catch (err) { console.error(err); Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar lançamentos' }); }
    }

    // ---------------- MONTH PICKER (MODAL) ----------------
    function openMonthPicker() {
        monthPickerState.month = currentMonth;
        monthPickerState.selectedDate = `${currentMonth}-01`;
        renderMonthPicker();
        const modal = document.getElementById('monthPickerModal');
        if (modal) { modal.classList.add('active'); modal.setAttribute('aria-hidden', 'false'); }
    }
    function closeMonthPicker() {
        const modal = document.getElementById('monthPickerModal');
        if (modal) { modal.classList.remove('active'); modal.setAttribute('aria-hidden', 'true'); }
    }
    function renderMonthPicker() { /* ... igual ao seu ... */
        const grid = document.getElementById('calendarGrid');
        const label = document.getElementById('mpLabel');
        if (!grid || !label) return;
        // usa monthLabel do header para o label do modal
        label.textContent = window.LukratoHeader?.monthLabel
            ? window.LukratoHeader.monthLabel(monthPickerState.month)
            : monthPickerState.month;

        const [year, month] = monthPickerState.month.split('-').map(Number);
        const first = new Date(year, month - 1, 1);
        const firstWeekday = first.getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const prevMonthDays = new Date(year, month - 1, 0).getDate();

        const cells = [];
        for (let i = 0; i < firstWeekday; i++) {
            const d = new Date(year, month - 2, prevMonthDays - (firstWeekday - 1 - i));
            cells.push({ date: d.toISOString().slice(0, 10), muted: true });
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const iso = new Date(year, month - 1, d).toISOString().slice(0, 10);
            cells.push({ date: iso, muted: false });
        }
        while (cells.length % 7 !== 0) {
            const last = new Date(cells[cells.length - 1].date);
            last.setDate(last.getDate() + 1);
            cells.push({ date: last.toISOString().slice(0, 10), muted: true });
        }

        grid.innerHTML = '';
        const todayIso = new Date().toISOString().slice(0, 10);
        cells.forEach((c) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'calendar-day' + (c.muted ? ' muted' : '') + (c.date === todayIso ? ' today' : '');
            btn.textContent = c.date.split('-')[2];
            btn.dataset.date = c.date;
            if (monthPickerState.selectedDate === c.date) btn.classList.add('selected');
            btn.addEventListener('click', () => {
                monthPickerState.selectedDate = c.date;
                monthPickerState.month = c.date.slice(0, 7);
                [...grid.querySelectorAll('.calendar-day')].forEach(el => el.classList.remove('selected'));
                btn.classList.add('selected');
                label.textContent = window.LukratoHeader?.monthLabel
                    ? window.LukratoHeader.monthLabel(monthPickerState.month)
                    : monthPickerState.month;
            });
            grid.appendChild(btn);
        });
    }
    function bindMonthPickerControls() {
        const prev = document.getElementById('mpPrev');
        const next = document.getElementById('mpNext');
        const confirm = document.getElementById('mpConfirm');

        if (prev) prev.addEventListener('click', () => {
            monthPickerState.month = window.LukratoHeader?.clampMonth
                ? window.LukratoHeader.clampMonth(monthPickerState.month, -1)
                : monthPickerState.month;
            renderMonthPicker();
        });
        if (next) next.addEventListener('click', () => {
            monthPickerState.month = window.LukratoHeader?.clampMonth
                ? window.LukratoHeader.clampMonth(monthPickerState.month, +1)
                : monthPickerState.month;
            renderMonthPicker();
        });
        if (confirm) confirm.addEventListener('click', async () => {
            // [HEADER SPLIT] confirma no header, que avisará o dashboard via evento
            window.LukratoHeader?.setMonth(monthPickerState.month);
            closeMonthPicker();
        });
        document.querySelectorAll('[data-close-month]').forEach((el) => {
            el.addEventListener('click', closeMonthPicker);
        });
    }

    // ---------------- CONTROLES / NAV ----------------
    function bindControls() {
        // [HEADER SPLIT] prev/next e abrir picker ficam no header.
        const btnExport = document.getElementById('exportBtn');
        if (btnExport) btnExport.addEventListener('click', () => exportToCSV(currentMonth));

        const fab = document.getElementById('fabButton');
        const menu = document.getElementById('fabMenu');
        if (fab && menu) {
            fab.addEventListener('click', () => {
                fabMenuOpen = !fabMenuOpen;
                fab.classList.toggle('active', fabMenuOpen);
                fab.setAttribute('aria-expanded', String(fabMenuOpen));
                menu.classList.toggle('active', fabMenuOpen);
            });
        }

        // FAB -> abrir modais
        document.querySelectorAll('.fab-menu-item[data-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const which = btn.getAttribute('data-modal');
                const id = (which === 'despesa-cartao') ? 'modalDespesaCartao'
                    : (which === 'despesa') ? 'modalDespesa'
                        : (which === 'receita') ? 'modalReceita'
                            : 'modalTransferencia';
                openModal(id);
            });
        });

        // Fechar modais
        document.querySelectorAll('.modal .modal-close, .modal .modal-backdrop, .modal [data-dismiss="modal"]').forEach((el) => {
            el.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal') || e.target.parentElement.closest('.modal');
                if (modal) closeModal(modal.id);
            });
        });

        // [HEADER SPLIT] abrir picker vindo do header
        document.addEventListener('lukrato:open-month-picker', openMonthPicker);
    }

    // ---------------- EXPORT CSV ----------------
    async function exportToCSV(month) { /* ... igual ao seu ... */
        try {
            const rows = await api.fetchTransactions(month, 1000);
            const header = ['Data', 'Tipo', 'Categoria', 'Conta/Cartão', 'Descrição', 'Valor'];
            const csv = [header.join(';')]
                .concat(rows.map(t => [
                    formatDateBR(t.data),
                    t.tipo || '',
                    (t.categoria ? t.categoria.nome : ''),
                    '',
                    (t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
                    (Number(t.valor) || 0).toString().replace('.', ','),
                ].join(';'))).join('\r\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a'); a.href = url; a.download = `lukrato-${month}.csv`;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (err) { console.error(err); Swal?.fire?.({ icon: 'error', title: 'Erro ao exportar CSV' }); }
    }

    // ---------------- MODAIS / OPTIONS / MASKS / FORMS ----------------
    // (mantém igual ao seu)
    function openModal(id) { /* ... */ }
    function closeModal(id) { /* ... */ }
    function setDefaultTodayDates(scope) { /* ... */ }
    let OPTIONS_CACHE = null;
    async function ensureOptions() { if (!OPTIONS_CACHE) OPTIONS_CACHE = await api.fetchOptions(); return OPTIONS_CACHE; }
    async function populateReceita() { /* ... */ }
    async function populateDespesa() { /* ... */ }
    async function populateDespesaCartao() { /* ... */ }
    async function populateTransferencia() { /* ... */ }
    function refreshMoneyMasks() { /* ... */ }
    function bindForms() { /* ... (igual ao seu) ... */ }
    async function afterDataChange() { await renderKPIs(); await renderTable(); await updateChart(); }

    // ---------------- INIT ----------------
    async function initDashboard() {
        // [HEADER SPLIT] rótulo é do header; aqui só renderiza dados do mês atual
        currentMonth = (window.LukratoHeader?.getMonth && window.LukratoHeader.getMonth()) || currentMonth;

        await Promise.all([renderKPIs(), renderTable()]);
        await buildChart();

        bindControls();
        bindForms();
        bindMonthPickerControls();
        refreshMoneyMasks();

        // Reage a mudanças de mês feitas pelo header
        document.addEventListener('lukrato:month-changed', async (e) => {
            currentMonth = e.detail?.month || currentMonth;
            await renderKPIs();
            await renderTable();
            await updateChart();
        });
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
})();
