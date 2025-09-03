<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$username  = $username  ?? 'usuário';
$menu      = $menu      ?? 'dashboard';
$base      = rtrim(BASE_URL ?? '/', '/') . '/';
?>

<!-- Libs (somente as necessárias aqui) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<?php loadPageCss(); ?>


<?php
$active = function (string $key) use ($menu) {
    return $menu === $key ? 'active' : '';
};
$aria   = function (string $key) use ($menu) {
    return $menu === $key ? ' aria-current="page"' : '';
};
?>

<!-- Header (seletor de mês) -->
<header class="lk-header">
    <div class="header-left">
        <div class="month-selector">
            <button class="month-nav-btn" id="prevMonth" aria-label="Mês anterior"><i class="fas fa-chevron-left"></i></button>
            <div class="month-display">
                <button class="month-dropdown-btn" id="monthDropdownBtn" aria-haspopup="true" aria-expanded="false">
                    <span id="currentMonthText"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="month-dropdown" id="monthDropdown" role="menu"></div>
            </div>
            <button class="month-nav-btn" id="nextMonth" aria-label="Próximo mês"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</header>

<!-- Conteúdo -->
<section>
    <div class="container">
        <!-- KPIs -->
        <section class="kpi-grid" role="region" aria-label="Indicadores principais">
            <div class="card kpi-card" id="saldoCard">
                <div class="card-header">
                    <div class="kpi-icon saldo"><i class="fas fa-wallet"></i></div><span class="kpi-title">Saldo Atual</span>
                </div>
                <div class="kpi-value" id="saldoValue">R$ 0,00</div>
            </div>
            <div class="card kpi-card" id="receitasCard">
                <div class="card-header">
                    <div class="kpi-icon receitas"><i class="fas fa-arrow-up"></i></div><span class="kpi-title">Receitas do Mês</span>
                </div>
                <div class="kpi-value receitas" id="receitasValue">R$ 0,00</div>
            </div>
            <div class="card kpi-card" id="despesasCard">
                <div class="card-header">
                    <div class="kpi-icon despesas"><i class="fas fa-arrow-down"></i></div><span class="kpi-title">Despesas do Mês</span>
                </div>
                <div class="kpi-value despesas" id="despesasValue">R$ 0,00</div>
            </div>
        </section>

        <!-- Gráfico + Resumo -->
        <section class="charts-grid">
            <div class="card chart-card">
                <div class="card-header">
                    <h2 class="card-title">Evolução Financeira</h2>
                </div>
                <div class="chart-container"><canvas id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></canvas></div>
            </div>

            <div class="card summary-card">
                <div class="card-header">
                    <h2 class="card-title">Resumo Mensal</h2>
                </div>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Total Receitas</span><span class="summary-value receitas" id="totalReceitas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Total Despesas</span><span class="summary-value despesas" id="totalDespesas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Resultado</span><span class="summary-value" id="resultadoMes">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Saldo Acumulado</span><span class="summary-value" id="saldoAcumulado">R$ 0,00</span></div>
                </div>
            </div>
        </section>

        <!-- Tabela -->
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

<!-- Modal Único de Lançamento -->
<div class="lk-modal" id="modalLancamento" role="dialog" aria-labelledby="modalLancamentoTitle" aria-hidden="true">
    <div class="lk-modal-backdrop"></div>
    <div class="lk-modal-content">
        <div class="lk-modal-header">
            <h2 id="modalLancamentoTitle">Novo Lançamento</h2>
            <button class="lk-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
        </div>
        <form class="lk-modal-body" id="formLancamento" novalidate>
            <div class="form-group">
                <label for="lanTipo">Tipo</label>
                <select id="lanTipo" class="form-select" required>
                    <option value="despesa">Despesa</option>
                    <option value="receita">Receita</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lanData">Data</label>
                <input type="date" id="lanData" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="lanCategoria">Categoria</label>
                <select id="lanCategoria" class="form-select" required>
                    <option value="">Selecione uma categoria</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lanDescricao">Descrição</label>
                <input type="text" id="lanDescricao" class="form-input" placeholder="Descrição do lançamento">
            </div>
            <div class="form-group">
                <label for="lanObservacao">Observação (opcional)</label>
                <input type="text" id="lanObservacao" class="form-input" placeholder="Detalhe, nota interna...">
            </div>
            <div class="form-group">
                <label for="lanValor">Valor</label>
                <input type="text" id="lanValor" class="form-input money-mask" placeholder="R$ 0,00" required>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="lanPago"><span class="checkbox-custom"></span>
                    <span id="lanPagoLabel">Foi pago?</span>
                </label>
            </div>
        </form>
        <div class="lk-modal-footer">
            <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
            <button type="submit" form="formLancamento" class="btn btn-primary">Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Seletor de Data/Mês (mantido) -->
