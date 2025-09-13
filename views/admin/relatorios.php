<style>
    /* ====== Layout base (escopado) ====== */
    .container {
        padding: 0;
    }

    /* Cabeçalho */
    .lk-h {
        margin-bottom: 20px;
    }

    .lk-titlebar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-3);
        margin-bottom: var(--spacing-3);
    }

    .lk-t {
        font-size: 28px;
        color: var(--color-primary);
        margin-left: 20px;
        font-weight: 700;
    }

    .lk-controls {
        display: flex;
        gap: var(--spacing-3);
        flex-wrap: wrap;
        margin-top: 0;
        padding: 0 24px;
    }

    /* ====== Abas (segmented) ====== */
    .lk-seg {
        border: 2px solid var(--glass-border);
        padding: 6px;
        border-radius: 999px;
        display: flex;
        gap: 6px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        height: 44px;
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
    }

    .lk-seg button {
        border: 0;
        background: transparent;
        padding: 10px 14px;
        border-radius: 999px;
        font-weight: 600;
        cursor: pointer;
        color: var(--color-text);
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1;
        height: 32px;
    }

    .lk-seg button.active {
        color: var(--color-primary);
    }

    /* ====== Dropdown base ====== */
    .lk-sel {
        position: relative;
    }

    .lk-sel span {
        color: var(--color-primary);
    }

    .lk-sel>button {
        border: 2px solid var(--glass-border);
        height: 44px;
        border-radius: 999px;
        padding: 0 16px;
        display: flex;
        background-color: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        align-items: center;
        gap: 10px;
        font-weight: 600;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        cursor: pointer;
        color: var(--color-text);
    }

    .lk-menu {
        position: absolute;
        top: 52px;
        left: 0;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        min-width: 260px;
        padding: 8px 0;
        display: none;
        z-index: 10;
        background: var(--color-surface);
        color: var(--color-text);
    }

    .lk-menu.open {
        display: block;
    }

    .lk-menu>button {
        width: 90%;
        text-align: center;
        margin: auto;
        border: 0;
        border-radius: 20px;
        padding: 10px 14px;
        cursor: pointer;
        background: var(--glass-bg);
        color: var(--color-text);
        margin-top: 5px;
    }

    .lk-menu>button:hover {
        background: color-mix(in srgb, var(--glass-bg) 80%, var(--color-surface) 20%);
    }

    /* ====== Seletor de período ====== */
    .lk-period {
        margin-top: 20px;
        margin-right: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--glass-border);
        border-radius: 999px;
        padding: 6px 8px;
        height: 44px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        color: var(--color-text);
    }

    .lk-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 700;
        color: var(--color-primary);
    }

    .lk-arrow {
        border: 0;
        padding: 8px;
        border-radius: 999px;
        cursor: pointer;
        background-color: transparent;
        color: var(--color-text);
    }

    /* ====== Cards/gráficos ====== */
    .lk-card {
        border: 2px solid var(--glass-border);
        background: var(--glass-bg);
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        padding: 18px;
        overflow: hidden;
        margin: 0 24px;
        color: var(--color-text);
        backdrop-filter: var(--glass-backdrop);
    }

    .lk-card+.lk-card {
        margin-top: 16px;
    }

    .lk-chart {
        padding: 20px 24px;
    }

    .lk-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 80px 28px;
        text-align: center;
        color: var(--color-text);
    }

    .lk-empty h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 8px 0 0;
        color: var(--color-text);
    }

    .lk-empty p {
        font-size: 14px;
        margin: 0;
        color: var(--color-text-muted);
    }

    .lk-empty img {
        width: 180px;
        max-width: 50vw;
        opacity: .9;
    }

    .lk-loading {
        padding: 40px;
        text-align: center;
        color: var(--color-text);
    }

    /* ====== Responsivo ====== */
    @media (max-width: 720px) {
        .lk-controls {
            padding: 0 16px;
        }

        .lk-card {
            margin: 0 16px;
        }

        .lk-titlebar {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .lk-period,
        .lk-sel>button,
        .lk-seg {
            width: 100%;
        }
    }
</style>

<div class="lk-wrap container pt-4">
    <div class="lk-h">
        <div class="lk-t">
            <h3>Relatórios</h3>
        </div>

        <div class="lk-titlebar">
            <div class="lk-period" aria-label="Seletor de mês">
                <button class="lk-arrow" id="prev" aria-label="Mês anterior"><i class="fa-solid fa-angle-left"></i></button>
                <span class="lk-chip" id="month">—</span>
                <button class="lk-arrow" id="next" aria-label="Próximo mês"><i class="fa-solid fa-angle-right"></i></button>
            </div>
        </div>

        <!-- Controles: Abas + Tipo (pizza) + Conta -->
        <div class="lk-controls pt-4" role="tablist" aria-label="Tipos de relatório">
            <div class="lk-seg" id="tabs">
                <button class="active" data-view="pizza" aria-pressed="true"><i class="fa-solid fa-chart-pie"></i> Por categoria</button>
                <button data-view="linha" aria-pressed="false"><i class="fa-solid fa-chart-line"></i> Saldo diário</button>
                <button data-view="barras" aria-pressed="false"><i class="fa-solid fa-chart-column"></i> Receitas x Despesas</button>
                <button data-view="contas" aria-pressed="false"><i class="fa-solid fa-wallet"></i> Por conta</button>
                <button data-view="evolucao" aria-pressed="false"><i class="fa-solid fa-timeline"></i> Evolução 12m</button>
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

    <div class="lk-card">
        <div id="area"></div>
    </div>
</div>

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
                primary: v('--color-primary', '#E67E22'), // Laranja vibrante
                text: v('--color-text', '#EAF2FF'),
                textMute: v('--color-text-muted', '#94A3B8'),
                surface: v('--color-surface', '#0F2233'),

                // Paleta Lukrato
                green: '#2ECC71',
                orange: '#E67E22',
                yellow: '#F39C12',
                blue: '#2C3E50',
                gray: '#BDC3C7',
                red: '#E74C3C',
                purple: '#9B59B6',
                cyan: '#1ABC9C'
            };

            // paleta p/ setores (donut). Altere a ordem/cores se quiser.
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

        const monthNames = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        const cap = s => s.charAt(0).toUpperCase() + s.slice(1);
        const fmt = v => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(v || 0);

        const st = {
            view: 'pizza',
            type: 'despesas_por_categoria',
            d: new Date(),
            chart: null,
            accounts: [],
            accountId: null // null => todas
        };

        const $ = sel => document.querySelector(sel);
        const monthEl = $('#month');
        const base = '<?= BASE_URL ?>';

        function label(d) {
            return cap(monthNames[d.getMonth()]) + ' ' + d.getFullYear();
        }

        function sync() {
            monthEl.textContent = label(st.d);
        }
        sync();

        // Navegação de meses
        $('#prev').addEventListener('click', () => {
            st.d.setMonth(st.d.getMonth() - 1);
            sync();
            load();
        });
        $('#next').addEventListener('click', () => {
            st.d.setMonth(st.d.getMonth() + 1);
            sync();
            load();
        });

        // Seletor de tipo (pizza)
        const selType = $('#typeSelect');
        const typeBtn = $('#typeBtn');
        const typeMenu = selType.querySelector('.lk-menu');
        typeBtn.addEventListener('click', () => {
            typeMenu.classList.toggle('open');
            typeBtn.setAttribute('aria-expanded', typeMenu.classList.contains('open'));
        });
        document.addEventListener('click', (e) => {
            if (!selType.contains(e.target)) {
                typeMenu.classList.remove('open');
                typeBtn.setAttribute('aria-expanded', 'false');
            }
        });
        typeMenu.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
            st.type = b.dataset.type;
            typeBtn.querySelector('.lb').textContent = b.textContent;
            typeMenu.classList.remove('open');
            typeBtn.setAttribute('aria-expanded', 'false');
            if (st.view === 'pizza') load();
        }));

        // Abas
        document.querySelectorAll('#tabs button').forEach(b => b.addEventListener('click', () => {
            document.querySelectorAll('#tabs button').forEach(x => {
                x.classList.remove('active');
                x.setAttribute('aria-pressed', 'false');
            });
            b.classList.add('active');
            b.setAttribute('aria-pressed', 'true');
            st.view = b.dataset.view;

            // mostra/oculta seletores
            selType.style.display = (st.view === 'pizza') ? '' : 'none';
            accountSelectWrap.style.display = ''; // seletor de conta visível

            load();
        }));
        selType.style.display = (st.view === 'pizza') ? '' : 'none';

        // ====== Seletor de Conta ======
        const accountSelectWrap = $('#accountSelect');
        const accountBtn = $('#accountBtn');
        const accountMenu = accountSelectWrap.querySelector('.lk-menu');

        accountBtn.addEventListener('click', () => {
            accountMenu.classList.toggle('open');
            accountBtn.setAttribute('aria-expanded', accountMenu.classList.contains('open'));
        });
        document.addEventListener('click', (e) => {
            if (!accountSelectWrap.contains(e.target)) {
                accountMenu.classList.remove('open');
                accountBtn.setAttribute('aria-expanded', 'false');
            }
        });

        async function loadAccounts() {
            try {
                const r = await fetch(`${base}api/accounts`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!r.ok) throw new Error('Falha ao carregar contas');
                const json = await r.json();

                // Esperado: array de contas com {id, nome/apelido/instituicao}
                st.accounts = (json.items || json || []).map(a => ({
                    id: Number(a.id),
                    nome: a.nome || a.apelido || a.instituicao || `Conta #${a.id}`
                }));

                // Monta menu
                accountMenu.innerHTML = '';
                const btnAll = document.createElement('button');
                btnAll.textContent = 'Todas as contas';
                btnAll.dataset.id = '';
                btnAll.addEventListener('click', () => {
                    st.accountId = null;
                    accountBtn.querySelector('.lb').textContent = 'Todas as contas';
                    accountMenu.classList.remove('open');
                    accountBtn.setAttribute('aria-expanded', 'false');
                    load();
                });
                accountMenu.appendChild(btnAll);

                st.accounts.forEach(acc => {
                    const b = document.createElement('button');
                    b.textContent = acc.nome;
                    b.dataset.id = acc.id;
                    b.addEventListener('click', () => {
                        st.accountId = acc.id;
                        accountBtn.querySelector('.lb').textContent = acc.nome;
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
                st.type; // pizza

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
                if (!r.ok) return {
                    labels: [],
                    values: []
                };
                return await r.json();
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

        // =================== DESENHOS COM CORES PERSONALIZADAS ===================
        function drawPie(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const ttl = (d.values || []).reduce((a, b) => a + Number(b), 0);
            const titulo = (st.type === 'receitas_por_categoria') ? 'Receitas por categorias' : 'Despesas por categorias';
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

            const rec = THEME.brand.green; // Receitas
            const des = THEME.brand.orange; // Despesas

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
                applyChartDefaults(); // aplica o tema global do Chart.js

                if (!st.accounts.length) {
                    await loadAccounts();
                } // carrega uma vez
                const d = await fetchData();
                if (!d || (!d.labels || !d.labels.length)) {
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