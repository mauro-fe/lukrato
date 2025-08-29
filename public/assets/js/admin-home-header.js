(() => {
    const BASE = (window.BASE_URL || '/').replace(/\/?$/, '/');

    // ------------ Utils ------------
    const $ = (s, sc = document) => sc.querySelector(s);
    const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));
    const isReportsPage = () => location.pathname.replace(/\/+$/, '').endsWith('/relatorios');

    // "2025-08" -> {year:2025, month:8}
    function ym(mStr) {
        const [y, m] = mStr.split('-').map(Number);
        return { year: y, month: m };
    }

    // Converte "R$ 1.234,56" ou "1234,56" para 1234.56
    function parseMoney(input) {
        if (input == null) return 0;
        let s = String(input).trim();
        s = s.replace(/[R$\s]/g, '');     // tira R$ e espaços
        if (s.includes(',')) {
            s = s.replace(/\./g, '').replace(',', '.');
        }
        const n = Number(s);
        return isNaN(n) ? 0 : Number(n.toFixed(2));
    }

    // Formata BRL p/ UI
    const fmtBRL = n => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(n || 0));

    // Mascara simples BRL on blur (opcional)
    function bindMoneyMask() {
        $$('.money-mask').forEach(inp => {
            inp.addEventListener('blur', () => {
                const v = parseMoney(inp.value);
                inp.value = fmtBRL(v);
            });
        });
    }

    // ------------ API Wrappers ------------
    async function apiOptions() {
        const r = await fetch(`${BASE}api/options`, { credentials: 'include' });
        if (!r.ok) throw new Error('Falha ao buscar opções');
        return r.json();
    }

    async function apiCreateTransaction(payload) {
        const r = await fetch(`${BASE}api/transactions`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok || json?.status === 'error') {
            throw new Error(json?.message || 'Não foi possível salvar.');
        }
        return json;
    }

    async function apiReport(type, monthStr) {
        const { year, month } = ym(monthStr);
        const url = new URL(`${BASE}api/reports`);
        url.searchParams.set('type', type);
        url.searchParams.set('year', String(year));
        url.searchParams.set('month', String(month)); // 1..12
        const r = await fetch(url, { credentials: 'include' });
        if (!r.ok) throw new Error(`Falha no relatório: ${type}`);
        return r.json();
    }

    // ------------ Categorias nos modais ------------
    async function loadCategoriasFor(selectEl, tipo) {
        try {
            const opt = await apiOptions();
            const list = tipo === 'receita' ? (opt?.categorias?.receitas || []) : (opt?.categorias?.despesas || []);
            selectEl.innerHTML = `<option value="">Selecione uma categoria</option>`;
            list.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id; o.textContent = c.nome;
                selectEl.appendChild(o);
            });
        } catch (e) {
            console.error(e);
            if (window.Swal) Swal.fire('Erro', 'Não foi possível carregar as categorias.', 'error');
        }
    }

    // ------------ Salvar Receita / Despesa ------------
    async function submitReceita(e) {
        e?.preventDefault?.();
        const data = $('#receitaData')?.value;
        const categoria = $('#receitaCategoria')?.value || null;
        const descricao = $('#receitaDescricao')?.value || '';
        const observacao = $('#receitaObservacao')?.value || '';
        const valorStr = $('#receitaValor')?.value || '';
        const valor = parseMoney(valorStr);

        if (!data || valor < 0) {
            if (window.Swal) Swal.fire('Atenção', 'Preencha data e valor.', 'warning');
            return;
        }

        await apiCreateTransaction({ tipo: 'receita', data, valor, categoria_id: categoria ? Number(categoria) : null, descricao, observacao });

        if (window.Swal) Swal.fire('Sucesso', 'Receita salva!', 'success');
        toggleModal('modalReceita', false);
        if (window.refreshDashboard) window.refreshDashboard();
        if (window.refreshReports) window.refreshReports();
    }

    async function submitDespesa(e) {
        e?.preventDefault?.();
        const data = $('#despesaData')?.value;
        const categoria = $('#despesaCategoria')?.value || null;
        const descricao = $('#despesaDescricao')?.value || '';
        const observacao = $('#despesaObservacao')?.value || '';
        const valorStr = $('#despesaValor')?.value || '';
        const valor = parseMoney(valorStr);

        if (!data || valor < 0) {
            if (window.Swal) Swal.fire('Atenção', 'Preencha data e valor.', 'warning');
            return;
        }

        await apiCreateTransaction({ tipo: 'despesa', data, valor, categoria_id: categoria ? Number(categoria) : null, descricao, observacao });

        if (window.Swal) Swal.fire('Sucesso', 'Despesa salva!', 'success');
        toggleModal('modalDespesa', false);
        if (window.refreshDashboard) window.refreshDashboard();
        if (window.refreshReports) window.refreshReports();
    }

    // ------------ Bind nos modais já existentes ------------
    function bindModalsIntegracao() {
        // Carrega categorias ao abrir cada modal
        document.addEventListener('lukrato:open-modal', (e) => {
            const key = String(e.detail?.key || '').toLowerCase();
            if (key === 'receita') {
                $('#receitaData') && ($('#receitaData').value = new Date().toISOString().slice(0, 10));
                loadCategoriasFor($('#receitaCategoria'), 'receita');
            }
            if (key === 'despesa') {
                $('#despesaData') && ($('#despesaData').value = new Date().toISOString().slice(0, 10));
                loadCategoriasFor($('#despesaCategoria'), 'despesa');
            }
            // (despesa-cartao / transferencia podem ser integrados depois)
        });

        // Submits
        $('#formReceita')?.addEventListener('submit', (ev) => submitReceita(ev).catch(err => {
            console.error(err);
            if (window.Swal) Swal.fire('Erro', err.message || 'Falha ao salvar.', 'error');
        }));

        $('#formDespesa')?.addEventListener('submit', (ev) => submitDespesa(ev).catch(err => {
            console.error(err);
            if (window.Swal) Swal.fire('Erro', err.message || 'Falha ao salvar.', 'error');
        }));

        // Máscara simples de dinheiro
        bindMoneyMask();
    }

    // ------------ Refresh Reports (chama seu ReportController@index) ------------
    // Emite eventos para a página de Relatórios consumir se quiser
    async function fetchAllReports(monthStr) {
        // Tipos suportados pelo seu backend atual:
        // 'despesas_por_categoria', 'receitas_por_categoria', 'saldo_mensal'
        const [despCat, recCat, saldo] = await Promise.all([
            apiReport('despesas_por_categoria', monthStr).catch(() => null),
            apiReport('receitas_por_categoria', monthStr).catch(() => null),
            apiReport('saldo_mensal', monthStr).catch(() => null),
        ]);
        return { despCat, recCat, saldo };
    }

    window.refreshReports = async function () {
        if (!isReportsPage()) return; // só atualiza quando está na tela de relatórios
        const monthStr = window.LukratoHeader?.getMonth ? window.LukratoHeader.getMonth() : new Date().toISOString().slice(0, 7);
        try {
            const data = await fetchAllReports(monthStr);
            // dispara um evento para a página de relatórios desenhar (gráficos, tabelas, etc.)
            document.dispatchEvent(new CustomEvent('lukrato:reports-data', { detail: { month: monthStr, ...data } }));
        } catch (e) {
            console.error(e);
            if (window.Swal) Swal.fire('Erro', 'Falha ao carregar relatórios.', 'error');
        }
    };

    // Quando o mês mudar pelo header → recarrega relatórios
    document.addEventListener('lukrato:month-changed', () => {
        window.refreshReports && window.refreshReports();
    });

    // Boot
    document.addEventListener('DOMContentLoaded', () => {
        bindModalsIntegracao();
        // Se já estiver na página de Relatórios ao carregar, busca logo
        window.refreshReports && window.refreshReports();
    });
})();
// Corrige BASE_URL de forma robusta, mesmo em subpastas tipo /lukrato/public/
(function fixBaseUrl() {
    const meta = document.querySelector('meta[name="base-url"]')?.content || '';
    let base = meta;

    // Se o meta estiver errado/vazio, tenta deduzir do path atual
    if (!base) {
        const m = location.pathname.match(/^(.*\/public\/)/);
        base = m ? (location.origin + m[1]) : (location.origin + '/');
    }

    // Se o meta veio sem a parte '/public/', completa a partir do path atual
    if (base && !/\/public\/?$/.test(base)) {
        const m2 = location.pathname.match(/^(.*\/public\/)/);
        if (m2) base = location.origin + m2[1];
    }

    // Garante barra final
    base = base.replace(/\/?$/, '/');

    // Exponha para todo o app
    window.BASE_URL = base;
    console.log('[Lukrato] BASE_URL =', window.BASE_URL);
})();

