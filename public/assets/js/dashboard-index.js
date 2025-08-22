/* =========================================================
 * Lukrato Dashboard - JS principal (compatível com seu HTML)
 * - Month Picker (modal) integrado ao cabeçalho
 * - FAB abre modais (receita/despesa/despesa-cartão/transferência)
 * - Máscara de moeda + submits para salvar mock
 * - IDs casados com o HTML enviado
 * ======================================================= */

// ---------------- ESTADO GLOBAL ----------------
let currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
let chartInstance = null;
let fabMenuOpen = false;

// Estado do Month Picker
let monthPickerState = {
    month: currentMonth,        // YYYY-MM
    selectedDate: `${currentMonth}-01`
};

// ---------------- MOCK / SEED ----------------
const mockData = {
    contas: [
        { id: 1, nome: 'Conta Corrente', tipo: 'corrente', saldo: 2500.00 },
        { id: 2, nome: 'Poupança', tipo: 'poupanca', saldo: 15000.00 },
        { id: 3, nome: 'Carteira', tipo: 'dinheiro', saldo: 200.00 }
    ],
    categorias: {
        receita: [
            { id: 1, nome: 'Salário', icone: 'fas fa-briefcase' },
            { id: 2, nome: 'Freelance', icone: 'fas fa-laptop' },
            { id: 3, nome: 'Investimentos', icone: 'fas fa-chart-line' },
            { id: 4, nome: 'Outros', icone: 'fas fa-plus' }
        ],
        despesa: [
            { id: 5, nome: 'Alimentação', icone: 'fas fa-utensils' },
            { id: 6, nome: 'Transporte', icone: 'fas fa-car' },
            { id: 7, nome: 'Moradia', icone: 'fas fa-home' },
            { id: 8, nome: 'Saúde', icone: 'fas fa-heartbeat' },
            { id: 9, nome: 'Educação', icone: 'fas fa-graduation-cap' },
            { id: 10, nome: 'Lazer', icone: 'fas fa-gamepad' },
            { id: 11, nome: 'Compras', icone: 'fas fa-shopping-bag' },
            { id: 12, nome: 'Outros', icone: 'fas fa-ellipsis-h' }
        ]
    },
    cartoes: [
        { id: 1, nome: 'Nubank', bandeira: 'mastercard', limite: 5000.00 },
        { id: 2, nome: 'Itaú Visa', bandeira: 'visa', limite: 3000.00 },
        { id: 3, nome: 'Bradesco Elo', bandeira: 'elo', limite: 2000.00 }
    ],
    transacoes: [
        { id: 1, tipo: 'receita', data: '2024-01-15', categoria_id: 1, conta_id: 1, descricao: 'Salário Janeiro', valor: 4500.00, pago: true },
        { id: 2, tipo: 'despesa', data: '2024-01-16', categoria_id: 5, conta_id: 1, descricao: 'Supermercado', valor: 250.00, pago: true },
        { id: 3, tipo: 'despesa_cartao', data: '2024-01-17', categoria_id: 11, cartao_id: 1, descricao: 'Compras Amazon', valor: 150.00, parcelas: 1 },
        { id: 4, tipo: 'transferencia', data: '2024-01-18', conta_origem_id: 1, conta_destino_id: 2, valor: 1000.00, observacao: 'Poupança mensal' }
    ]
};

// ---------------- API (futuro backend) ----------------
const api = {
    async fetchMetrics(month) { return computeKPIs(month); },
    async createTransaction(payload) { return saveTransaction(payload.tipo, payload); },
    async fetchTransactions(month, limit = 10) { return getLastTransactions(month, limit); }
};