<div class="lk-modal" id="monthPickerModal" aria-hidden="true" role="dialog" aria-labelledby="monthPickerTitle">
    <div class="lk-modal-backdrop" data-close-month></div>
    <div class="lk-modal-content" role="document">
        <div class="lk-modal-header" style="gap:12px;">
            <h2 id="monthPickerTitle" style="margin-right:auto;">Escolher data</h2>
            <div class="month-picker-nav" aria-live="polite">
                <button class="month-nav-btn" id="mpPrev" aria-label="Mês anterior"><i class="fas fa-chevron-left"></i></button>
                <span id="mpLabel" class="mp-label" style="min-width:170px;text-align:center;font-weight:700;"></span>
                <button class="month-nav-btn" id="mpNext" aria-label="Próximo mês"><i class="fas fa-chevron-right"></i></button>
            </div>
            <button class="lk-modal-close" data-close-month aria-label="Fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="lk-modal-body">
            <div class="calendar">
                <div class="calendar-weekdays">
                    <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>
        </div>
        <div class="lk-modal-footer">
            <button class="btn btn-ghost" data-close-month>Cancelar</button>
            <button class="btn btn-primary" id="mpConfirm">Usar mês</button>
        </div>
    </div>
</div>

<?php loadPageJs(); ?>
<script>
    (() => {
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

        const money = n => Number(n || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        const dateBR = iso => {
            if (!iso) return '—';
            const m = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
        };
        const $ = (s, sc = document) => sc.querySelector(s);

        async function getJSON(url) {
            const r = await fetch(url, {
                credentials: 'include'
            });
            const j = await r.json().catch(() => null);
            if (!r.ok || j?.error || j?.status === 'error') throw new Error(j?.message || j?.error || `HTTP ${r.status}`);
            return j;
        }
        const apiMetrics = m => getJSON(`${BASE}api/dashboard/metrics?month=${encodeURIComponent(m)}`);
        const apiTransactions = (m, l = 50) => getJSON(`${BASE}api/dashboard/transactions?month=${encodeURIComponent(m)}&limit=${l}`);

        /* ============ Controle de mês (header se houver, senão local) ============ */
        const STORAGE_KEY = 'lukrato.month.dashboard';

        // elementos locais opcionais (HTML da própria página)
        const $label = document.getElementById('dashLabel') || document.getElementById('currentMonthText');
        const $prev = document.getElementById('dashPrev') || document.getElementById('prevMonth');
        const $next = document.getElementById('dashNext') || document.getElementById('nextMonth');

        const monthLabel = (m) => {
            const [y, mm] = String(m || '').split('-').map(Number);
            if (!y || !mm) return '—';
            return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        };
        const addMonths = (m, delta) => {
            const [y, mm] = m.split('-').map(Number);
            const d = new Date(y, mm - 1 + delta, 1);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        };

        // fonte de verdade inicial
        let currentMonth =
            (window.LukratoHeader?.getMonth?.()) ||
            sessionStorage.getItem(STORAGE_KEY) ||
            new Date().toISOString().slice(0, 7);

        function writeLabel() {
            if ($label) $label.textContent = monthLabel(currentMonth);
        }

        function setLocalMonth(m, {
            emit = true
        } = {}) {
            currentMonth = m;
            sessionStorage.setItem(STORAGE_KEY, currentMonth);
            writeLabel();
            if (emit) document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: {
                    month: currentMonth
                }
            }));
        }

        // se não houver LukratoHeader, ligamos os botões locais
        $prev?.addEventListener('click', async () => {
            setLocalMonth(addMonths(currentMonth, -1));
            await renderAll();
        });
        $next?.addEventListener('click', async () => {
            setLocalMonth(addMonths(currentMonth, +1));
            await renderAll();
        });

        // se houver LukratoHeader, só ouvimos (não mudamos estado do header)
        document.addEventListener('lukrato:month-changed', async (e) => {
            const newMonth = e.detail?.month;
            if (!newMonth || newMonth === currentMonth) return;
            currentMonth = newMonth;
            // mantém label local sincronizado também
            writeLabel();
            await renderAll();
        });

        /* ============ Renderizadores ============ */
        async function renderKPIs() {
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
                if (el) el.textContent = money(k[key] || 0);
            });
        }

        async function renderTable() {
            const tbody = $('#transactionsTableBody');
            const empty = $('#emptyState');
            if (!tbody || !empty) return;

            const list = await apiTransactions(currentMonth, 50);
            tbody.innerHTML = '';
            empty.style.display = list.length ? 'none' : 'block';

            list.forEach(t => {
                const tr = document.createElement('tr');
                const color = t.tipo === 'receita' ? 'var(--verde)' :
                    (String(t.tipo || '').startsWith('despesa') ? 'var(--vermelho)' : 'var(--laranja)');
                tr.innerHTML = `
        <td>${dateBR(t.data)}</td>
        <td>${String(t.tipo||'').replace('_',' ')}</td>
        <td>${t.categoria?.nome || '—'}</td>
        <td>—</td>
        <td>${t.descricao || t.observacao || '—'}</td>
        <td style="font-weight:700;text-align:right;color:${color}">${money(Number(t.valor)||0)}</td>`;
                tbody.appendChild(tr);
            });
        }

        let chartInstance = null;
        async function drawChart() {
            const canvas = document.getElementById('evolutionChart');
            if (!canvas || typeof Chart === 'undefined') return;

            // últimos 6 meses relativos ao currentMonth
            const months = Array.from({
                length: 6
            }, (_, i) => {
                const [y, m] = currentMonth.split('-').map(Number);
                const d = new Date(y, m - 1 - (5 - i), 1);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            });
            const labels = months.map(m => {
                const [y, mm] = m.split('-').map(Number);
                return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                    month: 'short'
                });
            });
            const results = await Promise.all(months.map(m => apiMetrics(m).catch(() => ({
                resultado: 0
            }))));
            const series = results.map(x => Number(x.resultado || 0));

            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(230,126,34,0.35)');
            grad.addColorStop(1, 'rgba(230,126,34,0.05)');

            if (!chartInstance) {
                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
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
                    },
                    options: {
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
                    }
                });
            } else {
                chartInstance.data.labels = labels;
                chartInstance.data.datasets[0].data = series;
                chartInstance.update();
            }
        }

        async function renderAll() {
            writeLabel();
            await Promise.all([renderKPIs(), renderTable(), drawChart()]);
        }

        // Exponho pra você chamar depois de salvar algo
        window.refreshDashboard = renderAll;

        /* ============ Boot ============ */
        document.addEventListener('DOMContentLoaded', async () => {
            writeLabel();
            await renderAll();
        });
    })();
