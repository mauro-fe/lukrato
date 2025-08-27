<?php
// View: Relatórios – integrada ao layout padrão (header/footer via renderAdmin)
?>


<style>
    /* ---- Tudo escopado dentro de .lk-page para não interferir no tema ---- */
    .lk-page {
        /* apenas container */
    }

    .lk-wrap {
        max-width: 1140px;
        margin: 0 auto
    }

    .lk-h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px
    }

    .lk-t {
        font-size: 28px;
        color: #e67e22;
        font-weight: 700
    }

    .lk-controls {
        display: flex;
        margin-top: 50px;
        gap: 12px;
        flex-wrap: wrap
    }

    .lk-seg {
        background: #fff;
        border: 1px solid #e5e7eb;
        padding: 6px;
        border-radius: 999px;
        display: flex;
        gap: 6px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06)
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
        gap: 8px
    }

    .lk-seg button.active {
        background: #eef2ff;
        color: #e67e22
    }

    .lk-sel {
        position: relative
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
        cursor: pointer
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
        z-index: 10
    }

    .lk-menu.open {
        display: block
    }

    .lk-menu>button {
        width: 100%;
        text-align: left;
        background: transparent;
        border: 0;
        padding: 10px 14px;
        cursor: pointer
    }

    .lk-menu>button:hover {
        background: #f8fafc
    }

    .lk-period {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        padding: 6px 8px;
        height: 44px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06)
    }

    .lk-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #eef2ff;
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 700;
        color: #e67e22
    }

    .lk-arrow {
        border: 0;
        background: transparent;
        padding: 8px;
        border-radius: 999px;
        cursor: pointer
    }

    .lk-arrow:hover {
        background: #f3f4f6
    }

    .lk-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(17, 24, 39, .06)
    }

    .lk-empty {
        display: grid;
        place-items: center;
        padding: 60px 20px;
        text-align: center;
        color: #6b7280
    }

    .lk-empty img {
        width: 220px;
        max-width: 60vw;
        opacity: .95
    }

    .lk-chart {
        padding: 12px 14px
    }

    .lk-loading {
        padding: 40px;
        text-align: center;
        color: #6b7280
    }

    @media (max-width:720px) {
        .lk-h {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px
        }

        .lk-seg,
        .lk-period,
        .lk-sel>button {
            width: 100%
        }
    }
</style>

<div class="lk-wrap">
    <div class="lk-h">
        <div class="lk-t">Relatórios</div>

        <div class="lk-controls">
            <!-- Abas (pizza/linha) -->
            <div class="lk-seg" id="tabs">
                <button class="active" data-view="pizza"><i class="fa-solid fa-chart-pie"></i> Por
                    categoria</button>
                <button data-view="linha"><i class="fa-solid fa-chart-line"></i> Saldo mensal</button>
            </div>

            <!-- Tipo (só na aba pizza) -->
            <div class="lk-sel" id="typeSelect">
                <button id="typeBtn"><span class="lb">Despesas por categorias</span> <i
                        class="fa-solid fa-chevron-down"></i></button>
                <div class="lk-menu" role="menu">
                    <button data-type="despesas_por_categoria">Despesas por categorias</button>
                    <button data-type="receitas_por_categoria">Receitas por categorias</button>
                </div>
            </div>

            <!-- Período -->
            <div class="lk-period">
                <button class="lk-arrow" id="prev" aria-label="Mês anterior"><i
                        class="fa-solid fa-angle-left"></i></button>
                <span class="lk-chip" id="month">—</span>
                <button class="lk-arrow" id="next" aria-label="Próximo mês"><i
                        class="fa-solid fa-angle-right"></i></button>
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

        const monthNames = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto',
            'setembro', 'outubro', 'novembro', 'dezembro'
        ];
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
            return cap(monthNames[d.getMonth()]) + ' ' + d.getFullYear()
        }

        function sync() {
            monthEl.textContent = label(st.d)
        }
        sync();

        // Navegação de meses
        $('#prev').addEventListener('click', () => {
            st.d.setMonth(st.d.getMonth() - 1);
            sync();
            load()
        });
        $('#next').addEventListener('click', () => {
            st.d.setMonth(st.d.getMonth() + 1);
            sync();
            load()
        });

        // Seletor de tipo (apenas pizza)
        const sel = $('#typeSelect');
        const btn = $('#typeBtn');
        const menu = sel.querySelector('.lk-menu');
        btn.addEventListener('click', () => menu.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sel.contains(e.target)) menu.classList.remove('open');
        });
        menu.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
            st.type = b.dataset.type;
            btn.querySelector('.lb').textContent = b.textContent;
            menu.classList.remove('open');
            if (st.view === 'pizza') load();
        }));

        // Abas
        document.querySelectorAll('#tabs button').forEach(b => b.addEventListener('click', () => {
            document.querySelectorAll('#tabs button').forEach(x => x.classList.remove('active'));
            b.classList.add('active');
            st.view = b.dataset.view;
            // mostra/esconde seletor de tipo
            sel.style.display = (st.view === 'pizza') ? '' : 'none';
            load();
        }));
        sel.style.display = (st.view === 'pizza') ? '' : 'none';

        async function fetchData() {
            const y = st.d.getFullYear();
            const m = String(st.d.getMonth() + 1).padStart(2, '0');
            const type = (st.view === 'linha') ? 'saldo_mensal' : st.type;
            const url = `${base}api/reports?type=${encodeURIComponent(type)}&year=${y}&month=${m}`;
            try {
                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
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

        function setArea(html) {
            $('#area').innerHTML = html;
        }

        function loading() {
            setArea('<div class="lk-loading">Carregando…</div>');
        }

        function empty() {
            setArea(
                '<div class="lk-empty">' +
                '<img src="https://cdn.jsdelivr.net/gh/alohe/illustrations/undraw_clipboard.svg" alt="Sem dados">' +
                '<h3>Nenhum resultado</h3>' +
                '<p>Altere o período ou o tipo.</p>' +
                '</div>'
            );
        }

        function destroyChart() {
            if (st.chart) {
                st.chart.destroy();
                st.chart = null;
            }
        }

        function drawPie(d) {
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');
            destroyChart();
            const map = {
                despesas_por_categoria: 'Despesas por categorias',
                receitas_por_categoria: 'Receitas por categorias'
            };
            const ttl = (d.values || []).reduce((a, b) => a + Number(b), 0);
            st.chart = new Chart($('#c'), {
                type: 'doughnut',
                data: {
                    labels: d.labels || [],
                    datasets: [{
                        label: map[st.type],
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
                            text: `${map[st.type]} • Total ${fmt(ttl)}`
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

        async function load() {
            loading();
            try {
                await ensureChart();
                const d = await fetchData();
                if (!d.labels || !d.labels.length) return empty();
                if (st.view === 'pizza') drawPie(d);
                else drawLine(d);
            } catch (e) {
                console.error(e);
                empty();
            }
        }

        load();
    })();
</script>