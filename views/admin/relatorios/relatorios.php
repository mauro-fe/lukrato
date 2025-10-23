<style>
    /* Segmented Control - Estilo moderno */
    .lk-seg {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        padding: var(--spacing-2);
        box-shadow: var(--shadow-md);
        position: relative;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        scrollbar-color: var(--color-primary) transparent;
    }

    /* Scrollbar customizada para overflow horizontal */
    .lk-seg::-webkit-scrollbar {
        height: 4px;
    }

    .lk-seg::-webkit-scrollbar-track {
        background: transparent;
    }

    .lk-seg::-webkit-scrollbar-thumb {
        background: var(--color-primary);
        border-radius: var(--radius-sm);
    }

    .lk-seg::-webkit-scrollbar-thumb:hover {
        background: var(--color-secondary);
    }

    /* Botões do segmented control */
    .lk-seg button {
        position: relative;
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3) var(--spacing-4);
        background: transparent;
        border: 1px solid transparent;
        border-radius: var(--radius-md);
        color: var(--color-text);
        font-family: var(--font-primary);
        font-size: var(--font-size-sm);
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: all var(--transition-normal);
        overflow: hidden;
        z-index: 1;
    }

    /* Efeito de hover sutil */
    .lk-seg button::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--color-primary);
        opacity: 0;
        transition: opacity var(--transition-fast);
        border-radius: var(--radius-md);
        z-index: -1;
    }

    .lk-seg button:hover::before {
        opacity: 0.08;
    }

    .lk-seg button:hover {
        color: var(--color-text);
        border-color: var(--glass-border);
        transform: translateY(-1px);
    }

    /* Ícones */
    .lk-seg button i {
        font-size: var(--font-size-base);
        transition: transform var(--transition-normal);
    }

    .lk-seg button:hover i {
        transform: scale(1.1);
    }

    /* Estado ativo */
    .lk-seg button.active {
        background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 85%, var(--color-secondary) 15%));
        border-color: var(--color-primary);
        color: white;
        font-weight: 600;
        box-shadow: var(--shadow-md);
        transform: translateY(0);
    }

    .lk-seg button.active::before {
        display: none;
    }

    .lk-seg button.active:hover {
        background: linear-gradient(135deg, var(--color-secondary), color-mix(in srgb, var(--color-secondary) 85%, var(--color-primary) 15%));
        border-color: var(--color-secondary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .lk-seg button.active i {
        animation: iconPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes iconPop {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Estado de foco (acessibilidade) */
    .lk-seg button:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px var(--ring);
        z-index: 2;
    }

    /* Estado pressed */
    .lk-seg button:active {
        transform: scale(0.97);
    }

    .lk-seg button.active:active {
        transform: scale(0.98);
    }

    /* Indicador deslizante animado (opcional - efeito premium) */
    .lk-seg::after {
        content: '';
        position: absolute;
        bottom: var(--spacing-2);
        left: var(--spacing-2);
        height: calc(100% - var(--spacing-4));
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        border-radius: var(--radius-md);
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        pointer-events: none;
        opacity: 0;
        z-index: 0;
    }

    /* Variante compacta */
    .lk-seg.lk-seg-sm button {
        padding: var(--spacing-2) var(--spacing-3);
        font-size: var(--font-size-xs);
        gap: var(--spacing-1);
    }

    .lk-seg.lk-seg-sm button i {
        font-size: var(--font-size-sm);
    }

    /* Variante vertical */
    .lk-seg.lk-seg-vertical {
        flex-direction: column;
        width: fit-content;
        overflow-x: hidden;
        overflow-y: auto;
    }

    .lk-seg.lk-seg-vertical button {
        width: 100%;
        justify-content: flex-start;
    }

    /* Badges/contadores nos botões (opcional) */
    .lk-seg button .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 var(--spacing-1);
        background: var(--color-surface-muted);
        color: var(--color-text);
        font-size: var(--font-size-xs);
        font-weight: 600;
        border-radius: var(--radius-sm);
        margin-left: var(--spacing-1);
        transition: all var(--transition-fast);
    }

    .lk-seg button.active .badge {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }

    /* Loading state */
    .lk-seg button.loading {
        pointer-events: none;
        opacity: 0.6;
    }

    .lk-seg button.loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Disabled state */
    .lk-seg button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Tema escuro - ajustes específicos */
    :root[data-theme="dark"] .lk-seg {
        box-shadow: var(--shadow-lg), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    /* Tema claro - ajustes específicos */
    :root[data-theme="light"] .lk-seg {
        background: rgba(255, 255, 255, 0.6);
    }

    :root[data-theme="light"] .lk-seg button:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .lk-seg {
            gap: var(--spacing-1);
            padding: var(--spacing-1);
        }

        .lk-seg button {
            padding: var(--spacing-2) var(--spacing-3);
            font-size: var(--font-size-xs);
            gap: var(--spacing-1);
        }

        .lk-seg button i {
            font-size: var(--font-size-sm);
        }
    }

    @media (max-width: 768px) {
        .lk-seg {
            overflow-x: auto;
            scrollbar-width: none;
        }

        .lk-seg::-webkit-scrollbar {
            display: none;
        }

        /* Indicador visual de scroll */
        .lk-seg::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40px;
            height: 100%;
            background: linear-gradient(to left, var(--color-surface), transparent);
            pointer-events: none;
            opacity: 0;
            transition: opacity var(--transition-fast);
            z-index: 10;
        }

        .lk-seg.has-scroll::before {
            opacity: 1;
        }
    }

    @media (max-width: 640px) {
        .lk-seg button span:not(.badge) {
            display: none;
        }

        .lk-seg button {
            min-width: 44px;
            justify-content: center;
            padding: var(--spacing-3);
        }

        .lk-seg button i {
            margin: 0;
        }
    }

    /* Animação de entrada */
    .lk-seg[data-aos] button {
        opacity: 0;
        transform: translateY(10px);
        animation: slideInButton 0.4s ease forwards;
    }

    .lk-seg[data-aos] button:nth-child(1) {
        animation-delay: 0.05s;
    }

    .lk-seg[data-aos] button:nth-child(2) {
        animation-delay: 0.10s;
    }

    .lk-seg[data-aos] button:nth-child(3) {
        animation-delay: 0.15s;
    }

    .lk-seg[data-aos] button:nth-child(4) {
        animation-delay: 0.20s;
    }

    .lk-seg[data-aos] button:nth-child(5) {
        animation-delay: 0.25s;
    }

    @keyframes slideInButton {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Ripple effect ao clicar (efeito premium) */
    .lk-seg button {
        position: relative;
        overflow: hidden;
    }

    .lk-seg button .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: rippleEffect 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes rippleEffect {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
</style>

<section class="rel-page">

    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>
    <section class="lk-h">
        <!-- Controles: Abas + Tipo (pizza) + Conta -->
        <div class="lk-controls py-4" role="tablist" aria-label="Tipos de relatório">
            <div class="lk-seg" id="tabs" data-aos="fade-up-right">
                <button class="active" data-view="pizza" aria-pressed="true"><i class="fa-solid fa-chart-pie"></i>
                    Por
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
            <div class="lk-sel" id="typeSelectWrap" data-aos="fade-up-left">
                <label for="reportTypeSelect" class="sr-only">Tipo de relatório</label>
                <select id="reportTypeSelect" class="lk-select btn btn-primary" aria-label="Escolher tipo de relatório">
                    <option value="despesas_por_categoria">Despesas por categorias</option>
                    <option value="receitas_por_categoria">Receitas por categorias</option>
                </select>
            </div>

            <!-- contas -->
            <div data-aos="fade-up-right">
                <div class="lk-sel" id="accountSelectWrap" style="display:none">
                    <label for="reportAccountSelect" class="sr-only">Conta para filtrar</label>
                    <select id="reportAccountSelect" class="lk-select btn btn-primary" aria-label="Filtrar por conta">
                        <option value="">Todas as contas</option>
                    </select>
                </div>
            </div>
        </div>
    </section>


    <section class="lk-report-area" data-aos="fade-up">
        <div id="area" class="lk-report-area-body"></div>
    </section>

</section>
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
            const palette = ['#E67E22', '#2C3E50', '#2ECC71', '#BDC3C7', '#F39C12', '#9B59B6', '#1ABC9C',
                '#E74C3C'
            ];
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

        const fmt = v => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(v || 0));
        const cap = s => s.charAt(0).toUpperCase() + s.slice(1);

        // Helpers de mês (string YYYY-MM)
        const ymNow = () => new Date().toISOString().slice(0, 7);
        const ymToDate = (ym) => {
            const [y, m] = String(ym || '').split('-').map(Number);
            return new Date(y || new Date().getFullYear(), (m || 1) - 1, 1);
        };
        const ymLabel = (ym) => {
            try {
                const d = ymToDate(ym);
                return d.toLocaleDateString('pt-BR', {
                    month: 'long',
                    year: 'numeric'
                });
            } catch {
                return '-';
            }
        };
        const addMonthsYM = (ym, delta) => {
            try {
                const d = ymToDate(ym);
                d.setMonth(d.getMonth() + delta);
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                return `${y}-${m}`;
            } catch {
                return ym;
            }
        };

        // ===== State =====
        const st = {
            view: 'pizza',
            type: 'despesas_por_categoria',
            includeTransfers: false,
            chart: null,
            accounts: [],
            accountId: null,
            month: (window.LukratoHeader?.getMonth?.()) || ymNow(),
        };

        const $ = sel => document.querySelector(sel);
        const $$ = sel => Array.from(document.querySelectorAll(sel));
        const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

        const base = '<?= BASE_URL ?>';

        // Header (apenas label e setas; SEM modal)
        const currentMonthTextEl = $('#currentMonthText');
        const prevBtn = $('#prevMonth');
        const nextBtn = $('#nextMonth');

        function syncLabel() {
            if (currentMonthTextEl) currentMonthTextEl.textContent = ymLabel(st.month);
        }
        syncLabel();

        // Navegação pelas setas -> usa LukratoHeader.setMonth
        on(prevBtn, 'click', () => {
            const nextYM = addMonthsYM(st.month, -1);
            window.LukratoHeader?.setMonth?.(nextYM);
            // também atualiza localmente (caso outra tela não esteja presente)
            st.month = nextYM;
            syncLabel();
            load();
        });
        on(nextBtn, 'click', () => {
            const nextYM = addMonthsYM(st.month, +1);
            window.LukratoHeader?.setMonth?.(nextYM);
            st.month = nextYM;
            syncLabel();
            load();
        });

        // ===== Seletor de tipo (pizza) =====
        const typeSelectWrap = $('#typeSelectWrap');
        const typeSelect = $('#reportTypeSelect');
        if (typeSelect) {
            typeSelect.value = st.type;
            on(typeSelect, 'change', () => {
                st.type = typeSelect.value;
                if (st.view === 'pizza') load();
            });
        }
        if (typeSelectWrap) typeSelectWrap.style.display = (st.view === 'pizza') ? '' : 'none';

        // ===== Abas =====
        const accountSelectWrap = $('#accountSelectWrap');
        const accountSelect = $('#reportAccountSelect');
        if (accountSelect) {
            on(accountSelect, 'change', () => {
                const val = accountSelect.value;
                st.accountId = val ? Number(val) : null;
                load();
            });
        }
        $$('#tabs button').forEach((b) =>
            on(b, 'click', () => {
                $$('#tabs button').forEach((x) => {
                    x.classList.remove('active');
                    x.setAttribute('aria-pressed', 'false');
                });
                b.classList.add('active');
                b.setAttribute('aria-pressed', 'true');
                st.view = b.dataset.view;

                if (typeSelectWrap) typeSelectWrap.style.display = (st.view === 'pizza') ? '' : 'none';
                if (accountSelectWrap) accountSelectWrap.style.display = '';
                load();
            })
        );

        async function loadAccounts() {
            if (!accountSelectWrap || !accountSelect) return;
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

                accountSelect.innerHTML = '<option value="">Todas as contas</option>';
                st.accounts.forEach(acc => {
                    const option = document.createElement('option');
                    option.value = String(acc.id);
                    option.textContent = acc.nome;
                    accountSelect.appendChild(option);
                });
                accountSelect.value = st.accountId ? String(st.accountId) : '';
                accountSelectWrap.style.display = '';
            } catch (e) {
                console.warn('Contas: nao foi possivel carregar.', e);
                if (accountSelect) accountSelect.innerHTML = '<option value="">Todas as contas</option>';
                accountSelectWrap.style.display = 'none';
            }
        }

        // --- helper universal para 401/403 nesta página ---
        async function handleFetch403(response, base) {
            // 401: login
            if (response.status === 401) {
                const here = encodeURIComponent(location.pathname + location.search);
                location.href = `${base}login?return=${here}`;
                return true;
            }

            // redireciona para /billing
            const goToBilling = () => {
                location.href = `${base}billing`;
            };

            // 403: proibido -> mostra “Assinar” + “OK”
            if (response.status === 403) {
                let msg = 'Acesso não permitido.';
                try {
                    const data = await response.clone().json();
                    msg = data?.message || msg;
                } catch {}

                if (typeof Swal !== 'undefined' && Swal.fire) {
                    const ret = await Swal.fire({
                        title: 'Acesso restrito',
                        html: msg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Assinar',
                        cancelButtonText: 'OK',
                        reverseButtons: true,
                        focusConfirm: true
                    });
                    if (ret.isConfirmed) goToBilling();
                } else {
                    if (confirm(`${msg}\n\nIr para a página de assinatura agora?`)) {
                        goToBilling();
                    }
                }
                return true;
            }

            return false;
        }

        // ---- APIs ----
        async function fetchData() {
            const y = Number(st.month.split('-')[0]);
            const m = st.month.split('-')[1];

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
                if (await handleFetch403(r, base)) return {
                    labels: [],
                    values: []
                };
                if (!r.ok) throw new Error('Falha na API');
                const json = await r.json();
                const payload = (json && typeof json === 'object' && 'status' in json && 'data' in json) ?
                    json
                    .data : json;
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
        <h3>Nenhum dado encontrado</h3>
        <p>Altere o período, o tipo ou a conta.</p>
      </div>`);
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
            destroyChart(); // destrói o chart atual antes de trocar o DOM
            setArea('<div class="lk-chart"><canvas id="c" height="320"></canvas></div>');

            const color = THEME.brand.cyan;

            st.chart = new Chart(document.getElementById('c'), {
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
            setArea('<div class="lk-loading">Carregando…</div>');
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
            st.month = ym;
            window.LukratoHeader?.setMonth?.(ym);
            syncLabel();
            load();
        };
        window.setReportsView = function(view) {
            const btn = document.querySelector(`#tabs button[data-view="${view}"]`);
            if (btn) btn.click();
        };
        window.setReportsType = function(type) {
            const select = document.getElementById('reportTypeSelect');
            if (!select) return;
            const hasOption = Array.from(select.options).some(opt => opt.value === type);
            if (!hasOption) return;
            select.value = type;
            st.type = type;
            if (st.view === 'pizza') load();
        };

        // Reage ao mês global vindo do month-picker.js
        document.addEventListener('lukrato:month-changed', (e) => {
            const m = e.detail?.month;
            if (!m || m === st.month) return;
            st.month = m;
            syncLabel();
            load();
        });
    })();
</script>

<script>
    // Adiciona efeito ripple ao clicar
    document.querySelectorAll('.lk-seg button').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');

            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Detecta scroll horizontal para mostrar indicador
    const seg = document.querySelector('.lk-seg');
    if (seg) {
        const checkScroll = () => {
            if (seg.scrollWidth > seg.clientWidth) {
                seg.classList.add('has-scroll');
            } else {
                seg.classList.remove('has-scroll');
            }
        };

        checkScroll();
        window.addEventListener('resize', checkScroll);
    }
</script>