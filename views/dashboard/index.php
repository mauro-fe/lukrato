<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$username  = $username  ?? 'usuário';
$menu      = $menu      ?? 'dashboard';
$base      = rtrim(BASE_URL ?? '/', '/') . '/';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<?php if (function_exists('loadPageCss')) loadPageCss(); ?>

<?php
$active = function (string $key) use ($menu) {
    return $menu === $key ? 'active' : '';
};
$aria   = function (string $key) use ($menu) {
    return $menu === $key ? ' aria-current="page"' : '';
};
?>

<section class="container">
    <div>
        <h3>Dashboard</h3>
    </div>
    <header class="dash-lk-header">

        <div class="header-left">
            <div class="month-selector">
                <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="month-display">
                    <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" aria-haspopup="true"
                        aria-expanded="false">
                        <span id="currentMonthText">Carregando...</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                </div>

                <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </header>

    <section class="pt-5">
        <div class="">
            <section class="kpi-grid" role="region" aria-label="Indicadores principais">
                <div class="card kpi-card" id="saldoCard">
                    <div class="card-header">
                        <div class="kpi-icon saldo"><i class="fas fa-wallet"></i></div><span class="kpi-title">Saldo
                            Atual</span>
                    </div>
                    <div class="kpi-value" id="saldoValue">R$ 0,00</div>
                </div>
                <div class="card kpi-card" id="receitasCard">
                    <div class="card-header">
                        <div class="kpi-icon receitas"><i class="fas fa-arrow-up"></i></div><span
                            class="kpi-title">Receitas
                            do Mês</span>
                    </div>
                    <div class="kpi-value receitas" id="receitasValue">R$ 0,00</div>
                </div>
                <div class="card kpi-card" id="despesasCard">
                    <div class="card-header">
                        <div class="kpi-icon despesas"><i class="fas fa-arrow-down"></i></div><span
                            class="kpi-title">Despesas do Mês</span>
                    </div>
                    <div class="kpi-value despesas" id="despesasValue">R$ 0,00</div>
                </div>
            </section>

            <section class="charts-grid">
                <div class="card chart-card">
                    <div class="card-header">
                        <h2 class="card-title">Evolução Financeira</h2>
                    </div>
                    <div class="chart-container"><canvas id="evolutionChart" role="img"
                            aria-label="Gráfico de evolução do saldo"></canvas></div>
                </div>

                <div class="card summary-card">
                    <div class="card-header">
                        <h2 class="card-title">Resumo Mensal</h2>
                    </div>
                    <div class="summary-grid">
                        <div class="summary-item"><span class="summary-label">Total Receitas</span><span
                                class="summary-value receitas" id="totalReceitas">R$ 0,00</span></div>
                        <div class="summary-item"><span class="summary-label">Total Despesas</span><span
                                class="summary-value despesas" id="totalDespesas">R$ 0,00</span></div>
                        <div class="summary-item"><span class="summary-label">Resultado</span><span
                                class="summary-value" id="resultadoMes">R$ 0,00</span></div>
                        <div class="summary-item"><span class="summary-label">Saldo Acumulado</span><span
                                class="summary-value" id="saldoAcumulado">R$ 0,00</span></div>
                    </div>
                </div>
            </section>

            <section class="card table-card">
                <div class="card-header">
                    <h2 class="card-title">Últimos Lançamentos</h2>
                </div>
                <div class="table-container">
                    <div class="empty-state" id="emptyState" style="display:none;">
                        <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                        <h3>Nenhum lançamento encontrado</h3>
                        <p>Adicione sua primeira transação clicando no botão + no canto inferior direito</p>
                    </div>
                    <table class="table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Conta</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>

    <!-- Month Picker Modal (Bootstrap 5) -->
    <div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
            <div class="modal-content bg-dark text-light border-0 rounded-3">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body pt-0">
                    <!-- Toolbar: Ano + Ações rápidas -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div class="btn-group" role="group" aria-label="Navegar entre anos">
                            <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear"
                                title="Ano anterior">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                            <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear"
                                title="Próximo ano">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>
                            <input type="month" class="form-control form-control-sm bg-dark text-light border-secondary"
                                id="mpInputMonth" style="width:165px">
                        </div>
                    </div>

                    <!-- Grade de meses -->
                    <div id="mpGrid" class="row g-2"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<?php if (function_exists('loadPageJs')) loadPageJs(); ?>
