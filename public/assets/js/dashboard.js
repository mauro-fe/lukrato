/* =========================================================
 * Lukrato - Dashboard (somente lógica da página)
 * Depende do header-controller já carregado (window.LukratoHeader)
 * ======================================================= */
(() => {
    // ------- helpers -------
    const BASE_URL = (window.BASE_URL || '/').replace(/\/?$/, '/');
    const API = (p) => `${BASE_URL}api/${p.replace(/^\/+/, '')}`;

    const fmt = {
        money: (n) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(n || 0)),
        date: (iso) => {
            if (!iso) return '—';
            const d = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return d ? `${d[3]}/${d[2]}/${d[1]}` : '—';
        }
    };

    async function get(path) {
        const r = await fetch(API(path), { credentials: 'include' });
        if (!r.ok) throw new Error(`${r.status} ${r.statusText}`);
        return r.json();
    }

    const api = {
        metrics: (month) => get(`dashboard/metrics?month=${encodeURIComponent(month)}`),
        transactions: (month, limit = 50) => get(`dashboard/transactions?month=${encodeURIComponent(month)}&limit=${limit}`),
    };

    // ------- state -------
    let currentMonth = window.LukratoHeader?.getMonth?.() || new Date().toISOString().slice(0, 7);
    let chartInstance = null;

    // ------- chart -------
    async function monthlySeries(refMonth, count = 6) {
        const clamp = window.LukratoHeader?.clampMonth ?? ((m, d) => {
            const [y, mm] = m.split('-').map(Number);
            return new Date(y, mm - 1 + d, 1).toISOString().slice(0, 7);
        });

        const labelFn = window.LukratoHeader?.monthLabel ?? (m => m);

        const months = Array.from({ length: count }, (_, i) => {
            const off = i - (count - 1);
            return clamp(refMonth, off);
        });

        const labels = months.map(labelFn);
        const results = await Promise.all(months.map(m => api.metrics(m).catch(() => ({ resultado: 0 }))));
        const series = results.map(x => Number(x.resultado || 0));

        return { labels, series };
    }

    async function drawChart() {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const { labels, series } = await monthlySeries(currentMonth, 6);

        if (!chartInstance) {
            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(230,126,34,0.35)');
            grad.addColorStop(1, 'rgba(230,126,34,0.05)');

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
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2C3E50',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            displayColors: false,
                            callbacks: { label: (c) => fmt.money(c.parsed.y) }
                        }
                    },
                    scales: {
                        x: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3' } },
                        y: { grid: { color: 'rgba(189,195,199,0.16)' }, ticks: { color: '#cfd8e3', callback: v => fmt.money(v) } }
                    }
                }
            });
        } else {
            chartInstance.data.labels = labels;
            chartInstance.data.datasets[0].data = series;
            chartInstance.update();
        }
    }

    // ------- KPIs -------
    async function renderKPIs() {
        try {
            const k = await api.metrics(currentMonth);
            const map = {
                saldoValue: 'saldo',
                receitasValue: 'receitas',
                despesasValue: 'despesas',
                totalReceitas: 'receitas',
                totalDespesas: 'despesas',
                resultadoMes: 'resultado',
                saldoAcumulado: 'saldoAcumulado'
            };
            Object.entries(map).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el) el.textContent = fmt.money(k[key] || 0);
            });
        } catch (e) {
            console.error(e);
            window.Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar KPIs' });
        }
    }

    // ------- tabela -------
    async function renderTable() {
        const tbody = document.getElementById('transactionsTableBody');
        const empty = document.getElementById('emptyState');
        if (!tbody || !empty) return;

        try {
            const list = await api.transactions(currentMonth, 50);
            tbody.innerHTML = '';
            empty.style.display = list.length ? 'none' : 'block';

            list.forEach(t => {
                const tr = document.createElement('tr');
                const color = t.tipo === 'receita' ? 'var(--verde)'
                    : String(t.tipo || '').startsWith('despesa') ? 'var(--vermelho)'
                        : 'var(--laranja)';
                tr.innerHTML = `
          <td>${fmt.date(t.data)}</td>
          <td>${String(t.tipo || '').replace('_', ' ')}</td>
          <td>${t.categoria?.nome || '—'}</td>
          <td>—</td>
          <td>${t.descricao || t.observacao || '—'}</td>
          <td style="font-weight:700;text-align:right;color:${color}">${fmt.money(Number(t.valor) || 0)}</td>
        `;
                tbody.appendChild(tr);
            });
        } catch (e) {
            console.error(e);
            window.Swal?.fire?.({ icon: 'error', title: 'Erro ao carregar lançamentos' });
        }
    }

    // ------- export (chamado quando o header emite o evento) -------
    async function exportCSV(month) {
        try {
            const list = await api.transactions(month, 1000);
            const head = ['Data', 'Tipo', 'Categoria', 'Conta/Cartão', 'Descrição', 'Valor'];
            const rows = list.map(t => [
                fmt.date(t.data),
                t.tipo || '',
                t.categoria?.nome || '',
                '',
                String(t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
                (Number(t.valor) || 0).toFixed(2).replace('.', ',')
            ].join(';'));
            const csv = [head.join(';'), ...rows].join('\r\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = Object.assign(document.createElement('a'), { href: url, download: `lukrato-${month}.csv` });
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
        } catch (e) {
            console.error(e);
            window.Swal?.fire?.({ icon: 'error', title: 'Erro ao exportar CSV' });
        }
    }

    // ------- init -------
    async function renderAll() {
        await Promise.all([renderKPIs(), renderTable(), drawChart()]);
    }

    document.addEventListener('DOMContentLoaded', async () => {
        // render inicial
        await renderAll();

        // quando o mês do header mudar, recarrega
        document.addEventListener('lukrato:month-changed', async (e) => {
            currentMonth = e.detail?.month || currentMonth;
            await renderAll();
        });

        // quando o header pedir exportar, gera CSV desta página
        document.addEventListener('lukrato:export-click', (e) => {
            exportCSV(e.detail?.month || currentMonth);
        });

        // se algum modal global salvar algo e emitir esse evento, atualiza
        document.addEventListener('lukrato:data-changed', renderAll);
    });
})();
