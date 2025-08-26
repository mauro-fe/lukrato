/* =========================================================
 * Lukrato Dashboard - JS principal (com backend PHP)
 * - Integração via API:
 *    GET  api/dashboard/metrics?month=YYYY-MM
 *    GET  api/dashboard/transactions?month=YYYY-MM&limit=50
 *    GET  api/options
 *    POST api/transactions
 * - Chart.js UMD (carregar ANTES deste arquivo)
 * ======================================================= */

/* EXPECTATIVAS NO HTML:
   <meta name="csrf-token" content="<?= h(csrf_token('default')) ?>">
   <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
*/

(() => {
    // ---------------- ESTADO GLOBAL ----------------
    let currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
    let chartInstance = null;
    let fabMenuOpen = false;

    // Estado do Month Picker
    const monthPickerState = {
        month: currentMonth,
        selectedDate: `${currentMonth}-01`,
    };

    // ---------------- API ----------------
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const api = {
        async fetchMetrics(month) {
            const r = await fetch(`${window.BASE_URL}api/dashboard/metrics?month=${encodeURIComponent(month)}`, { credentials: 'include' });
            if (!r.ok) throw new Error('Falha ao buscar métricas');
            return r.json();
        },
        async fetchTransactions(month, limit = 50) {
            const r = await fetch(`${window.BASE_URL}api/dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`, { credentials: 'include' });
            if (!r.ok) throw new Error('Falha ao buscar lançamentos');
            return r.json();
        },
        async fetchOptions() {
            const r = await fetch(`${window.BASE_URL}api/options`, { credentials: 'include' });
            if (!r.ok) throw new Error('Falha ao buscar opções');
            return r.json();
        },
        async createTransaction(payload) {
            const r = await fetch(`${window.BASE_URL}api/transactions`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                credentials: 'include',
                body: JSON.stringify(payload),
            });
            if (!r.ok) {
                const txt = await r.text().catch(() => '');
                throw new Error(`Falha ao salvar transação${txt ? `: ${txt}` : ''}`);
            }
            return r.json();
        },
    };

    // ---------------- FORMAT & DATAS ----------------
    // Função para formatar um número no formato monetário brasileiro (R$ 0,00)
    function formatMoneyBR(value) {
        // Verifica se o valor não é um número ou é NaN -> retorna "R$ 0,00"
        if (typeof value !== 'number' || isNaN(value)) return 'R$ 0,00';

        // Usa Intl.NumberFormat para formatar no padrão do Brasil
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    }

    // Função para converter uma string no formato monetário brasileiro em número
    function parseMoneyBR(moneyStr) {
        // Se for vazio ou não for string, retorna 0
        if (!moneyStr || typeof moneyStr !== 'string') return 0;

        // Remove tudo que não for dígito, vírgula ou traço
        // Exemplo: "R$ 1.234,56" -> "1234,56"
        // Depois troca a vírgula por ponto para converter em número decimal
        return parseFloat(moneyStr.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
    }

    // Aceita "YYYY-MM-DD", "YYYY-MM-DD HH:mm:ss", "YYYY-MM-DDTHH:mm:ss"
    function formatDateBR(input) {
        if (!input) return '—';

        // Garante string e separa a parte da data da parte da hora (T ou espaço)
        const [datePart] = String(input).trim().split(/[T\s]/);

        // datePart deve estar em AAAA-MM-DD; se vier com /, normaliza para -
        const normalized = datePart.replace(/\//g, '-');

        const m = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return '—';

        const [, y, mo, d] = m;
        return `${d}/${mo}/${y}`;
    }


    // Função para transformar um mês no formato ISO (AAAA-MM) em rótulo legível (ex: "agosto de 2025")
    function monthLabel(monthStr) {
        // Divide em ano e mês e transforma em número
        const [y, m] = monthStr.split('-').map(Number);

        // Cria um objeto Date no primeiro dia do mês
        const date = new Date(y, m - 1, 1);

        // Converte para string com mês e ano por extenso em pt-BR
        return date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    }

    // Função para somar ou subtrair meses a partir de uma data no formato AAAA-MM
    function clampMonth(monthStr, delta) {
        // Separa ano e mês e transforma em número
        const [y, m] = monthStr.split('-').map(Number);

        // Cria nova data somando/subtraindo "delta" meses
        const date = new Date(y, m - 1 + delta, 1);

        // Retorna no formato ISO AAAA-MM
        return date.toISOString().slice(0, 7);
    }


    // ---------------- CHART ----------------
    async function buildMonthlySeries(referenceMonth, lastN = 6) {
        const labels = [];
        const months = [];
        for (let i = lastN - 1; i >= 0; i--) {
            const m = clampMonth(referenceMonth, -i);
            labels.push(monthLabel(m));
            months.push(m);
        }
        // busca métricas em paralelo
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
        gradient.addColorStop(0, 'rgba(230, 126, 34, 0.35)');
        gradient.addColorStop(1, 'rgba(230, 126, 34, 0.05)');

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
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2C3E50',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        displayColors: false,
                        callbacks: { label: (ctx) => formatMoneyBR(ctx.parsed.y) },
                    },
                },
                scales: {
                    x: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3' } },
                    y: {
                        grid: { color: 'rgba(189,195,199,0.16)' },
                        ticks: { color: '#cfd8e3', callback: (v) => formatMoneyBR(v) },
                    },
                },
            },
        });
    }

    async function updateChart() {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas) return;
        if (!chartInstance) return buildChart();
        const { labels, series } = await buildMonthlySeries(currentMonth, 6);
        chartInstance.data.labels = labels;
        chartInstance.data.datasets[0].data = series;
        chartInstance.update();
    }

    // ---------------- RENDER ----------------
    async function renderKPIs() {
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
        } catch (err) {
            console.error(err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar KPIs' });
        }
    }

    function renderMonthLabel() {
        const label = document.getElementById('currentMonthText');
        if (label) label.textContent = monthLabel(currentMonth);
    }

    async function renderTable() {
        const tbody = document.getElementById('transactionsTableBody');
        const empty = document.getElementById('emptyState');
        if (!tbody) return;

        tbody.innerHTML = '';
        try {
            const txs = await api.fetchTransactions(currentMonth, 50);

            if (!txs.length) {
                if (empty) empty.style.display = 'block';
                return;
            }
            if (empty) empty.style.display = 'none';

            txs.forEach((t) => {
                const tr = document.createElement('tr');

                const tdData = document.createElement('td');
                tdData.textContent = formatDateBR(t.data);

                const tdTipo = document.createElement('td');
                tdTipo.textContent = String(t.tipo || '').replace('_', ' ');

                const tdCat = document.createElement('td');
                tdCat.textContent = t.categoria ? t.categoria.nome : '—';

                const tdConta = document.createElement('td');
                tdConta.textContent = '—'; // sem contas no modelo atual

                const tdDesc = document.createElement('td');
                tdDesc.textContent = t.descricao || t.observacao || '—';

                const tdValor = document.createElement('td');
                tdValor.style.fontWeight = '700';
                tdValor.style.textAlign = 'right';
                tdValor.style.color = (t.tipo === 'receita') ? 'var(--verde)' :
                    (String(t.tipo || '').startsWith('despesa') ? 'var(--vermelho)' : 'var(--laranja)');
                tdValor.textContent = formatMoneyBR(Number(t.valor) || 0);

                tr.appendChild(tdData);
                tr.appendChild(tdTipo);
                tr.appendChild(tdCat);
                tr.appendChild(tdConta);
                tr.appendChild(tdDesc);
                tr.appendChild(tdValor);
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error(err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar lançamentos' });
        }
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
    function renderMonthPicker() {
        const grid = document.getElementById('calendarGrid');
        const label = document.getElementById('mpLabel');
        if (!grid || !label) return;

        label.textContent = monthLabel(monthPickerState.month);

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
                [...grid.querySelectorAll('.calendar-day')].forEach((el) => el.classList.remove('selected'));
                btn.classList.add('selected');
                label.textContent = monthLabel(monthPickerState.month);
            });

            grid.appendChild(btn);
        });
    }
    function bindMonthPickerControls() {
        const prev = document.getElementById('mpPrev');
        const next = document.getElementById('mpNext');
        const confirm = document.getElementById('mpConfirm');

        if (prev) prev.addEventListener('click', () => {
            monthPickerState.month = clampMonth(monthPickerState.month, -1);
            renderMonthPicker();
        });
        if (next) next.addEventListener('click', () => {
            monthPickerState.month = clampMonth(monthPickerState.month, +1);
            renderMonthPicker();
        });
        if (confirm) confirm.addEventListener('click', async () => {
            currentMonth = monthPickerState.month;
            renderMonthLabel();
            await renderKPIs();
            await renderTable();
            await updateChart();
            closeMonthPicker();
        });
        document.querySelectorAll('[data-close-month]').forEach((el) => {
            el.addEventListener('click', closeMonthPicker);
        });
    }

    // ---------------- CONTROLES / NAV ----------------
    function bindControls() {
        const prev = document.getElementById('prevMonth');
        const next = document.getElementById('nextMonth');
        const openBtn = document.getElementById('monthDropdownBtn');

        if (prev) prev.addEventListener('click', async () => {
            currentMonth = clampMonth(currentMonth, -1);
            renderMonthLabel(); await renderKPIs(); await renderTable(); await updateChart();
        });
        if (next) next.addEventListener('click', async () => {
            currentMonth = clampMonth(currentMonth, +1);
            renderMonthLabel(); await renderKPIs(); await renderTable(); await updateChart();
        });
        if (openBtn) openBtn.addEventListener('click', (e) => { e.preventDefault(); openMonthPicker(); });

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
    }

    // ---------------- EXPORT CSV ----------------
    async function exportToCSV(month) {
        try {
            const rows = await api.fetchTransactions(month, 1000);
            const header = ['Data', 'Tipo', 'Categoria', 'Conta/Cartão', 'Descrição', 'Valor'];
            const csv = [header.join(';')]
                .concat(rows.map((t) => [
                    formatDateBR(t.data),
                    t.tipo || '',
                    (t.categoria ? t.categoria.nome : ''),
                    '', // sem contas/cartões por enquanto
                    (t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
                    (Number(t.valor) || 0).toString().replace('.', ','),
                ].join(';'))).join('\r\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = `lukrato-${month}.csv`;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error(err);
            Swal?.fire?.({ icon: 'error', title: 'Erro ao exportar CSV' });
        }
    }

    // ---------------- MODAIS: helpers + popula selects ----------------
    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('active');
        el.setAttribute('aria-hidden', 'false');

        if (id === 'modalReceita') { populateReceita(); }
        if (id === 'modalDespesa') { populateDespesa(); }
        if (id === 'modalDespesaCartao') { populateDespesaCartao(); }
        if (id === 'modalTransferencia') { populateTransferencia(); }

        refreshMoneyMasks();
        setDefaultTodayDates(el);
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('active');
        el.setAttribute('aria-hidden', 'true');
    }
    function setDefaultTodayDates(scope) {
        const today = new Date().toISOString().slice(0, 10);
        scope.querySelectorAll('input[type="date"]').forEach((inp) => { if (!inp.value) inp.value = today; });
    }

    let OPTIONS_CACHE = null;
    async function ensureOptions() {
        if (!OPTIONS_CACHE) OPTIONS_CACHE = await api.fetchOptions();
        return OPTIONS_CACHE;
    }

    async function populateReceita() {
        const selCat = document.getElementById('receitaCategoria');
        const { categorias } = await ensureOptions();
        const all = categorias?.receitas || [];
        selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
            all.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    }

    async function populateDespesa() {
        const selCat = document.getElementById('despesaCategoria');
        const { categorias } = await ensureOptions();
        const all = categorias?.despesas || [];
        selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
            all.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    }

    async function populateDespesaCartao() {
        const selCat = document.getElementById('despesaCartaoCategoria');
        const { categorias } = await ensureOptions();
        const all = categorias?.despesas || [];
        selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
            all.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    }



    async function populateTransferencia() {
        const selOrigem = document.getElementById('transferenciaOrigem');
        const selDestino = document.getElementById('transferenciaDestino');
        if (selOrigem) { selOrigem.removeAttribute('required'); selOrigem.innerHTML = '<option value="">—</option>'; }
        if (selDestino) { selDestino.removeAttribute('required'); selDestino.innerHTML = '<option value="">—</option>'; }
    }

    // ---------------- Máscara de moeda ----------------
    function refreshMoneyMasks() {
        if (window.jQuery && $.fn.inputmask) {
            $('.money-mask').inputmask({
                alias: 'currency',
                prefix: 'R$ ',
                groupSeparator: '.',
                radixPoint: ',',
                digits: 2,
                autoGroup: true,
                rightAlign: false,
            });
        }
    }

    // ---------------- SUBMIT DOS FORMULÁRIOS ----------------
    function bindForms() {
        // RECEITA
        const f1 = document.getElementById('formReceita');
        if (f1) f1.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const payload = {
                    tipo: 'receita',
                    data: document.getElementById('receitaData').value,
                    categoria_id: Number(document.getElementById('receitaCategoria').value) || null,
                    descricao: document.getElementById('receitaDescricao').value.trim(),
                    observacao: (document.getElementById('receitaObservacao')?.value || '').trim(),
                    valor: parseMoneyBR(document.getElementById('receitaValor').value),
                };
                await api.createTransaction(payload);
                closeModal('modalReceita');
                await afterDataChange();
                Swal?.fire?.({ icon: 'success', title: 'Receita salva!', timer: 1300, showConfirmButton: false });
            } catch (err) {
                console.error(err);
                Swal?.fire?.({ icon: 'error', title: 'Erro ao salvar receita' });
            }
        });

        // DESPESA
        const f2 = document.getElementById('formDespesa');
        if (f2) f2.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const payload = {
                    tipo: 'despesa',
                    data: document.getElementById('despesaData').value,
                    categoria_id: Number(document.getElementById('despesaCategoria').value) || null,
                    descricao: document.getElementById('despesaDescricao').value.trim(),
                    observacao: (document.getElementById('despesaObservacao')?.value || '').trim(),
                    valor: parseMoneyBR(document.getElementById('despesaValor').value),
                };
                await api.createTransaction(payload);
                closeModal('modalDespesa');
                await afterDataChange();
                Swal?.fire?.({ icon: 'success', title: 'Despesa salva!', timer: 1300, showConfirmButton: false });
            } catch (err) {
                console.error(err);
                Swal?.fire?.({ icon: 'error', title: 'Erro ao salvar despesa' });
            }
        });

        // DESPESA CARTÃO (salva como despesa comum por enquanto)
        const f3 = document.getElementById('formDespesaCartao');
        if (f3) f3.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const payload = {
                    tipo: 'despesa',
                    data: document.getElementById('despesaCartaoData').value,
                    categoria_id: Number(document.getElementById('despesaCartaoCategoria').value) || null,
                    descricao: document.getElementById('despesaCartaoDescricao').value.trim(),
                    observacao: (document.getElementById('despesaCartaoObservacao')?.value || '').trim(),
                    valor: parseMoneyBR(document.getElementById('despesaCartaoValor').value),
                };
                await api.createTransaction(payload);
                closeModal('modalDespesaCartao');
                await afterDataChange();
                Swal?.fire?.({ icon: 'success', title: 'Despesa salva!', timer: 1300, showConfirmButton: false });
            } catch (err) {
                console.error(err);
                Swal?.fire?.({ icon: 'error', title: 'Erro ao salvar despesa' });
            }
        });


        // TRANSFERÊNCIA (placeholder: não persiste — só fecha e avisa)
        const f4 = document.getElementById('formTransferencia');
        if (f4) f4.addEventListener('submit', async (e) => {
            e.preventDefault();
            closeModal('modalTransferencia');
            Swal?.fire?.({ icon: 'info', title: 'Funcionalidade de transferência pendente' });
        });
    }

    async function afterDataChange() {
        await renderKPIs();
        await renderTable();
        await updateChart();
    }

    // ---------------- INIT ----------------
    async function initDashboard() {
        renderMonthLabel();
        await Promise.all([renderKPIs(), renderTable()]);
        await buildChart();

        bindControls();
        bindForms();
        bindMonthPickerControls();
        refreshMoneyMasks();
    }

    document.addEventListener('DOMContentLoaded', () => { initDashboard(); });
})();
