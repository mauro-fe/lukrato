<div class="lk-wrap">
    <div class="lk-h">
        <div class="lk-t">
            <h3>Relatórios</h3>
        </div>

        <header class="dash-lk-header">
            <div class="header-left">
                <div class="month-selector">
                    <div class="lk-period">
                        <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" data-bs-toggle="modal"
                            data-bs-target="#monthModal" aria-haspopup="true" aria-expanded="false">
                            <span id="currentMonthText">Carregando...</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <div class="month-display">
                            <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                        </div>

                        <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Controles: Abas + Tipo (pizza) + Conta -->
        <div class="lk-controls pt-4" role="tablist" aria-label="Tipos de relatório">
            <div class="lk-seg" id="tabs">
                <button class="active" data-view="pizza" aria-pressed="true"><i class="fa-solid fa-chart-pie"></i> Por
                    categoria</button>
                <button data-view="linha" aria-pressed="false"><i class="fa-solid fa-chart-line"></i> Saldo
                    diário</button>
                <button data-view="barras" aria-pressed="false"><i class="fa-solid fa-chart-column"></i> Receitas x
                    Despesas</button>
                <button data-view="contas" aria-pressed="false"><i class="fa-solid fa-wallet"></i> Por conta</button>
                <button data-view="evolucao" aria-pressed="false"><i class="fa-solid fa-timeline"></i> Evolução
                    12m</button>
            </div>

            <!-- tipo (apenas pizza) -->
            <div class="lk-sel" id="typeSelect">
                <button id="typeBtn" aria-haspopup="menu" aria-expanded="false">
                    <span class="lb">Despesas por categorias</span> <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="lk-menu" role="menu" aria-label="Escolha do tipo">
                    <button data-type="despesas_por_categoria">Despesas por categorias</button>
                    <button data-type="receitas_por_categoria">Receitas por categorias</button>
                </div>
            </div>

            <!-- contas -->
            <div class="lk-sel" id="accountSelect" style="display:none">
                <button id="accountBtn" aria-haspopup="menu" aria-expanded="false">
                    <span class="lb">Todas as contas</span> <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="lk-menu" role="menu" aria-label="Escolha de conta">
                    <!-- preenchido via JS -->
                </div>
            </div>
        </div>
    </div>

    <div class="lk-card mb-5">
        <div id="area"></div>
    </div>
