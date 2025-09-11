/* Página: Lançamentos (mês auto via header, botão filtra tipo no cliente) */
(() => {
    const rawBase = (window.BASE_URL || '/').replace(/\/?$/, '/');
    const BASES = [`${rawBase}api/`, `${rawBase}index.php/api/`];

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbodyLancamentos');
    const selectTipo = $('#filtroTipo');
    const btnFiltrar = $('#btnFiltrar');

    // cache simples por mês para não refazer fetch desnecessário
    const monthCache = new Map();
    let isLoading = false;

    const fmt = {
        money: (n) =>
            new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
                .format(Number(n || 0)),
        date: (iso) => {
            if (!iso) return '—';
            const m = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
        },
    };

    /** Resolve o mês atual (YYYY-MM) a partir do header/component/evento */
    const getMonth = () => {
        // 1) Componente do header (se expuser getMonth)
        if (window.LukratoHeader?.getMonth) {
            const m = window.LukratoHeader.getMonth();
            if (m) return m;
        }
        // 2) Texto “currentMonthText” pode ter data em data-* (se você configurou)
        const el = document.getElementById('currentMonthText');
        const dataMonth = el?.getAttribute?.('data-month');
        if (dataMonth) return dataMonth;

        // 3) Fallback: mês atual
        return new Date().toISOString().slice(0, 7);
    };

    const setEmpty = (msg) => {
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">${msg}</td></tr>`;
    };

    const normalizeList = (p) => {
        if (Array.isArray(p)) return p;
        if (p?.items) return p.items;
        if (p?.data) return p.data;
        if (p?.lancamentos) return p.lancamentos;
        return [];
    };

    const normalizeTipo = (v) => {
        if (!v) return '';
        const s = String(v).toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (/(receita|entrada|credit)/.test(s)) return 'receita';
        if (/(despesa|saida|debit)/.test(s)) return 'despesa';
        return '';
    };

    /** Busca por mês no backend (sem tipo) e retorna lista bruta */
    async function tryFetch(pathWithQuery) {
        for (const base of BASES) {
            try {
                const r = await fetch(base + pathWithQuery, { credentials: 'include' });
                if (r.ok) return await r.json();     // 2xx
                if (r.status === 404) continue;      // tenta próxima base
                // outros códigos: para de tentar nesta base e passa para próxima
            } catch (_) { /* ignora e tenta próxima base */ }
        }
        return null;
    }

    async function fetchListByMonth(month) {
        if (monthCache.has(month)) return monthCache.get(month);

        const q = `month=${encodeURIComponent(month)}&limit=500`;
        const candidates = [
            `dashboard/transactions?${q}`,
            `transactions?${q}`,
            `lancamentos?${q}`,
        ];

        for (const c of candidates) {
            const json = await tryFetch(c);
            const list = normalizeList(json);
            if (list.length) {
                monthCache.set(month, list);
                return list;
            }
        }
        // mesmo vazio, cacheia (evita requisições repetidas)
        monthCache.set(month, []);
        return [];
    }

    /** Aplica filtro de tipo no cliente */
    function applyTipoFilter(list, tipoSelecionado) {
        if (!tipoSelecionado) return list;
        const alvo = normalizeTipo(tipoSelecionado);
        if (!alvo) return list;

        return list.filter((t) => {
            const candidatos = [
                t.tipo,
                t.tipo_transacao,
                t.kind,
                t.category_type,
                t.categoria?.tipo,
                t.categoria_tipo
            ];
            for (const c of candidatos) {
                const n = normalizeTipo(c);
                if (n) return n === alvo;
            }
            // fallback: sinal do valor
            const v = Number(t.valor);
            if (!Number.isNaN(v)) {
                return (alvo === 'receita' && v >= 0) || (alvo === 'despesa' && v < 0);
            }
            return true;
        });
    }

    async function getDataForRender() {
        const month = getMonth();
        const lista = await fetchListByMonth(month);
        return { month, lista };
    }

    function setLoading(on) {
        isLoading = on;
        if (btnFiltrar) {
            btnFiltrar.disabled = on;
            btnFiltrar.classList.toggle('is-loading', on);
            btnFiltrar.innerHTML = on
                ? `<i class="fas fa-circle-notch fa-spin"></i> Filtrando…`
                : `<i class="fas fa-filter"></i> Filtrar`;
        }
    }

    async function render({ byButton = false } = {}) {
        if (!tbody || isLoading) return;
        setEmpty('Carregando…');
        setLoading(true);

        try {
            const { month, lista } = await getDataForRender();
            const tipoSelecionado = byButton ? (selectTipo?.value || '') : '';
            const final = applyTipoFilter(lista, tipoSelecionado);

            if (!final.length) {
                setEmpty('Sem lançamentos para o período');
            } else {
                tbody.innerHTML = final.map(t => `
          <tr>
            <td>${fmt.date(t.data)}</td>
            <td>${normalizeTipo(t.tipo) || t.tipo || '—'}</td>
            <td>${t.categoria_nome || t.categoria?.nome || t.categoria || '—'}</td>
            <td>${t.conta?.nome || '—'}</td>
            <td>${t.descricao || t.observacao || '—'}</td>
            <td class="text-right">${fmt.money(t.valor)}</td>
          </tr>
        `).join('');
            }

            // dispara evento útil para quem quiser escutar após render
            document.dispatchEvent(new CustomEvent('lukrato:lancamentos-rendered', {
                detail: { month, total: final.length }
            }));
        } catch (e) {
            console.error(e);
            setEmpty('Erro ao carregar lançamentos');
        } finally {
            setLoading(false);
        }
    }

    async function onExport(monthOverride) {
        const month = monthOverride || getMonth();
        // usa cache/fetch
        const lista = await fetchListByMonth(month);
        // respeita tipo selecionado (independe de ter clicado antes)
        const tipoSelecionado = selectTipo?.value || '';
        const final = applyTipoFilter(lista, tipoSelecionado);

        const rows = final.map(t => [
            fmt.date(t.data),
            (normalizeTipo(t.tipo) || t.tipo || ''),
            (t.categoria_nome || t.categoria?.nome || t.categoria || ''),
            (t.conta?.nome || ''),
            String(t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
            (Number(t.valor) || 0).toFixed(2).replace('.', ',')
        ].join(';'));

        const csv = ['Data;Tipo;Categoria;Conta/Cartão;Descrição;Valor', ...rows].join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = Object.assign(document.createElement('a'), { href: url, download: `lukrato-${month}.csv` });
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // 1) Render inicial (sem tipo)
        render();

        // 2) Botão aplica APENAS o tipo
        btnFiltrar?.addEventListener('click', () => render({ byButton: true }));

        // 3) Eventos globais do header:
        //    - quando o mês muda via setas/dropdown, o header deve emitir este evento
        document.addEventListener('lukrato:month-changed', (e) => {
            // se o header enviar detail.month, atualiza um data-attr (ajuda o getMonth)
            const m = e?.detail?.month;
            const el = document.getElementById('currentMonthText');
            if (m && el) el.setAttribute('data-month', m);
            // limpe cache se quiser sempre refazer busca para novos meses
            // monthCache.delete(m); // opcional
            render(); // mês auto, sem tipo
        });

        // exportar
        document.addEventListener('lukrato:export-click', (e) => onExport(e.detail?.month));

        // data-changed (ex.: criou/alterou/apagou lançamento)
        document.addEventListener('lukrato:data-changed', () => {
            // invalida cache do mês atual para refletir alterações
            const m = getMonth();
            monthCache.delete(m);
            render();
        });
    });
})();