</script>

<script>
    (() => {
        const STORAGE_KEY = 'lukrato.month.dashboard';
        const $ = (s, sc = document) => sc.querySelector(s);

        const $btn = $('#monthDropdownBtn');
        const $drop = $('#monthDropdown');
        const $label = $('#currentMonthText');
        const $prev = $('#prevMonth');
        const $next = $('#nextMonth');

        // estado do mês (pega do header se existir; senão do storage; senão hoje)
        let currentMonth = (window.LukratoHeader?.getMonth?.()) ||
            sessionStorage.getItem(STORAGE_KEY) ||
            new Date().toISOString().slice(0, 7);

        // ano “focado” no dropdown (inicia no ano do currentMonth)
        let viewYear = Number(currentMonth.split('-')[0]);

        const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        const monthLabel = (m) => {
            const [y, mm] = m.split('-').map(Number);
            return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        };
        const addMonths = (m, delta) => {
            const [y, mm] = m.split('-').map(Number);
            const d = new Date(y, mm - 1 + delta, 1);
            return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        };
        const setMonth = (m, {
            emit = true
        } = {}) => {
            currentMonth = m;
            sessionStorage.setItem(STORAGE_KEY, currentMonth);
            if ($label) $label.textContent = monthLabel(currentMonth);
            if (emit) document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: {
                    month: currentMonth
                }
            }));
        };

        // escreve label inicial
        if ($label) $label.textContent = monthLabel(currentMonth);

        // navegação setas (mes a mes)
        $prev?.addEventListener('click', () => setMonth(addMonths(currentMonth, -1)));
        $next?.addEventListener('click', () => setMonth(addMonths(currentMonth, +1)));

        // dropdown: template + render
        function renderDropdown() {
            if (!$drop) return;
            const curY = viewYear;
            const cm = currentMonth;

            const curMonthNum = Number(cm.split('-')[1]); // 1..12

            $drop.innerHTML = `
      <div class="mdp-head" style="display:flex;align-items:center;gap:8px;justify-content:space-between;padding:8px 8px 0;">
        <button class="mdp-y-prev" aria-label="Ano anterior" style="background:none;border:0;color:inherit;cursor:pointer;padding:6px 8px;">‹</button>
        <strong class="mdp-year" aria-live="polite">${curY}</strong>
        <button class="mdp-y-next" aria-label="Próximo ano" style="background:none;border:0;color:inherit;cursor:pointer;padding:6px 8px;">›</button>
      </div>
      <div class="mdp-grid" role="grid" style="display:grid;grid-template-columns:repeat(3, minmax(80px,1fr));gap:8px;padding:10px;">
        ${MONTHS.map((mName,i)=>{
          const mNum = i+1;
          const isActive = (mNum===curMonthNum && curY===Number(cm.split('-')[0]));
          return `
            <button class="mdp-month ${isActive?'active':''}" data-month="${String(mNum).padStart(2,'0')}" data-year="${curY}"
              role="gridcell"
              style="background:var(--azul);border:1px solid var(--glass-border);border-radius:12px;padding:10px;cursor:pointer;text-transform:capitalize;">
              ${mName}
            </button>`;
        }).join('')}
      </div>
    `;

            // binds do header do dropdown
            $drop.querySelector('.mdp-y-prev')?.addEventListener('click', (e) => {
                e.stopPropagation();
                viewYear--;
                renderDropdown();
            });
            $drop.querySelector('.mdp-y-next')?.addEventListener('click', (e) => {
                e.stopPropagation();
                viewYear++;
                renderDropdown();
            });

            // bind de cada mês
            $drop.querySelectorAll('.mdp-month').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const y = btn.getAttribute('data-year');
                    const mm = btn.getAttribute('data-month');
                    setMonth(`${y}-${mm}`);
                    closeDropdown();
                });
            });
        }

        function openDropdown() {
            if (!$drop) return;
            viewYear = Number(currentMonth.split('-')[0]); // sempre abrir no ano do currentMonth
            renderDropdown();
            $drop.classList.add('active');
            $btn?.setAttribute('aria-expanded', 'true');
            // fechar ao clicar fora
            document.addEventListener('click', handleOutside, {
                once: true
            });
            document.addEventListener('keydown', onEsc, {
                once: true
            });
        }

        function closeDropdown() {
            if (!$drop) return;
            $drop.classList.remove('active');
            $btn?.setAttribute('aria-expanded', 'false');
        }

        function handleOutside(ev) {
            if ($drop.contains(ev.target) || $btn.contains(ev.target)) {
                // clicou dentro – reanexa o outside pra próxima interação
                document.addEventListener('click', handleOutside, {
                    once: true
                });
                return;
            }
            closeDropdown();
        }

        function onEsc(ev) {
            if (ev.key === 'Escape') closeDropdown();
        }

        // toggle do botão central
        $btn?.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = $drop.classList.contains('active');
            if (isOpen) closeDropdown();
            else openDropdown();
        });

        // se algum outro script (ex.: seu header global) mudar o mês, mantém o label sincronizado aqui também
        document.addEventListener('lukrato:month-changed', (e) => {
            const m = e.detail?.month;
            if (!m || m === currentMonth) return;
            currentMonth = m;
            sessionStorage.setItem(STORAGE_KEY, currentMonth);
            if ($label) $label.textContent = monthLabel(currentMonth);
        });
    })();
</script>