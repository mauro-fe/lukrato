<?php
// View: Relatórios – integrada ao layout padrão (header/footer via renderAdmin)
?>

<style>
    /* ====== Layout base (escopado) ====== */
    .lk-page {
        /* container */
    }

    .lk-wrap {
        max-width: 1140px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 0 32px;
        /* gutter principal vai nos cards/grades */
        box-sizing: border-box;
    }

    /* Cabeçalho */
    .lk-h {
        margin-bottom: 16px;
    }

    .lk-titlebar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .lk-t {
        font-size: 28px;
        color: #e67e22;
        margin-left: 20px;
        font-weight: 700;
    }

    .lk-controls {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 0;
        padding: 0 24px;
        /* gutter alinhado com os cards */
    }

    /* ====== Abas (segmented) ====== */
    .lk-seg {
        background: #fff;
        border: 1px solid #e5e7eb;
        padding: 6px;
        border-radius: 999px;
        display: flex;
        gap: 6px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        height: 44px;
    }

    .lk-seg button {
        border: 0;
        background: transparent;
        padding: 10px 14px;
        border-radius: 999px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1;
        height: 32px;
    }

    .lk-seg button.active {
        background: #eef2ff;
        color: #e67e22;
    }

    /* ====== Dropdown ====== */
    .lk-sel {
        position: relative;
    }

    .lk-sel>button {
        background: #fff;
        border: 1px solid #e5e7eb;
        height: 44px;
        border-radius: 999px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        cursor: pointer;
    }

    .lk-menu {
        position: absolute;
        top: 52px;
        left: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        min-width: 260px;
        padding: 8px 0;
        display: none;
        z-index: 10;
    }

    .lk-menu.open {
        display: block;
    }

    .lk-menu>button {
        width: 100%;
        text-align: left;
        background: transparent;
        border: 0;
        padding: 10px 14px;
        cursor: pointer;
    }

    .lk-menu>button:hover {
        background: #f8fafc;
    }

    /* ====== Seletor de período ====== */
    .lk-period {
        margin-top: 20px;
        margin-right: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        padding: 6px 8px;
        height: 44px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
    }

    .lk-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #eef2ff;
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 700;
        color: #e67e22;
    }

    .lk-arrow {
        border: 0;
        background: transparent;
        padding: 8px;
        border-radius: 999px;
        cursor: pointer;
    }

    .lk-arrow:hover {
        background: #f3f4f6;
    }

    /* ====== Cards (grandes), gráficos, estados ====== */
    .lk-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06);
        padding: 18px;
        overflow: hidden;
        margin: 0 24px;
        /* gutter lateral largo */
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
        color: #6b7280;
    }

    .lk-empty h3 {
        font-size: 18px;
        font-weight: 700;
        color: #374151;
        margin: 8px 0 0;
    }

    .lk-empty p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }

    .lk-empty img {
        width: 180px;
        max-width: 50vw;
        opacity: .9;
    }

    .lk-loading {
        padding: 40px;
        text-align: center;
        color: #6b7280;
    }

    /* ====== Responsivo ====== */
    @media (max-width:1024px) {
        /* (sem KPIs aqui) */
    }

    @media (max-width:720px) {
        .lk-controls {
            padding: 0 16px;
        }

        .lk-card {
            margin: 0 16px;
        }

        /* gutter menor no mobile */
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

<div class="lk-wrap">
    <div class="lk-h">
        <!-- barra de título + mês alinhados -->
        <div class="lk-titlebar">
            <div class="lk-t">Relatórios</div>

            <!-- Período (vai para a direita do título) -->
            <div class="lk-period" aria-label="Seletor de mês">
                <button class="lk-arrow" id="prev" aria-label="Mês anterior"><i class="fa-solid fa-angle-left"></i></button>
                <span class="lk-chip" id="month">—</span>
                <button class="lk-arrow" id="next" aria-label="Próximo mês"><i class="fa-solid fa-angle-right"></i></button>
            </div>
        </div>

        <!-- linha de controles (tabs + seletor de tipo) -->
        <div class="lk-controls" role="tablist" aria-label="Tipos de relatório">
            <div class="lk-seg" id="tabs">
                <button class="active" data-view="pizza" aria-pressed="true"><i class="fa-solid fa-chart-pie"></i> Por categoria</button>
                <button data-view="linha" aria-pressed="false"><i class="fa-solid fa-chart-line"></i> Saldo diário</button>
                <button data-view="barras" aria-pressed="false"><i class="fa-solid fa-chart-column"></i> Receitas x Despesas</button>
                <button data-view="evolucao" aria-pressed="false"><i class="fa-solid fa-timeline"></i> Evolução 12m</button>
            </div>

            <div class="lk-sel" id="typeSelect">
                <button id="typeBtn" aria-haspopup="menu" aria-expanded="false">
                    <span class="lb">Despesas por categorias</span> <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="lk-menu" role="menu" aria-label="Escolha do tipo">
                    <button data-type="despesas_por_categoria">Despesas por categorias</button>
                    <button data-type="receitas_por_categoria">Receitas por categorias</button>
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
            chart: null
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

        // Seletor de tipo (apenas pizza)
        const sel = $('#typeSelect');
        const btn = $('#typeBtn');
        const menu = sel.querySelector('.lk-menu');
        btn.addEventListener('click', () => {
            menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', menu.classList.contains('open'));
        });
        document.addEventListener('click', (e) => {
            if (!sel.contains(e.target)) {
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
        menu.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
            st.type = b.dataset.type;
            btn.querySelector('.lb').textContent = b.textContent;
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
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
            sel.style.display = (st.view === 'pizza') ? '' : 'none';
            load();
        }));
        sel.style.display = (st.view === 'pizza') ? '' : 'none';

        // ---- APIs ----
        async function fetchData() {
            const y = st.d.getFullYear();
            const m = String(st.d.getMonth() + 1).padStart(2, '0');

            const type =
                st.view === 'linha' ? 'saldo_mensal' :
                st.view === 'barras' ? 'receitas_despesas_diario' :
                st.view === 'evolucao' ? 'evolucao_12m' :
                st.type; // pizza

            const url = `${base}api/reports?type=${encodeURIComponent(type)}&year=${y}&month=${m}`;
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

        // (opcional) KPIs – se quiser reusar futuramente
        async function loadKpis() {
            /* deixado aqui para futura integração com /api/dashboard/metrics */
        }

        // ---- UI helpers ----
        function setArea(html) {
            $('#area').innerHTML = html;
        }

        function loading() {
            setArea('<div class="lk-loading">Carregando…</div>');
        }

        // ====== Estado vazio (SEM botão) ======
        function empty() {
            setArea(`
            <div class="lk-empty">
                <img src="https://cdn.jsdelivr.net/gh/alohe/illustrations/undraw_clipboard.svg" alt="Sem dados">
                <h3>Nenhum dado encontrado</h3>
                <p>Altere o período ou selecione outro tipo de relatório.</p>
            </div>
        `);
        }

        function destroyChart() {
            if (st.chart) {
                st.chart.destroy();
                st.chart = null;
            }
        }

        // ---- Desenho dos gráficos ----
        function drawPie(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const ttl = (d.values || []).reduce((a, b) => a + Number(b), 0);
            const titulo = (st.type === 'receitas_por_categoria') ? 'Receitas por categorias' : 'Despesas por categorias';
            st.chart = new Chart($('#c'), {
                type: 'doughnut',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: titulo,
                        data: d.values || []
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
                    maintainAspectRatio: false
                }
            });
        }

        function drawLine(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            st.chart = new Chart($('#c'), {
                type: 'line',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: 'Saldo diário (receitas - despesas)',
                        data: (d.values || []).map(Number),
                        tension: .3,
                        fill: false
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
                        y: {
                            ticks: {
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
            st.chart = new Chart($('#c'), {
                type: 'bar',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                            label: 'Receitas',
                            data: (d.receitas || []).map(Number)
                        },
                        {
                            label: 'Despesas',
                            data: (d.despesas || []).map(Number)
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
                        y: {
                            ticks: {
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
            st.chart = new Chart($('#c'), {
                type: 'line',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: 'Saldo mensal',
                        data: (d.values || []).map(Number),
                        tension: .3
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
                        y: {
                            ticks: {
                                callback: (v) => fmt(v)
                            }
                        }
                    }
                }
            });
        }

        // ---- Loader principal ----
        async function load() {
            loading();
            try {
                await ensureChart();
                // await loadKpis(); // (quando integrar)
                const d = await fetchData();
                if (!d.labels || !d.labels.length) return empty();

                if (st.view === 'pizza') drawPie(d);
                else if (st.view === 'linha') drawLine(d);
                else if (st.view === 'barras') drawBars(d);
                else if (st.view === 'evolucao') drawLine12m(d);
            } catch (e) {
                console.error(e);
                empty();
            }
        }

        load();
    })();
</script>