</div>
<!-- Modal: Selecionar mês -->
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
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear" title="Ano anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Próximo ano">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function() {
        // Carrega Chart.js se necessário
        function ensureChart() {
            return new Promise((resolve, reject) => {
                if (window.Chart) return resolve();
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
                s.onload = () => resolve();
                s.onerror = () => reject(new Error('Falha ao carregar Chart.js'));
                document.head.appendChild(s);
            });
        }

        /* =================== THEME / CORES DO LUKRATO PARA CHART.JS =================== */
        function getTheme() {
            const css = getComputedStyle(document.documentElement);
            const v = (k, fallback) => (css.getPropertyValue(k) || fallback).trim();

            const brand = {
                primary: v('--color-primary', '#E67E22'),
                text: v('--color-text', '#EAF2FF'),
                textMute: v('--color-text-muted', '#94A3B8'),
                surface: v('--color-surface', '#0F2233'),
                green: '#2ECC71',
                orange: '#E67E22',
                yellow: '#F39C12',
                blue: '#2C3E50',
                gray: '#BDC3C7',
                red: '#E74C3C',
                purple: '#9B59B6',
                cyan: '#1ABC9C'
            };
            const palette = ['#E67E22', '#2C3E50', '#2ECC71', '#BDC3C7', '#F39C12', '#9B59B6', '#1ABC9C', '#E74C3C'];
            return {
                brand,
                palette
            };
        }

        function hexToRgba(hex, a = .25) {
            const m = hex.replace('#', '');
            const n = parseInt(m, 16);
            const r = (m.length === 3) ? ((n >> 8) & 0xF) * 17 : (n >> 16) & 255;
            const g = (m.length === 3) ? ((n >> 4) & 0xF) * 17 : (n >> 8) & 255;
            const b = (m.length === 3) ? (n & 0xF) * 17 : n & 255;
            return `rgba(${r},${g},${b},${a})`;
        }
        const THEME = getTheme();

        function applyChartDefaults() {
            if (!window.Chart) return;
            Chart.defaults.color = THEME.brand.text;
            Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
            Chart.defaults.plugins.title.color = THEME.brand.text;
            Chart.defaults.plugins.legend.labels.color = THEME.brand.textMute;
        }
        /* ============================================================================== */

        const monthNames = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro',
            'outubro', 'novembro', 'dezembro'
        ];
        const cap = s => s.charAt(0).toUpperCase() + s.slice(1);
        const fmt = v => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(v || 0));

        const st = {
            view: 'pizza',
            type: 'despesas_por_categoria',
            includeTransfers: false,
            d: new Date(),
            chart: null,
            accounts: [],
            accountId: null,
            modalYear: new Date().getFullYear()
        };

        const $ = sel => document.querySelector(sel);
        const $$ = sel => Array.from(document.querySelectorAll(sel));
        const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

        const base = '<?= BASE_URL ?>';

        const currentMonthTextEl = $('#currentMonthText');
        const prevBtn = $('#prevMonth');
        const nextBtn = $('#nextMonth');
        const monthModalEl = document.getElementById('monthModal');
        const ensureMonthModal = () => {
            if (!monthModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
            try {
                if (monthModalEl.parentElement && monthModalEl.parentElement !== document.body) {
                    document.body.appendChild(monthModalEl);
                }
                return bootstrap.Modal.getOrCreateInstance(monthModalEl);
            } catch {
                return null;
            }
        };
        ensureMonthModal();

        function label(d) {
            return cap(monthNames[d.getMonth()]) + ' ' + d.getFullYear();
        }

        function sync() {
            if (currentMonthTextEl) currentMonthTextEl.textContent = label(st.d);
        }
        sync();

        // Navegação pelas setas
        on(prevBtn, 'click', () => {
            st.d.setMonth(st.d.getMonth() - 1);
            sync();
            load();
        });
        on(nextBtn, 'click', () => {
            st.d.setMonth(st.d.getMonth() + 1);
            sync();
            load();
        });

        // ===== Modal de mês =====
        const yearPrev = $('#mpPrevYear');
        const yearNext = $('#mpNextYear');
        const yearLabel = $('#mpYearLabel');
        const monthsGrid = $('#mpGrid');
        const inputMonth = $('#mpInputMonth');
        const todayBtn = $('#mpTodayBtn');

        function renderMonthsGrid() {
            if (!monthsGrid || !yearLabel) return;
            monthsGrid.innerHTML = '';
            yearLabel.textContent = String(st.modalYear);

            for (let m = 0; m < 12; m++) {
                const col = document.createElement('div');
                col.className = 'col-4';

                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn btn-outline-light w-100';
                b.textContent = cap(monthNames[m]);

                // destaque mês atual escolhido
                if (st.d.getFullYear() === st.modalYear && st.d.getMonth() === m) {
                    b.classList.add('active');
                    b.style.borderColor = 'var(--color-primary, #E67E22)';
                    b.style.background = 'rgba(230,126,34,.15)';
                }

                b.addEventListener('click', () => {
                    st.d = new Date(st.modalYear, m, 1);
                    sync();
                    load();
                    try {
                        ensureMonthModal()?.hide();
                    } catch {}
                });

                col.appendChild(b);
                monthsGrid.appendChild(col);
            }
        }

        on(yearPrev, 'click', () => {
            st.modalYear--;
            renderMonthsGrid();
        });
        on(yearNext, 'click', () => {
            st.modalYear++;
            renderMonthsGrid();
        });

        // “Hoje”
        on(todayBtn, 'click', () => {
            const now = new Date();
            st.modalYear = now.getFullYear();
            st.d = new Date(st.modalYear, now.getMonth(), 1);
            sync();
            load();
            try {
                ensureMonthModal()?.hide();
            } catch {}
        });

        // <input type="month">
        on(inputMonth, 'change', () => {
            const ym = inputMonth.value; // formato yyyy-mm
            if (!/^\d{4}-\d{2}$/.test(ym)) return;
            const [y, m] = ym.split('-').map(Number);
            st.modalYear = y;
            st.d = new Date(y, m - 1, 1);
            sync();
            load();
            try {
                ensureMonthModal()?.hide();
            } catch {}
        });

        // Ao abrir o modal, alinhar o ano mostrado e redesenhar a grade
        ensureMonthModal();
        monthModalEl?.addEventListener('shown.bs.modal', () => {
            st.modalYear = st.d.getFullYear();
            // preencher <input type="month"> com o mês atual selecionado
            if (inputMonth) {
                const y = st.d.getFullYear();
                const m = String(st.d.getMonth() + 1).padStart(2, '0');
                inputMonth.value = `${y}-${m}`;
            }
            renderMonthsGrid();
        });

        // ===== Seletor de tipo (pizza) =====
        const selType = $('#typeSelect');
        const typeBtn = $('#typeBtn');
        const typeMenu = selType?.querySelector('.lk-menu');

        on(typeBtn, 'click', () => {
            if (!typeMenu) return;
            typeMenu.classList.toggle('open');
            typeBtn.setAttribute('aria-expanded', String(typeMenu.classList.contains('open')));
        });

        document.addEventListener('click', (e) => {
            if (selType && typeMenu && !selType.contains(e.target)) {
                typeMenu.classList.remove('open');
                if (typeBtn) typeBtn.setAttribute('aria-expanded', 'false');
            }
        });

        (typeMenu ? typeMenu.querySelectorAll('button') : []).forEach((b) =>
            on(b, 'click', () => {
                st.type = b.dataset.type;
                const lb = typeBtn ? typeBtn.querySelector('.lb') : null;
                if (lb) lb.textContent = b.textContent; // <-- sem optional chaining do lado esquerdo
                typeMenu.classList.remove('open');
                if (typeBtn) typeBtn.setAttribute('aria-expanded', 'false');
                if (st.view === 'pizza') load();
            })
        );

        // ===== Abas =====
        const accountSelectWrap = $('#accountSelect');

        $$('#tabs button').forEach((b) =>
            on(b, 'click', () => {
                $$('#tabs button').forEach((x) => {
                    x.classList.remove('active');
                    x.setAttribute('aria-pressed', 'false');
                });
                b.classList.add('active');
                b.setAttribute('aria-pressed', 'true');
                st.view = b.dataset.view;

                if (selType) selType.style.display = (st.view === 'pizza') ? '' : 'none';
                if (accountSelectWrap) accountSelectWrap.style.display = '';
                load();
            })
        );
        if (selType) selType.style.display = (st.view === 'pizza') ? '' : 'none';

        // ===== Seletor de Conta =====
        const accountBtn = $('#accountBtn');
        const accountMenu = accountSelectWrap?.querySelector('.lk-menu');

        on(accountBtn, 'click', () => {
            if (!accountMenu) return;
            accountMenu.classList.toggle('open');
            accountBtn.setAttribute('aria-expanded', String(accountMenu.classList.contains('open')));
        });

        document.addEventListener('click', (e) => {
            if (accountSelectWrap && accountMenu && !accountSelectWrap.contains(e.target)) {
                accountMenu.classList.remove('open');
                if (accountBtn) accountBtn.setAttribute('aria-expanded', 'false');
            }
        });

        async function loadAccounts() {
            if (!accountSelectWrap || !accountMenu || !accountBtn) return;
            try {
                const r = await fetch(`${base}api/accounts`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!r.ok) throw new Error('Falha ao carregar contas');
                const json = await r.json();

                st.accounts = (json.items || json || []).map(a => ({
                    id: Number(a.id),
                    nome: a.nome || a.apelido || a.instituicao || `Conta #${a.id}`
                }));

                accountMenu.innerHTML = '';
                const btnAll = document.createElement('button');
                btnAll.textContent = 'Todas as contas';
                on(btnAll, 'click', () => {
                    st.accountId = null;
                    const lb = accountBtn ? accountBtn.querySelector('.lb') : null;
                    if (lb) lb.textContent = 'Todas as contas';
                    accountMenu.classList.remove('open');
                    accountBtn.setAttribute('aria-expanded', 'false');
                    load();
                });
                accountMenu.appendChild(btnAll);

                st.accounts.forEach(acc => {
                    const b = document.createElement('button');
                    b.textContent = acc.nome;
                    on(b, 'click', () => {
                        st.accountId = acc.id;
                        const lb = accountBtn ? accountBtn.querySelector('.lb') : null;
                        if (lb) lb.textContent = acc.nome;
                        accountMenu.classList.remove('open');
                        accountBtn.setAttribute('aria-expanded', 'false');
                        load();
                    });
                    accountMenu.appendChild(b);
                });

                accountSelectWrap.style.display = '';
            } catch (e) {
                console.warn('Contas: não foi possível carregar.', e);
                accountSelectWrap.style.display = 'none';
            }
        }

        // ---- APIs ----
        async function fetchData() {
            const y = st.d.getFullYear();
            const m = String(st.d.getMonth() + 1).padStart(2, '0');

            const type =
                st.view === 'linha' ? 'saldo_mensal' :
                st.view === 'barras' ? 'receitas_despesas_diario' :
                st.view === 'evolucao' ? 'evolucao_12m' :
                st.view === 'contas' ? 'receitas_despesas_por_conta' :
                st.type;

            const params = new URLSearchParams({
                type,
                year: String(y),
                month: String(m)
            });
            if (st.accountId) params.set('account_id', String(st.accountId));

            const url = `${base}api/reports?${params.toString()}`;
            try {
                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });
                const json = await r.json();

                // 🔽 NOVO: lida com os dois formatos (embrulhado ou não)
                const payload = (json && typeof json === 'object' && 'status' in json && 'data' in json) ?
                    json.data :
                    json;

                return payload;
            } catch (e) {
                console.error(e);
                return {
                    labels: [],
                    values: []
                };
            }
        }

        // UI helpers
        function setArea(html) {
            $('#area').innerHTML = html;
        }

        function loading() {
            setArea('<div class="lk-loading">Carregando…</div>');
        }

        function empty() {
            setArea(`
      <div class="lk-empty">
        <img src="https://cdn.jsdelivr.net/gh/alohe/illustrations/undraw_clipboard.svg" alt="Sem dados">
        <h3>Nenhum dado encontrado</h3>
        <p>Altere o período, o tipo ou a conta.</p>
      </div>
    `);
        }

        function destroyChart() {
            if (st.chart) {
                st.chart.destroy();
                st.chart = null;
            }
        }

        // =================== DESENHOS ===================
        function drawPie(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const ttl = (d.values || []).reduce((a, b) => a + Number(b), 0);
            const titulo = (st.type === 'receitas_por_categoria') ? 'Receitas por categorias' :
                'Despesas por categorias';
            const colors = (d.values || []).map((_, i) => THEME.palette[i % THEME.palette.length]);

            st.chart = new Chart($('#c'), {
                type: 'doughnut',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: titulo,
                        data: d.values || [],
                        backgroundColor: colors,
                        borderColor: THEME.brand.surface,
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: `${titulo} • Total ${fmt(ttl)}`
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => `${c.label}: ${fmt(c.parsed)}`
                            }
                        }
                    },
                    maintainAspectRatio: false,
                    cutout: '60%'
                }
            });
        }

        function drawLine(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const color = THEME.brand.primary;

            st.chart = new Chart($('#c'), {
                type: 'line',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: 'Saldo diário (receitas - despesas)',
                        data: (d.values || []).map(Number),
                        tension: .3,
                        borderWidth: 2,
                        borderColor: color,
                        pointRadius: 2,
                        backgroundColor: hexToRgba(color, .20),
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => fmt(c.parsed.y)
                            }
                        },
                        title: {
                            display: true,
                            text: 'Saldo do mês (diário)'
                        }
                    },
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute,
                                callback: (v) => fmt(v)
                            }
                        }
                    }
                }
            });
        }

        function drawBars(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();

            const rec = THEME.brand.green;
            const des = THEME.brand.orange;

            st.chart = new Chart($('#c'), {
                type: 'bar',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                            label: 'Receitas',
                            data: (d.receitas || []).map(Number),
                            backgroundColor: hexToRgba(rec, .55),
                            borderColor: rec,
                            borderWidth: 2
                        },
                        {
                            label: 'Despesas',
                            data: (d.despesas || []).map(Number),
                            backgroundColor: hexToRgba(des, .55),
                            borderColor: des,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => fmt(c.parsed.y)
                            }
                        }
                    },
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute,
                                callback: (v) => fmt(v)
                            }
                        }
                    }
                }
            });
        }

        function drawLine12m(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const color = THEME.brand.cyan;

            st.chart = new Chart($('#c'), {
                type: 'line',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: 'Saldo mensal',
                        data: (d.values || []).map(Number),
                        tension: .3,
                        borderWidth: 2,
                        borderColor: color,
                        pointRadius: 2,
                        backgroundColor: hexToRgba(color, .18),
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => fmt(c.parsed.y)
                            }
                        },
                        title: {
                            display: true,
                            text: 'Evolução dos últimos 12 meses'
                        }
                    },
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            ticks: {
                                color: THEME.brand.textMute,
                                callback: (v) => fmt(v)
                            }
                        }
                    }
                }
            });
        }
        // =======================================================================

        // Loader principal
        async function load() {
            loading();
            try {
                await ensureChart();
                applyChartDefaults();
                if (!st.accounts.length) {
                    await loadAccounts();
                }
                const d = await fetchData();
                if (!d || !d.labels || !d.labels.length) {
                    empty();
                    return;
                }

                if (st.view === 'pizza') drawPie(d);
                else if (st.view === 'linha') drawLine(d);
                else if (st.view === 'barras' || st.view === 'contas') drawBars(d);
                else if (st.view === 'evolucao') drawLine12m(d);
            } catch (e) {
                console.error(e);
                empty();
            }
        }

        load();

        // Helpers globais
        window.refreshReports = function() {
            load();
        };
        window.setReportsMonth = function(ym) {
            if (!/^\d{4}-\d{2}$/.test(ym)) return;
            const [y, m] = ym.split('-').map(Number);
            st.d = new Date(y, m - 1, 1);
            sync();
            load();
        };
        window.setReportsView = function(view) {
            const btn = document.querySelector(`#tabs button[data-view="${view}"]`);
            if (btn) btn.click();
        };
        window.setReportsType = function(type) {
            const b = document.querySelector(`.lk-menu button[data-type="${type}"]`);
            if (b) b.click();
        };
    })();
</script>