// ---------------- FORMAT & DATAS ----------------
function formatMoneyBR(value) {
    if (typeof value !== 'number' || isNaN(value)) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
function parseMoneyBR(moneyStr) {
    if (!moneyStr || typeof moneyStr !== 'string') return 0;
    return parseFloat(moneyStr.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
}
function getMonthRange(monthStr) {
    const [year, month] = monthStr.split('-').map(Number);
    const start = new Date(year, month - 1, 1);
    const end = new Date(year, month, 0);
    return { start: start.toISOString().split('T')[0], end: end.toISOString().split('T')[0] };
}
function formatDateBR(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}
function monthLabel(monthStr) {
    const [y, m] = monthStr.split('-').map(Number);
    const date = new Date(y, m - 1, 1);
    return date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
}
function clampMonth(monthStr, delta) {
    const [y, m] = monthStr.split('-').map(Number);
    const date = new Date(y, m - 1 + delta, 1);
    return date.toISOString().slice(0, 7);
}

// ---------------- STORAGE / SEED ----------------
const LS_KEYS = {
    CONTAS: 'lukrato_contas',
    CATEGORIAS: 'lukrato_categorias',
    CARTOES: 'lukrato_cartoes',
    TRANSACOES: 'lukrato_transacoes',
    SEEDED: 'lukrato_seeded'
};
function ensureSeed() {
    if (localStorage.getItem(LS_KEYS.SEEDED)) return;
    localStorage.setItem(LS_KEYS.CONTAS, JSON.stringify(mockData.contas));
    localStorage.setItem(LS_KEYS.CATEGORIAS, JSON.stringify(mockData.categorias));
    localStorage.setItem(LS_KEYS.CARTOES, JSON.stringify(mockData.cartoes));
    localStorage.setItem(LS_KEYS.TRANSACOES, JSON.stringify(mockData.transacoes));
    localStorage.setItem(LS_KEYS.SEEDED, '1');
}
function getContasFromStorage() { try { return JSON.parse(localStorage.getItem(LS_KEYS.CONTAS)) || []; } catch { return []; } }
function setContasToStorage(contas) { localStorage.setItem(LS_KEYS.CONTAS, JSON.stringify(contas || [])); }
function getCategoriasFromStorage() { try { return JSON.parse(localStorage.getItem(LS_KEYS.CATEGORIAS)) || { receita: [], despesa: [] }; } catch { return { receita: [], despesa: [] }; } }
function getCartoesFromStorage() { try { return JSON.parse(localStorage.getItem(LS_KEYS.CARTOES)) || []; } catch { return []; } }
function getTransactionsFromStorage() { try { return JSON.parse(localStorage.getItem(LS_KEYS.TRANSACOES)) || []; } catch { return []; } }
function setTransactionsToStorage(transacoes) { localStorage.setItem(LS_KEYS.TRANSACOES, JSON.stringify(transacoes || [])); }

// ---------------- UTIL ----------------
function uid() { return Math.floor(Date.now() / 1000) + Math.floor(Math.random() * 1000); }
function getCategoriaNome(tipo, categoria_id) {
    const cats = getCategoriasFromStorage()[tipo] || [];
    const found = cats.find(c => c.id === categoria_id);
    return found ? found.nome : '—';
}
function getContaNome(conta_id) {
    const contas = getContasFromStorage();
    const found = contas.find(c => c.id === conta_id);
    return found ? found.nome : '—';
}

// ---------------- CÁLCULOS ----------------
function computeKPIs(month) {
    const { start, end } = getMonthRange(month);
    const transacoes = getTransactionsFromStorage().filter(t => t.data >= start && t.data <= end);

    let receitas = 0, despesas = 0;
    transacoes.forEach(t => {
        if (t.tipo === 'receita' && t.pago) receitas += t.valor;
        else if ((t.tipo === 'despesa' || t.tipo === 'despesa_cartao') && t.pago !== false) despesas += t.valor;
    });

    const contas = getContasFromStorage();
    const saldoTotal = contas.reduce((acc, c) => acc + c.saldo, 0);
    return { saldo: saldoTotal, receitas, despesas, resultado: receitas - despesas, saldoAcumulado: saldoTotal };
}
function getLastTransactions(month, limit = 10) {
    const { start, end } = getMonthRange(month);
    const transacoes = getTransactionsFromStorage()
        .filter(t => t.data >= start && t.data <= end)
        .sort((a, b) => (a.data < b.data ? 1 : a.data > b.data ? -1 : (b.id || 0) - (a.id || 0)));
    return transacoes.slice(0, limit);
}

// ---------------- TRANSAÇÕES (mock) ----------------
function saveTransaction(tipo, payload) {
    const txs = getTransactionsFromStorage();
    const novo = {
        id: uid(),
        tipo,
        data: payload.data || new Date().toISOString().slice(0, 10),
        valor: Number(payload.valor) || 0,
        descricao: payload.descricao || '',
        pago: payload.pago !== undefined ? payload.pago : true,
        categoria_id: payload.categoria_id || null,
        conta_id: payload.conta_id || null,
        cartao_id: payload.cartao_id || null,
        parcelas: payload.parcelas || null,
        conta_origem_id: payload.conta_origem_id || null,
        conta_destino_id: payload.conta_destino_id || null,
        observacao: payload.observacao || null
    };

    if (tipo === 'despesa_cartao' && Number(payload.parcelas) > 1) {
        const qtd = Number(payload.parcelas);
        for (let i = 0; i < qtd; i++) {
            const d = new Date(novo.data); d.setMonth(d.getMonth() + i);
            txs.push({ ...novo, id: uid(), data: d.toISOString().slice(0, 10), descricao: `${novo.descricao} (${i + 1}/${qtd})` });
        }
    } else {
        txs.push(novo);
    }

    setTransactionsToStorage(txs);
    return { ok: true, data: novo };
}

// ---------------- CHART ----------------
function buildMonthlySeries(referenceMonth, lastN = 6) {
    const series = [], labels = [];
    for (let i = lastN - 1; i >= 0; i--) {
        const m = clampMonth(referenceMonth, -i);
        const k = computeKPIs(m);
        labels.push(monthLabel(m));
        series.push(Number((k.resultado || 0).toFixed(2)));
    }
    return { labels, series };
}
function buildChart() {
    const canvas = document.getElementById('evolutionChart'); // <== seu ID
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const { labels, series } = buildMonthlySeries(currentMonth, 6);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(230, 126, 34, 0.35)');
    gradient.addColorStop(1, 'rgba(230, 126, 34, 0.05)');

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels, datasets: [{
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
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2C3E50',
                    titleColor: '#fff', bodyColor: '#fff', displayColors: false,
                    callbacks: { label: ctx => formatMoneyBR(ctx.parsed.y) }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3' } },
                y: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3', callback: v => formatMoneyBR(v) } }
            }
        }
    });
}
function updateChart() {
    const canvas = document.getElementById('evolutionChart');
    if (!canvas || !chartInstance) return buildChart();
    const { labels, series } = buildMonthlySeries(currentMonth, 6);
    chartInstance.data.labels = labels;
    chartInstance.data.datasets[0].data = series;
    chartInstance.update();
}