// ===== Lukrato Modals Core (global) =====
(function lukratoFailsafe() {
    const $ = (s, sc = document) => sc.querySelector(s);
    const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));
    const idFromKey = (key) => 'modal' + String(key || '').replace(/(^|-)(\w)/g, (_, __, b) => b
        .toUpperCase());

    // 1) MÊS no header – escreve se estiver vazio
    document.addEventListener('DOMContentLoaded', () => {
        const span = $('#currentMonthText');
        const hasText = span && span.textContent && span.textContent.trim() !== '—' && span
            .textContent.trim() !== '';
        if (span && !hasText) {
            const m = (window.LukratoHeader?.getMonth?.() || new Date().toISOString().slice(0, 7));
            const [y, mm] = m.split('-').map(Number);
            const label = new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
            span.textContent = label;
        }
    });

    // 2) Abrir modais por clique (suporta data-open-modal E data-modal)
    function toggleModal(id, open) {
        const m = document.getElementById(id);
        if (!m) return;
        const isLK = m.classList.contains('lk-modal');
        const backdropSel = isLK ? '.lk-modal-backdrop' : '.modal-backdrop';
        m.classList.toggle('active', !!open);
        m.setAttribute('aria-hidden', String(!open));
        if (open) {
            const today = new Date().toISOString().slice(0, 10);
            m.querySelectorAll('input[type="date"]').forEach(inp => {
                if (!inp.value) inp.value = today;
            });
            // foca primeiro campo
            (m.querySelector('input,select,textarea,button') || {}).focus?.();
            document.body.style.overflow = 'hidden';
        } else {
            // fecha: libera scroll caso nenhum outro modal esteja aberto
            if (!document.querySelector('.lk-modal.active, .modal.active')) {
                document.body.style.overflow = '';
            }
        }
    }
    window.LukratoModal = window.LukratoModal || {
        toggle: toggleModal
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open-modal],[data-modal]');
        if (btn) {
            e.preventDefault();
            const key = btn.getAttribute('data-open-modal') || btn.getAttribute('data-modal');
            document.dispatchEvent(new CustomEvent('lukrato:open-modal', {
                detail: {
                    key
                }
            }));
            toggleModal(idFromKey(key), true);
            return;
        }
        // fechar por X, backdrop, data-dismiss
        if (
            e.target.closest('.lk-modal-close,.modal-close,[data-dismiss="modal"]') ||
            e.target.classList.contains('lk-modal-backdrop') ||
            e.target.classList.contains('modal-backdrop')
        ) {
            e.preventDefault();
            const m = e.target.closest('.lk-modal,.modal') || document.querySelector(
                '.lk-modal.active,.modal.active');
            if (m) toggleModal(m.id, false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const top = $$('.lk-modal.active,.modal.active').at(-1);
        if (top) {
            e.preventDefault();
            toggleModal(top.id, false);
        }
    });

    // 3) FAB fallback (se o bind do header não existiu)
    document.addEventListener('DOMContentLoaded', () => {
        const fab = $('#fabButton');
        const menu = $('#fabMenu');
        if (fab && menu && !fab.dataset.lkFabBound) {
            fab.dataset.lkFabBound = '1';
            fab.addEventListener('click', () => {
                const open = !menu.classList.contains('active');
                fab.classList.toggle('active', open);
                fab.setAttribute('aria-expanded', String(open));
                menu.classList.toggle('active', open);
            });
        }
    });
})();
