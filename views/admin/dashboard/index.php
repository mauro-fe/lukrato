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
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>
    <section class="kpi-grid" role="region" aria-label="Indicadores principais">
        <div data-aos="flip-left">
            <div class="card kpi-card" id="saldoCard">
                <div class="card-header">
                    <div class="kpi-icon saldo"><i class="fas fa-wallet"></i></div><span class="kpi-title">Saldo
                        Atual</span>
                </div>
                <div class="kpi-value" id="saldoValue">R$ 0,00</div>
            </div>
        </div>
        <div data-aos="flip-left">
            <div class="card kpi-card" id="receitasCard">
                <div class="card-header">
                    <div class="kpi-icon receitas"><i class="fas fa-arrow-up"></i></div><span class="kpi-title">Receitas
                        do Mês</span>
                </div>
                <div class="kpi-value receitas" id="receitasValue">R$ 0,00</div>
            </div>
        </div>
        <div data-aos="flip-right">
            <div class="card kpi-card" id="despesasCard">
                <div class="card-header">
                    <div class="kpi-icon despesas"><i class="fas fa-arrow-down"></i></div><span
                        class="kpi-title">Despesas do Mês</span>
                </div>
                <div class="kpi-value despesas" id="despesasValue">R$ 0,00</div>
            </div>
        </div>
        <div data-aos="flip-right">
            <div class="card kpi-card" id="saldoMesCard" data-aos="fade-up-left">
                <div class="card-header">
                    <div class="kpi-icon saldo"><i class="fas fa-balance-scale"></i></div><span class="kpi-title">Saldo
                        do Mês</span>
                </div>
                <div class="kpi-value" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <section class="charts-grid" data-aos="zoom-in">
        <div class="card chart-card">
            <div class="card-header">
                <h2 class="card-title">Evolução Financeira</h2>
            </div>
            <div class="chart-container"><canvas id="evolutionChart" role="img"
                    aria-label="Gráfico de evolução do saldo"></canvas></div>
        </div>

        <!-- <div class="card summary-card">
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
                </div> -->
    </section>

    <section class="card table-card mb-5" data-aos="fade-up">
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
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<?php if (function_exists('loadPageJs')) loadPageJs(); ?>
<script>
    (() => {
        'use strict';

        /* ==========================================
           CONFIGURAÇÕES BÁSICAS
        ========================================== */
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

        /* ==========================================
           API HELPERS
        ========================================== */
        async function apiDeleteLancamento(id) {
            const csrf =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="csrf_token"]')?.value ||
                '';

            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? {
                    'X-CSRF-Token': csrf
                } : {}),
            };

            const tries = [{
                    url: `${BASE}api/lancamentos/${id}`,
                    method: 'DELETE'
                },
                {
                    url: `${BASE}index.php/api/lancamentos/${id}`,
                    method: 'DELETE'
                },
                {
                    url: `${BASE}api/lancamentos/${id}/delete`,
                    method: 'POST'
                },
                {
                    url: `${BASE}index.php/api/lancamentos/${id}/delete`,
                    method: 'POST'
                },
                {
                    url: `${BASE}api/lancamentos/delete`,
                    method: 'POST',
                    body: JSON.stringify({
                        id
                    })
                },
                {
                    url: `${BASE}index.php/api/lancamentos/delete`,
                    method: 'POST',
                    body: JSON.stringify({
                        id
                    })
                },
            ];

            for (const t of tries) {
                try {
                    const r = await fetch(t.url, {
                        credentials: 'include',
                        headers,
                        method: t.method,
                        body: t.body
                    });
                    if (r.ok) return await r.json();
                    if (r.status !== 404) {
                        const j = await r.json().catch(() => ({}));
                        throw new Error(j?.message || `HTTP ${r.status}`);
                    }
                } catch (_) {}
            }
            throw new Error('Endpoint de exclusão não encontrado.');
        }

        const apiMetrics = m => getJSON(`${BASE}api/dashboard/metrics?month=${encodeURIComponent(m)}`);
        const apiAccountsBalances = m => getJSON(
            `${BASE}api/accounts?with_balances=1&month=${encodeURIComponent(m)}&only_active=1`);
        const apiTransactionsSmart = async (m, l = 5) => {
            const url1 = `${BASE}api/lancamentos?month=${encodeURIComponent(m)}&limit=${l}`;
            try {
                const d = await getJSON(url1);
                return Array.isArray(d) ? d : (d.items || d.data || d.lancamentos || []);
            } catch {
                const url2 = `${BASE}api/dashboard/transactions?month=${encodeURIComponent(m)}&limit=${l}`;
                return await getJSON(url2);
            }
        };

        /* ==========================================
           RENDERIZAÇÕES
        ========================================== */
        const $label = $('#currentMonthText');

        function writeLabel(m) {
            if (!$label) return;
            try {
                const [y, mm] = String(m).split('-').map(Number);
                const label = new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                    month: 'long',
                    year: 'numeric'
                });
                $label.textContent = label;
            } catch {
                $label.textContent = '-';
            }
        }

        async function renderKPIs(month) {
            try {
                const [k, contas] = await Promise.all([
                    apiMetrics(month),
                    apiAccountsBalances(month)
                ]);

                const map = {
                    receitasValue: 'receitas',
                    despesasValue: 'despesas',
                    totalReceitas: 'receitas',
                    totalDespesas: 'despesas',
                    resultadoMes: 'resultado',
                    saldoMesValue: 'resultado',
                    saldoAcumulado: 'saldoAcumulado'
                };
                Object.entries(map).forEach(([id, key]) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = money(k[key] || 0);
                });

                const totalSaldo = (Array.isArray(contas) ? contas : []).reduce((sum, c) => {
                    const v = (typeof c.saldoAtual === 'number') ? c.saldoAtual : (c.saldoInicial || 0);
                    return sum + (isFinite(v) ? v : 0);
                }, 0);

                const saldoEl = document.getElementById('saldoValue');
                if (saldoEl) saldoEl.textContent = money(totalSaldo);
            } catch (e) {
                console.error('KPIs:', e);
                ['saldoValue', 'receitasValue', 'despesasValue', 'saldoMesValue', 'totalReceitas', 'totalDespesas',
                    'resultadoMes', 'saldoAcumulado'
                ]
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = 'R$ 0,00';
                });
            }
        }

        function getContaLabel(t) {
            if (typeof t.conta === 'string' && t.conta.trim()) return t.conta.trim();
            const origem = t.conta_instituicao ?? t.conta_nome ?? t.conta?.instituicao ?? t.conta?.nome ?? null;
            const destino = t.conta_destino_instituicao ?? t.conta_destino_nome ?? t.conta_destino?.instituicao ?? t
                .conta_destino?.nome ?? null;
            if (t.eh_transferencia && (origem || destino)) return `${origem || '-'} → ${destino || '-'}`;
            if (t.conta_label && String(t.conta_label).trim()) return String(t.conta_label).trim();
            return origem || '-';
        }

        async function renderTable(month) {
            const tbody = document.querySelector('#transactionsTableBody');
            const empty = document.querySelector('#emptyState');
            const table = document.querySelector('#transactionsTable');

            try {
                const list = await apiTransactionsSmart(month, 5);
                tbody.innerHTML = '';

                const hasData = Array.isArray(list) && list.length > 0;
                empty.style.display = hasData ? 'none' : 'block';
                if (table) table.style.display = hasData ? 'table' : 'none';

                if (hasData) {
                    list.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', t.id);
                        const tipo = String(t.tipo || '').toLowerCase();
                        const color = (tipo === 'receita') ? '#27ae60' :
                            (tipo.startsWith('despesa') ? '#e74c3c' : '#f39c12');
                        const categoriaTxt = t.categoria_nome ?? (typeof t.categoria === 'string' ? t
                            .categoria : t.categoria?.nome) ?? '-';
                        const contaTxt = getContaLabel(t);
                        tr.innerHTML = `
                        <td>${dateBR(t.data)}</td>
                        <td>${String(t.tipo||'').replace(/_/g,' ')}</td>
                        <td>${categoriaTxt}</td>
                        <td>${contaTxt}</td>
                        <td>${t.descricao || t.observacao || '--'}</td>
                        <td style="font-weight:700;color:${color}">${money(Number(t.valor)||0)}</td>
                        <td class="text-end">
                            <button class="lk-btn danger btn-del" data-id="${t.id}">
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

        // exclusão rápida na tabela
        document.getElementById('transactionsTableBody')?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-del');
            if (!btn) return;
            const tr = e.target.closest('tr');
            const id = btn.getAttribute('data-id');
            if (!id) return;
            try {
                await ensureSwal();
                const confirm = await Swal.fire({
                    title: 'Excluir lançamento?',
                    text: 'Essa ação não pode ser desfeita.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar'
                });
                if (!confirm.isConfirmed) return;
                Swal.fire({
                    title: 'Excluindo...',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });
                await apiDeleteLancamento(Number(id));
                Swal.close();
                toast('success', 'Lançamento excluído');
                tr.remove();
                await window.refreshDashboard?.();
                document.dispatchEvent(new CustomEvent('lukrato:data-changed'));
            } catch (err) {
                console.error(err);
                await ensureSwal();
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: err.message || 'Falha ao excluir'
                });
            }
        });

        /* ==========================================
           CHART
        ========================================== */
        let chartInstance = null;
        async function drawChart(month) {
            const canvas = document.getElementById('evolutionChart');
            if (!canvas || typeof Chart === 'undefined') return;
            try {
                const months = Array.from({
                    length: 6
                }, (_, i) => {
                    const [y, m] = month.split('-').map(Number);
                    const d = new Date(y, m - 1 - (5 - i), 1);
                    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
                });
                const labels = months.map(m => new Date(m).toLocaleDateString('pt-BR', {
                    month: 'short'
                }));
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
                        fill: true,
                        tension: .35
                    }]
                };
                const options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: v => money(v)
                            }
                        }
                    }
                };
                if (chartInstance) {
                    chartInstance.data = data;
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

        /* ==========================================
           REFRESH PRINCIPAL
        ========================================== */
        async function renderAll() {
            const month = window.LukratoHeader?.getMonth?.() || new Date().toISOString().slice(0, 7);
            writeLabel(month);
            await Promise.allSettled([renderKPIs(month), renderTable(month), drawChart(month)]);
        }
        window.refreshDashboard = renderAll;

        document.addEventListener('lukrato:data-changed', renderAll);
        document.addEventListener('lukrato:month-changed', renderAll);

        if (document.readyState === 'loading')
            document.addEventListener('DOMContentLoaded', renderAll);
        else
            renderAll();
    })();
</script>