// ---------------- RENDER ----------------
function renderKPIs() {
    const k = computeKPIs(currentMonth);
    // KPIs (IDs do seu HTML)
    const saldo = document.getElementById('saldoValue');
    const rec = document.getElementById('receitasValue');
    const des = document.getElementById('despesasValue');
    if (saldo) saldo.textContent = formatMoneyBR(k.saldo);
    if (rec) rec.textContent = formatMoneyBR(k.receitas);
    if (des) des.textContent = formatMoneyBR(k.despesas);

    // Resumo
    const sRec = document.getElementById('totalReceitas');
    const sDes = document.getElementById('totalDespesas');
    const sRes = document.getElementById('resultadoMes');
    const sAc = document.getElementById('saldoAcumulado');
    if (sRec) sRec.textContent = formatMoneyBR(k.receitas);
    if (sDes) sDes.textContent = formatMoneyBR(k.despesas);
    if (sRes) sRes.textContent = formatMoneyBR(k.resultado);
    if (sAc) sAc.textContent = formatMoneyBR(k.saldoAcumulado);
}
function renderMonthLabel() {
    const label = document.getElementById('currentMonthText'); // no header
    if (label) label.textContent = monthLabel(currentMonth);
}
function renderTable() {
    const tbody = document.getElementById('transactionsTableBody');
    const empty = document.getElementById('emptyState');
    if (!tbody) return;

    const txs = getLastTransactions(currentMonth, 50);
    tbody.innerHTML = '';

    if (!txs.length) {
        if (empty) empty.style.display = 'block';
        return;
    } else if (empty) empty.style.display = 'none';

    txs.forEach(t => {
        const tr = document.createElement('tr');

        const tdData = document.createElement('td');
        tdData.textContent = formatDateBR(t.data);

        const tdTipo = document.createElement('td');
        tdTipo.textContent = t.tipo.replace('_', ' ');

        const tdCat = document.createElement('td');
        tdCat.textContent = (t.tipo === 'receita' || t.tipo.startsWith('despesa'))
            ? getCategoriaNome(t.tipo === 'receita' ? 'receita' : 'despesa', t.categoria_id) : '—';

        const tdConta = document.createElement('td');
        tdConta.textContent = t.conta_id ? getContaNome(t.conta_id) : (t.cartao_id ? 'Cartão' : '—');

        const tdDesc = document.createElement('td');
        tdDesc.textContent = t.descricao || t.observacao || '—';

        const tdValor = document.createElement('td');
        tdValor.style.fontWeight = '700';
        tdValor.style.textAlign = 'right';
        tdValor.style.color = (t.tipo === 'receita') ? 'var(--verde)' :
            (t.tipo.startsWith('despesa') ? 'var(--vermelho)' : 'var(--laranja)');
        tdValor.textContent = formatMoneyBR(t.valor);

        tr.appendChild(tdData);
        tr.appendChild(tdTipo);
        tr.appendChild(tdCat);
        tr.appendChild(tdConta);
        tr.appendChild(tdDesc);
        tr.appendChild(tdValor);
        tbody.appendChild(tr);
    });
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

    cells.forEach(c => {
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
    if (confirm) confirm.addEventListener('click', () => {
        currentMonth = monthPickerState.month;
        renderMonthLabel(); renderKPIs(); renderTable(); updateChart();
        closeMonthPicker();
    });
    document.querySelectorAll('[data-close-month]').forEach(el => {
        el.addEventListener('click', closeMonthPicker);
    });
}

// ---------------- CONTROLES / NAV ----------------
function populateMonthDropdown() { /* opcional: não usado com modal */ }

function bindControls() {
    // Navegação de mês do header
    const prev = document.getElementById('prevMonth');
    const next = document.getElementById('nextMonth');
    const openBtn = document.getElementById('monthDropdownBtn'); // abre modal

    if (prev) prev.addEventListener('click', () => {
        currentMonth = clampMonth(currentMonth, -1);
        renderMonthLabel(); renderKPIs(); renderTable(); updateChart();
    });
    if (next) next.addEventListener('click', () => {
        currentMonth = clampMonth(currentMonth, +1);
        renderMonthLabel(); renderKPIs(); renderTable(); updateChart();
    });
    if (openBtn) openBtn.addEventListener('click', (e) => { e.preventDefault(); openMonthPicker(); });

    // Export
    const btnExport = document.getElementById('exportBtn');
    if (btnExport) btnExport.addEventListener('click', () => exportToCSV(currentMonth));

    // FAB
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
    document.querySelectorAll('.fab-menu-item[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const which = btn.getAttribute('data-modal');
            const id = (which === 'despesa-cartao') ? 'modalDespesaCartao'
                : (which === 'despesa') ? 'modalDespesa'
                    : (which === 'receita') ? 'modalReceita'
                        : 'modalTransferencia';
            openModal(id);
        });
    });

    // Fechar modais (X, backdrop, botão data-dismiss)
    document.querySelectorAll('.modal .modal-close, .modal .modal-backdrop, .modal [data-dismiss="modal"]').forEach(el => {
        el.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal') || e.target.parentElement.closest('.modal');
            if (modal) closeModal(modal.id);
        });
    });
}

