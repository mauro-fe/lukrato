<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>


<?php
$active = function (string $key) use ($menu) {
    return $menu === $key ? 'active' : '';
};
$aria   = function (string $key) use ($menu) {
    return $menu === $key ? ' aria-current="page"' : '';
};
?>
<section class="dashboard-page">
    <div>
        <h3>Dashboard</h3>
    </div>
    <header class="dash-lk-header">
        <div class="header-left">
            <div class="month-selector">
                <div class="lk-period">
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

            <section class="card table-card mb-5">
                <div class="card-header">
                    <h2 class="card-title">5 Últimos Lançamentos</h2>
                </div>
                <div class="table-container">
                    <div class="empty-state" id="emptyState" style="display:none;">
                        <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                        <h3>Nenhum lançamento encontrado</h3>
                        <p>Adicione sua primeira transação clicando no botão + no canto inferior esquerdo</p>
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
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>

    <div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
            <div class="modal-content bg-dark text-light border-0 rounded-3">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body pt-0">
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
            } catch {
                return 'R$ 0,00';
            }
        };

        const dateBR = iso => {
            if (!iso) return '-';
            try {
                const d = String(iso).split(/[T\s]/)[0];
                const m = d.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                return m ? `${m[3]}/${m[2]}/${m[1]}` : '-';
            } catch {
                return '-';
            }
        };

        const $ = (s, sc = document) => sc.querySelector(s);

        async function getJSON(url) {
            const r = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const j = await r.json();
            if (j?.error || j?.status === 'error') throw new Error(j?.message || j?.error || 'Erro na API');
            return j;
        }
        async function ensureSwal() {
            if (window.Swal) return;
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        function toast(icon, title) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 1700,
                showConfirmButton: false,
                icon,
                title
            });
        }

        async function apiDeleteLancamento(id) {
            const csrf =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="csrf_token"]')?.value ||
                '';

            const commonHeaders = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? {
                    'X-CSRF-Token': csrf
                } : {}),
            };

            // Use sempre a BASE jÃ¡ calculada no topo do arquivo
            const tries = [{
                    url: `${BASE}api/lancamentos/${id}`,
                    opt: {
                        method: 'DELETE'
                    }
                },
                {
                    url: `${BASE}index.php/api/lancamentos/${id}`,
                    opt: {
                        method: 'DELETE'
                    }
                },

                // (opcionais) fallbacks por POST caso vocÃª decida expor uma rota alternativa sem DELETE
                {
                    url: `${BASE}api/lancamentos/${id}/delete`,
                    opt: {
                        method: 'POST'
                    }
                },
                {
                    url: `${BASE}index.php/api/lancamentos/${id}/delete`,
                    opt: {
                        method: 'POST'
                    }
                },
                {
                    url: `${BASE}api/lancamentos/delete`,
                    opt: {
                        method: 'POST',
                        body: JSON.stringify({
                            id
                        })
                    }
                },
                {
                    url: `${BASE}index.php/api/lancamentos/delete`,
                    opt: {
                        method: 'POST',
                        body: JSON.stringify({
                            id
                        })
                    }
                },
            ];

            for (const t of tries) {
                try {
                    const r = await fetch(t.url, {
                        credentials: 'include',
                        headers: commonHeaders,
                        ...t.opt
                    });
                    if (r.ok) return await r.json();
                    // se nÃ£o for 404, jÃ¡ mostre o erro da API
                    if (r.status !== 404) {
                        const j = await r.json().catch(() => ({}));
                        throw new Error(j?.message || `HTTP ${r.status}`);
                    }
                } catch (_) {}
            }
            throw new Error('Endpoint de exclusÃ£o nÃ£o encontrado. Verifique as rotas.');
        }

        const apiMetrics = m => getJSON(`${BASE}api/dashboard/metrics?month=${encodeURIComponent(m)}`);
        const apiAccountsBalances = (m) =>
            getJSON(`${BASE}api/accounts?with_balances=1&month=${encodeURIComponent(m)}&only_active=1`);

        async function apiTransactionsSmart(m, l = 5) {
            const urlLanc = `${BASE}api/lancamentos?month=${encodeURIComponent(m)}&limit=${l}`;
            try {
                const data = await getJSON(urlLanc);
                if (Array.isArray(data)) return data;
                return data?.items || data?.data || data?.lancamentos || [];
            } catch {
                const urlDash = `${BASE}api/dashboard/transactions?month=${encodeURIComponent(m)}&limit=${l}`;
                return await getJSON(urlDash);
            }
        }

        const STORAGE_KEY = 'lukrato.month.dashboard';
        const $label = $('#currentMonthText');
        const btnOpen = $('#monthDropdownBtn');
        const modalEl = $('#monthModal');
        const mpYearLabel = $('#mpYearLabel');
        const mpPrevYear = $('#mpPrevYear');
        const mpNextYear = $('#mpNextYear');
        const mpGrid = $('#mpGrid');
        const mpTodayBtn = $('#mpTodayBtn');
        const mpInput = $('#mpInputMonth');
        const MONTH_NAMES_SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        let currentMonth = (() => {
            try {
                return (window.LukratoHeader?.getMonth?.()) || sessionStorage.getItem(STORAGE_KEY) || new Date()
                    .toISOString().slice(0, 7);
            } catch {
                return new Date().toISOString().slice(0, 7);
            }
        })();

        let modalYear = (() => {
            try {
                return Number(currentMonth.split('-')[0]) || new Date().getFullYear();
            } catch {
                return new Date().getFullYear();
            }
        })();

        function yymm(d) {
            return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        }

        function makeMonthValue(y, i) {
            return `${y}-${String(i+1).padStart(2,'0')}`;
        }
        const monthLabel = m => {
            try {
                const [y, mm] = String(m || '').split('-').map(Number);
                return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                    month: 'long',
                    year: 'numeric'
                });
            } catch {
                return '-';
            }
        };
        const addMonths = (m, d) => {
            try {
                const [y, mm] = m.split('-').map(Number);
                const dt = new Date(y, mm - 1 + d, 1);
                return `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}`;
            } catch {
                return m;
            }
        };

        function writeLabel() {
            if ($label) $label.textContent = monthLabel(currentMonth);
        }

        function setLocalMonth(m, {
            emit = true
        } = {}) {
            if (!/^\d{4}-\d{2}$/.test(m)) return;
            currentMonth = m;
            try {
                sessionStorage.setItem(STORAGE_KEY, m);
            } catch {}
            writeLabel();
            if (emit) {
                try {
                    document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                        detail: {
                            month: m
                        }
                    }));
                } catch {}
            }
        }

        function buildGrid() {
            if (!mpYearLabel || !mpGrid) return;
            mpYearLabel.textContent = modalYear;
            let html = '';
            for (let i = 0; i < 12; i++) {
                const val = makeMonthValue(modalYear, i);
                const isCurrent = val === currentMonth;
                html += `<div class="col-4">
        <button type="button" class="mp-month btn w-100 py-3 ${isCurrent?'btn-warning text-dark fw-bold':'btn-outline-light'}" data-val="${val}">
          ${MONTH_NAMES_SHORT[i]}
        </button>
      </div>`;
            }
            mpGrid.innerHTML = html;
            mpGrid.querySelectorAll('.mp-month').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const v = btn.getAttribute('data-val');
                    if (!v) return;
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                        }
                    } catch {}
                    setLocalMonth(v);
                    await renderAll();
                });
            });
        }

        mpPrevYear?.addEventListener('click', e => {
            e.preventDefault();
            modalYear--;
            buildGrid();
        });
        mpNextYear?.addEventListener('click', e => {
            e.preventDefault();
            modalYear++;
            buildGrid();
        });
        mpTodayBtn?.addEventListener('click', e => {
            e.preventDefault();
            const now = new Date();
            const today = yymm(new Date(now.getFullYear(), now.getMonth(), 1));
            try {
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
            } catch {}
            setLocalMonth(today);
            renderAll();
        });
        mpInput?.addEventListener('change', async e => {
            const v = String(e.target.value || '');
            if (!/^\d{4}-\d{2}$/.test(v)) return;
            try {
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
            } catch {}
            setLocalMonth(v);
            await renderAll();
        });
        btnOpen?.addEventListener('click', e => {
            e.preventDefault();
            try {
                modalYear = Number((currentMonth || '').split('-')[0]) || new Date().getFullYear();
                buildGrid();
                if (mpInput) mpInput.value = currentMonth;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl)?.show();
                }
            } catch (err) {
                console.error(err);
            }
        });

        document.getElementById('prevMonth')?.addEventListener('click', async e => {
            e.preventDefault();
            setLocalMonth(addMonths(currentMonth, -1));
            await renderAll();
        });
        document.getElementById('nextMonth')?.addEventListener('click', async e => {
            e.preventDefault();
            setLocalMonth(addMonths(currentMonth, +1));
            await renderAll();
        });
        document.addEventListener('lukrato:month-changed', async e => {
            try {
                const m = e.detail?.month;
                if (!m || m === currentMonth) return;
                currentMonth = m;
                writeLabel();
                await renderAll();
            } catch (err) {
                console.error(err);
            }
        });

        /* ============ Renderizadores ============ */
        async function renderKPIs() {
            try {
                // busca mÃ©tricas + saldos das contas em paralelo
                const [k, contas] = await Promise.all([
                    apiMetrics(currentMonth),
                    apiAccountsBalances(currentMonth)
                ]);

                // preenche os demais KPIs com o que vier das mÃ©tricas
                const map = {
                    receitasValue: 'receitas',
                    despesasValue: 'despesas',
                    totalReceitas: 'receitas',
                    totalDespesas: 'despesas',
                    resultadoMes: 'resultado',
                    saldoAcumulado: 'saldoAcumulado'
                };
                Object.entries(map).forEach(([id, key]) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = money(k[key] || 0);
                });

                // **Saldo Atual**: soma de todas as contas (saldoAtual se existir, senÃ£o saldoInicial)
                const totalSaldo = (Array.isArray(contas) ? contas : []).reduce((sum, c) => {
                    const v = (typeof c.saldoAtual === 'number') ? c.saldoAtual : (c.saldoInicial || 0);
                    return sum + (isFinite(v) ? v : 0);
                }, 0);

                const saldoEl = document.getElementById('saldoValue');
                if (saldoEl) saldoEl.textContent = money(totalSaldo);

            } catch (e) {
                console.error('KPIs:', e);
                ['saldoValue', 'receitasValue', 'despesasValue', 'totalReceitas', 'totalDespesas', 'resultadoMes',
                    'saldoAcumulado'
                ]
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = 'R$ 0,00';
                });
            }
        }


        // aceita string ("Sicredi") OU objetos com instituicao/nome, e formata transferÃªncia "origem â†’ destino"
        function getContaLabel(t) {
            // se vier string pronta
            if (typeof t.conta === 'string' && t.conta.trim()) return t.conta.trim();
            // preferir instituiÃ§Ã£o, depois nome
            const origem = t.conta_instituicao ?? t.conta_nome ?? t.conta?.instituicao ?? t.conta?.nome ?? null;
            const destino = t.conta_destino_instituicao ?? t.conta_destino_nome ?? t.conta_destino?.instituicao ?? t
                .conta_destino?.nome ?? null;
            if (t.eh_transferencia && (origem || destino)) return `${origem || '-'} -’ ${destino || '-”'}`;
            // rÃ³tulo pronto do backend, se existir
            if (t.conta_label && String(t.conta_label).trim()) return String(t.conta_label).trim();
            return origem || '-';
        }

        async function renderTable() {
            const tbody = document.querySelector('#transactionsTableBody');
            const empty = document.querySelector('#emptyState');
            const table = document.querySelector('#transactionsTable');


            try {


                const list = await apiTransactionsSmart(currentMonth, 5);
                tbody.innerHTML = '';

                const hasData = Array.isArray(list) && list.length > 0;
                empty.style.display = hasData ? 'none' : 'block';
                if (table) table.style.display = hasData ? 'table' : 'none';

                if (hasData) {
                    list.forEach(t => {
                        // dentro de renderTable(), onde vocÃª monta cada linha
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', t.id); // <-- ADICIONE ESTA LINHA
                        const tipo = String(t.tipo || '').toLowerCase();
                        const color = (tipo === 'receita') ? 'var(--verde, #27ae60)' :
                            (tipo.startsWith('despesa') ? 'var(--vermelho, #e74c3c)' :
                                'var(--laranja, #f39c12)');
                        const categoriaTxt = t.categoria_nome ?? (typeof t.categoria === 'string' ? t
                            .categoria : t.categoria?.nome) ?? '-”';
                        const contaTxt = getContaLabel(t);

                        tr.innerHTML = `
  <td data-label="Data">${dateBR(t.data)}</td>
  <td data-label="Tipo">${(String(t.tipo||'').replace(/_/g,' ') || '--')}</td>
  <td data-label="Categoria">${categoriaTxt}</td>
  <td data-label="Conta">${contaTxt}</td>
  <td data-label="Descricao">${t.descricao || t.observacao || '--'}</td>
  <td data-label="Valor" style="font-weight:700;color:${color}">${money(Number(t.valor)||0)}</td>
  <td data-label="Acoes" class="text-end">
    <button type="button" class="lk-btn danger btn-del" data-id="${t.id}" title="Excluir" aria-label="Excluir lancamento">
      <i class="fas fa-trash"></i>
    </button>
  </td>`;
                        tbody.appendChild(tr);

                    });
                }
            } catch (e) {
                console.error('Tabela:', e);
                empty.style.display = 'block';
                if (table) table.style.display = 'none';
            }
        }

        // Clique no botÃ£o excluir dentro da tabela de "Ãšltimos LanÃ§amentos" (Dashboard)
        document.getElementById('transactionsTableBody')?.addEventListener('click', async (e) => {
            const btn = e.target.closest?.('.btn-del');
            if (!btn) return;

            const tr = e.target.closest('tr');
            const id = btn.getAttribute('data-id') || btn.closest('tr')?.getAttribute('data-id');
            if (!id) return;

            try {
                await ensureSwal();

                const confirm = await Swal.fire({
                    title: 'Excluir lanÃ§amento?',
                    text: 'Essa ação não pode ser desfeita.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    focusCancel: true
                });
                if (!confirm.isConfirmed) return;

                Swal.fire({
                    title: 'Excluindo...',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });

                await apiDeleteLancamento(Number(id));

                Swal.close();
                toast('success', 'Lançamento excluído');

                // remove a linha imediatamente para dar feedbackâ€¦
                tr.remove();

                // â€¦e atualiza os cards/ grÃ¡fico/ tabela
                await window.refreshDashboard?.();
                // avisa outras telas (como /lancamentos) que houve mudanÃ§a
                document.dispatchEvent(new CustomEvent('lukrato:data-changed'));
            } catch (err) {
                console.error(err);
                await ensureSwal();
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: (err && err.message) || 'Falha ao excluir'
                });
            }
        });


        let chartInstance = null;
        async function drawChart() {
            const canvas = document.getElementById('evolutionChart');
            if (!canvas || typeof Chart === 'undefined') return;
            try {
                const months = Array.from({
                    length: 6
                }, (_, i) => {
                    const [y, m] = currentMonth.split('-').map(Number);
                    const d = new Date(y, m - 1 - (5 - i), 1);
                    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
                });
                const labels = months.map(m => {
                    try {
                        const [yy, mm] = m.split('-').map(Number);
                        return new Date(yy, mm - 1, 1).toLocaleDateString('pt-BR', {
                            month: 'short'
                        });
                    } catch {
                        return 'N/A';
                    }
                });
                const results = await Promise.allSettled(months.map(m => apiMetrics(m)));
                const series = results.map(r => r.status === 'fulfilled' ? Number(r.value?.resultado || 0) : 0);
                const ctx = canvas.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 300);
                grad.addColorStop(0, 'rgba(230,126,34,0.35)');
                grad.addColorStop(1, 'rgba(230,126,34,0.05)');
                const data = {
                    labels,
                    datasets: [{
                        label: 'Resultado do mês',
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
                        data,
                        options
                    });
                }
            } catch (e) {
                console.error('Gráfico:', e);
            }
        }

        async function renderAll() {
            writeLabel();
            await Promise.allSettled([renderKPIs(), renderTable(), drawChart()]);
        }
        window.refreshDashboard = renderAll;

        document.addEventListener('lukrato:data-changed', async () => {
            try {
                await renderAll();
            } catch (err) {
                console.error('Dashboard refresh falhou:', err);
            }
        });

        /* Boot */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', async () => {
                writeLabel();
                await renderAll();
            });
        } else {
            setTimeout(async () => {
                writeLabel();
                await renderAll();
            }, 100);
        }
    })();
</script>