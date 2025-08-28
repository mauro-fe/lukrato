/* Página: Lançamentos */
(() => {
    const BASE_URL = (window.BASE_URL || '/').replace(/\/?$/, '/');
    const API = (p) => `${BASE_URL}api/${p.replace(/^\/+/, '')}`;

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbodyLancamentos');
    const form = $('#formFiltros');
    const fMes = $('#filtroMes');
    const fTipo = $('#filtroTipo');

    const fmt = {
        money: (n) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(n || 0)),
        date: (iso) => {
            if (!iso) return '—';
            const m = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
        }
    };

    function getMonth() { return window.LukratoHeader?.getMonth?.() || fMes?.value || new Date().toISOString().slice(0, 7); }

    function setEmpty(msg) { if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center">${msg}</td></tr>`; }

    function normalizeList(p) { if (Array.isArray(p)) return p; if (p?.items) return p.items; if (p?.data) return p.data; if (p?.lancamentos) return p.lancamentos; return []; }

    async function fetchList() {
        const month = getMonth();
        const tipo = fTipo?.value || '';
        const q = `month=${encodeURIComponent(month)}&tipo=${encodeURIComponent(tipo)}&limit=200`;

        // tenta alguns endpoints
        const paths = [`lancamentos?${q}`, `transactions?${q}`, `dashboard/transactions?${q}`];
        for (const p of paths) {
            const r = await fetch(API(p), { credentials: 'include' });
            if (r.ok) return normalizeList(await r.json());
            if (r.status !== 404) break;
        }
        return [];
    }

    async function render() {
        if (!tbody) return;
        setEmpty('Carregando…');
        const list = await fetchList();
        if (!list.length) { setEmpty('Sem lançamentos para o período'); return; }
        tbody.innerHTML = list.map(t => `
      <tr>
        <td>${fmt.date(t.data)}</td>
        <td>${t.tipo || '—'}</td>
        <td>${t.categoria_nome || t.categoria?.nome || t.categoria || '—'}</td>
        <td>${t.conta?.nome || '—'}</td>
        <td>${t.descricao || t.observacao || '—'}</td>
        <td class="text-right">${fmt.money(t.valor)}</td>
      </tr>
    `).join('');
    }

    // export acionado pelo header
    async function onExport(month) {
        const list = await fetchList();
        const rows = list.map(t => [
            fmt.date(t.data),
            t.tipo || '',
            t.categoria_nome || t.categoria?.nome || t.categoria || '',
            t.conta?.nome || '',
            String(t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
            (Number(t.valor) || 0).toFixed(2).replace('.', ',')
        ].join(';'));
        const csv = ['Data;Tipo;Categoria;Conta/Cartão;Descrição;Valor', ...rows].join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = Object.assign(document.createElement('a'), { href: url, download: `lukrato-${month}.csv` });
        document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    }

    document.addEventListener('DOMContentLoaded', () => {
        render();
        form?.addEventListener('submit', (e) => { e.preventDefault(); render(); });
        fMes?.addEventListener('change', render);
        fTipo?.addEventListener('change', render);

        // mês mudou no header → recarrega
        document.addEventListener('lukrato:month-changed', render);
        // header pediu exportar → gera CSV desta página
        document.addEventListener('lukrato:export-click', (e) => onExport(e.detail?.month));
        // algum modal salvou algo → recarrega
        document.addEventListener('lukrato:data-changed', render);
    });
})();