// ---------------- EXPORT CSV ----------------
function exportToCSV(month) {
    const rows = getLastTransactions(month, 1000);
    const header = ['Data', 'Tipo', 'Categoria', 'Conta/Cartão', 'Descrição', 'Valor'];
    const csv = [header.join(';')]
        .concat(rows.map(t => [
            formatDateBR(t.data),
            t.tipo,
            (t.tipo === 'receita' || t.tipo.startsWith('despesa')) ? getCategoriaNome(t.tipo === 'receita' ? 'receita' : 'despesa', t.categoria_id) : '',
            t.conta_id ? getContaNome(t.conta_id) : (t.cartao_id ? 'Cartão' : ''),
            (t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
            t.valor.toString().replace('.', ',')
        ].join(';'))).join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `lukrato-${month}.csv`;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ---------------- MODAIS: helpers + popula selects ----------------
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('active');
    el.setAttribute('aria-hidden', 'false');
    // preencher selects e máscaras quando abre
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
    scope.querySelectorAll('input[type="date"]').forEach(inp => {
        if (!inp.value) inp.value = today;
    });
}

function populateReceita() {
    const selCat = document.getElementById('receitaCategoria');
    const selConta = document.getElementById('receitaConta');
    selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
        getCategoriasFromStorage().receita.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    selConta.innerHTML = '<option value="">Selecione uma conta</option>' +
        getContasFromStorage().map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
}
function populateDespesa() {
    const selCat = document.getElementById('despesaCategoria');
    const selConta = document.getElementById('despesaConta');
    selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
        getCategoriasFromStorage().despesa.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    selConta.innerHTML = '<option value="">Selecione uma conta</option>' +
        getContasFromStorage().map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
}
function populateDespesaCartao() {
    const selCat = document.getElementById('despesaCartaoCategoria');
    const selCartao = document.getElementById('despesaCartaoCartao');
    selCat.innerHTML = '<option value="">Selecione uma categoria</option>' +
        getCategoriasFromStorage().despesa.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    selCartao.innerHTML = '<option value="">Selecione um cartão</option>' +
        getCartoesFromStorage().map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
}
function populateTransferencia() {
    const selOrigem = document.getElementById('transferenciaOrigem');
    const selDestino = document.getElementById('transferenciaDestino');
    const contas = getContasFromStorage();
    const opts = '<option value="">Selecione</option>' + contas.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
    selOrigem.innerHTML = opts; selDestino.innerHTML = opts;
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
            rightAlign: false
        });
    }
}