<script>
(() => {
    'use strict';

    /* ============ BASE + helpers ============ */
    const BASE = (() => {
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
    })();

    const money = n => {
        try {
            return Number(n || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        } catch (e) {
            return 'R$ 0,00';
        }
    };

    const dateBR = iso => {
        if (!iso) return '—';
        try {
            const m = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
        } catch (e) {
            return '—';
        }
    };

    const $ = (s, sc = document) => sc.querySelector(s);

    async function getJSON(url) {
        try {
            const r = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!r.ok) {
                throw new Error(`HTTP ${r.status}`);
            }

            const j = await r.json();
            if (j?.error || j?.status === 'error') {
                throw new Error(j?.message || j?.error || 'Erro na resposta da API');
            }
            return j;
        } catch (e) {
            console.error('Erro na requisição:', e);
            throw e;
        }
    }

    const apiMetrics = m => getJSON(`${BASE}api/dashboard/metrics?month=${encodeURIComponent(m)}`);
    const apiTransactions = (m, l = 50) => getJSON(
        `${BASE}api/dashboard/transactions?month=${encodeURIComponent(m)}&limit=${l}`);

    /* ============ Controle de mês ============ */
    const STORAGE_KEY = 'lukrato.month.dashboard';

    // elementos
    const $label = $('#currentMonthText');
    const $prev = $('#prevMonth');
    const $next = $('#nextMonth');
    const btnOpen = $('#monthDropdownBtn');
    const modalEl = $('#monthModal');

    if (!btnOpen || !modalEl) {
        console.warn('Elementos do modal não encontrados');
        return;
    }

    // elementos do modal
    const mpYearLabel = $('#mpYearLabel');
    const mpPrevYear = $('#mpPrevYear');
    const mpNextYear = $('#mpNextYear');
    const mpGrid = $('#mpGrid');
    const mpTodayBtn = $('#mpTodayBtn');
    const mpInput = $('#mpInputMonth');

    const MONTH_NAMES_SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // fonte de verdade inicial
    let currentMonth = (() => {
        try {
            return (window.LukratoHeader?.getMonth?.()) ||
                sessionStorage.getItem(STORAGE_KEY) ||
                new Date().toISOString().slice(0, 7);
        } catch (e) {
            return new Date().toISOString().slice(0, 7);
        }
    })();

    let modalYear = (() => {
        try {
            return Number(currentMonth.split('-')[0]) || new Date().getFullYear();
        } catch (e) {
            return new Date().getFullYear();
        }
    })();

    function yymm(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
    }

    function makeMonthValue(year, mIndex) { // mIndex 0..11
        return `${year}-${String(mIndex+1).padStart(2,'0')}`;
    }

    const monthLabel = (m) => {
        try {
            const [y, mm] = String(m || '').split('-').map(Number);
            if (!y || !mm || mm < 1 || mm > 12) return '—';
            return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        } catch (e) {
            return '—';
        }
    };

    const addMonths = (m, delta) => {
        try {
            const [y, mm] = m.split('-').map(Number);
            const d = new Date(y, mm - 1 + delta, 1);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        } catch (e) {
            return m; // retorna o mês original em caso de erro
        }
    };

    function writeLabel() {
        if ($label) {
            const label = monthLabel(currentMonth);
            $label.textContent = label;
        }
    }

    function setLocalMonth(m, {
        emit = true
    } = {}) {
        if (!m || !/^\d{4}-\d{2}$/.test(m)) {
            console.warn('Formato de mês inválido:', m);
            return;
        }

        currentMonth = m;
        try {
            sessionStorage.setItem(STORAGE_KEY, currentMonth);
        } catch (e) {
            console.warn('Erro ao salvar no sessionStorage:', e);
        }

        writeLabel();

        if (emit) {
            try {
                document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                    detail: {
                        month: currentMonth
                    }
                }));
            } catch (e) {
                console.warn('Erro ao disparar evento:', e);
            }
        }
    }

    function buildGrid() {
        if (!mpYearLabel || !mpGrid) return;

        mpYearLabel.textContent = modalYear;

        // monta 12 cards de mês (3 col x 4 rows)
        let html = '';
        for (let i = 0; i < 12; i++) {
            const val = makeMonthValue(modalYear, i);
            const isCurrent = val === currentMonth;
            html += `
                    <div class="col-4">
                        <button type="button"
                            class="mp-month btn w-100 py-3 ${isCurrent ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}"
                            data-val="${val}">
                            ${MONTH_NAMES_SHORT[i]}
                        </button>
                    </div>`;
        }
        mpGrid.innerHTML = html;

        // listeners
        mpGrid.querySelectorAll('.mp-month').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const v = btn.getAttribute('data-val');
                if (!v) return;

                // fecha modal
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                        inst?.hide();
                    }
                } catch (e) {
                    console.warn('Bootstrap Modal não disponível:', e);
                }

                setLocalMonth(v);
                await renderAll();
            });
        });
    }

    function setToday() {
        try {
            const now = new Date();
            const todayFirst = new Date(now.getFullYear(), now.getMonth(), 1);
            const todayVal = yymm(todayFirst);

            // fecha modal
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                    inst?.hide();
                }
            } catch (e) {
                console.warn('Bootstrap Modal não disponível:', e);
            }

            setLocalMonth(todayVal);
            renderAll();
        } catch (e) {
            console.error('Erro ao definir mês atual:', e);
        }
    }

    // input type=month -> integra direto
    function syncInput() {
        if (mpInput) {
            mpInput.value = currentMonth;
        }
    }

    // Event listeners
    mpPrevYear?.addEventListener('click', (e) => {
        e.preventDefault();
        modalYear--;
        buildGrid();
    });

    mpNextYear?.addEventListener('click', (e) => {
        e.preventDefault();
        modalYear++;
        buildGrid();
    });

    mpTodayBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        setToday();
    });

    mpInput?.addEventListener('change', async (e) => {
        const v = String(e.target.value || '');
        if (!/^\d{4}-\d{2}$/.test(v)) return;

        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                inst?.hide();
            }
        } catch (e) {
            console.warn('Bootstrap Modal não disponível:', e);
        }

        setLocalMonth(v);
        await renderAll();
    });

    // abrir modal no clique do botão
    btnOpen?.addEventListener('click', (ev) => {
        ev.preventDefault();

        try {
            modalYear = Number((currentMonth || '').split('-')[0]) || new Date().getFullYear();
            buildGrid();
            syncInput();

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                inst?.show();
            } else {
                console.warn('Bootstrap 5 não detectado. Inclua JS do Bootstrap para o modal funcionar.');
            }
        } catch (e) {
            console.error('Erro ao abrir modal:', e);
        }
    });

    // navegação por botões se não houver LukratoHeader
    $prev?.addEventListener('click', async (e) => {
        e.preventDefault();
        setLocalMonth(addMonths(currentMonth, -1));
        await renderAll();
    });

    $next?.addEventListener('click', async (e) => {
        e.preventDefault();
        setLocalMonth(addMonths(currentMonth, +1));
        await renderAll();
    });

    // se houver LukratoHeader, só ouvimos
    document.addEventListener('lukrato:month-changed', async (e) => {
        try {
            const newMonth = e.detail?.month;
            if (!newMonth || newMonth === currentMonth) return;
            currentMonth = newMonth;
            writeLabel();
            await renderAll();
        } catch (err) {
            console.error('Erro ao processar mudança de mês:', err);
        }
    });

    // se mês mudar por outro lugar, mantém coerência interna
    document.addEventListener('lukrato:month-changed', () => {
        syncInput();
    });

    /* ============ Renderizadores ============ */
    async function renderKPIs() {
        try {
            const k = await apiMetrics(currentMonth);
            const map = {
                saldoValue: 'saldo',
                receitasValue: 'receitas',
                despesasValue: 'despesas',
                totalReceitas: 'receitas',
                totalDespesas: 'despesas',
                resultadoMes: 'resultado',
                saldoAcumulado: 'saldoAcumulado',
            };

            Object.entries(map).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = money(k[key] || 0);
                }
            });
        } catch (e) {
            console.error('Erro ao renderizar KPIs:', e);

            // Fallback: zerar valores em caso de erro
            const ids = ['saldoValue', 'receitasValue', 'despesasValue', 'totalReceitas', 'totalDespesas',
                'resultadoMes', 'saldoAcumulado'
            ];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = 'R$ 0,00';
            });
        }
    }

    async function renderTable() {
        const tbody = $('#transactionsTableBody');
        const empty = $('#emptyState');
        const table = $('#transactionsTable');

        if (!tbody || !empty) return;

        try {
            const list = await apiTransactions(currentMonth, 50);
            tbody.innerHTML = '';

            const hasData = Array.isArray(list) && list.length > 0;
            empty.style.display = hasData ? 'none' : 'block';
            if (table) table.style.display = hasData ? 'table' : 'none';

            if (hasData) {
                list.forEach(t => {
                    const tr = document.createElement('tr');
                    const tipo = String(t.tipo || '').toLowerCase();
                    const color = tipo === 'receita' ? 'var(--verde, #27ae60)' :
                        (tipo.startsWith('despesa') ? 'var(--vermelho, #e74c3c)' :
                            'var(--laranja, #f39c12)');

                    tr.innerHTML =
                        `
                            <td>${dateBR(t.data)}</td>
                            <td>${String(t.tipo||'').replace(/_/g,' ')}</td>
                            <td>${t.categoria?.nome || '—'}</td>
                            <td>${t.conta?.nome || '—'}</td>
                            <td>${t.descricao || t.observacao || '—'}</td>
                            <td style="font-weight:700;text-align:right;color:${color}">${money(Number(t.valor)||0)}</td>`;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) {
            console.error('Erro ao renderizar tabela:', e);
            empty.style.display = 'block';
            if (table) table.style.display = 'none';
        }
    }

    let chartInstance = null;
    async function drawChart() {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas) return;

        if (typeof Chart === 'undefined') {
            console.warn('Chart.js não carregado');
            return;
        }

        try {
            // últimos 6 meses relativos ao currentMonth
            const months = Array.from({
                length: 6
            }, (_, i) => {
                const [y, m] = currentMonth.split('-').map(Number);
                const d = new Date(y, m - 1 - (5 - i), 1);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            });

            const labels = months.map(m => {
                try {
                    const [y, mm] = m.split('-').map(Number);
                    return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                        month: 'short'
                    });
                } catch (e) {
                    return 'N/A';
                }
            });

            const results = await Promise.allSettled(
                months.map(m => apiMetrics(m))
            );

            const series = results.map(result => {
                if (result.status === 'fulfilled') {
                    return Number(result.value?.resultado || 0);
                }
                return 0;
            });

            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(230,126,34,0.35)');
            grad.addColorStop(1, 'rgba(230,126,34,0.05)');

            const data = {
                labels,
                datasets: [{
                    label: 'Resultado do Mês',
                    data: series,
                    borderColor: '#E67E22',
                    backgroundColor: grad,
                    borderWidth: 3,
                    pointBackgroundColor: '#E67E22',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: .35,
                    fill: true
                }]
            };

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#2C3E50',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        displayColors: false,
                        callbacks: {
                            label: (c) => money(c.parsed.y)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(189,195,199,.16)'
                        },
                        ticks: {
                            color: '#cfd8e3'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(189,195,199,.16)'
                        },
                        ticks: {
                            color: '#cfd8e3',
                            callback: v => money(v)
                        }
                    }
                }
            };

            if (chartInstance) {
                chartInstance.data = data;
                chartInstance.options = options;
                chartInstance.update();
            } else {
                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: options
                });
            }
        } catch (e) {
            console.error('Erro ao renderizar gráfico:', e);
        }
    }

    async function renderAll() {
        writeLabel();
        await Promise.allSettled([
            renderKPIs(),
            renderTable(),
            drawChart()
        ]);
    }

    // Expor para uso externo
    window.refreshDashboard = renderAll;

    /* ============ Boot ============ */
    // Aguardar carregamento completo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', async () => {
            writeLabel();
            await renderAll();
        });
    } else {
        // DOM já carregado
        setTimeout(async () => {
            writeLabel();
            await renderAll();
        }, 100);
    }
})();
</script>