// ---------------- SUBMIT DOS FORMULÁRIOS ----------------
function bindForms() {
    // RECEITA
    const f1 = document.getElementById('formReceita');
    if (f1) f1.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            tipo: 'receita',
            data: document.getElementById('receitaData').value,
            categoria_id: Number(document.getElementById('receitaCategoria').value),
            conta_id: Number(document.getElementById('receitaConta').value),
            descricao: document.getElementById('receitaDescricao').value.trim(),
            valor: parseMoneyBR(document.getElementById('receitaValor').value),
            pago: document.getElementById('receitaPago').checked
        };
        saveTransaction('receita', payload);
        closeModal('modalReceita');
        afterDataChange();
        Swal.fire({ icon: 'success', title: 'Receita salva!', timer: 1300, showConfirmButton: false });
    });

    // DESPESA
    const f2 = document.getElementById('formDespesa');
    if (f2) f2.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            tipo: 'despesa',
            data: document.getElementById('despesaData').value,
            categoria_id: Number(document.getElementById('despesaCategoria').value),
            conta_id: Number(document.getElementById('despesaConta').value),
            descricao: document.getElementById('despesaDescricao').value.trim(),
            valor: parseMoneyBR(document.getElementById('despesaValor').value),
            pago: document.getElementById('despesaPago').checked
        };
        saveTransaction('despesa', payload);
        closeModal('modalDespesa');
        afterDataChange();
        Swal.fire({ icon: 'success', title: 'Despesa salva!', timer: 1300, showConfirmButton: false });
    });

    // DESPESA CARTÃO
    const f3 = document.getElementById('formDespesaCartao');
    if (f3) f3.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            tipo: 'despesa_cartao',
            data: document.getElementById('despesaCartaoData').value,
            cartao_id: Number(document.getElementById('despesaCartaoCartao').value),
            categoria_id: Number(document.getElementById('despesaCartaoCategoria').value),
            descricao: document.getElementById('despesaCartaoDescricao').value.trim(),
            valor: parseMoneyBR(document.getElementById('despesaCartaoValor').value),
            parcelas: Number(document.getElementById('despesaCartaoParcelas').value) || 1
        };
        saveTransaction('despesa_cartao', payload);
        closeModal('modalDespesaCartao');
        afterDataChange();
        Swal.fire({ icon: 'success', title: 'Despesa no cartão salva!', timer: 1300, showConfirmButton: false });
    });

    // TRANSFERÊNCIA
    const f4 = document.getElementById('formTransferencia');
    if (f4) f4.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            tipo: 'transferencia',
            data: document.getElementById('transferenciaData').value,
            conta_origem_id: Number(document.getElementById('transferenciaOrigem').value),
            conta_destino_id: Number(document.getElementById('transferenciaDestino').value),
            valor: parseMoneyBR(document.getElementById('transferenciaValor').value),
            observacao: document.getElementById('transferenciaObservacao').value.trim()
        };
        saveTransaction('transferencia', payload);
        closeModal('modalTransferencia');
        afterDataChange();
        Swal.fire({ icon: 'success', title: 'Transferência registrada!', timer: 1300, showConfirmButton: false });
    });
}

function afterDataChange() {
    renderKPIs(); renderTable(); updateChart();
}

// ---------------- INIT ----------------
function initDashboard() {
    ensureSeed();
    renderMonthLabel();
    renderKPIs();
    renderTable();
    buildChart();

    bindControls();
    bindForms();
    bindMonthPickerControls();
    refreshMoneyMasks();
}

document.addEventListener('DOMContentLoaded', initDashboard);
