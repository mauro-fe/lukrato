// BASE_URL a partir do meta
(function () {
    const m = document.querySelector('meta[name="base-url"]');
    let base = (m && m.content) ? m.content : (location.origin + '/');
    if (!/\/$/.test(base)) base += '/';
    window.BASE_URL = base;
})();

(function () {
    const BASE = window.BASE_URL || '/';

    // Utils mínimos
    const $ = (s, sc = document) => sc.querySelector(s);
    const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));
    const brl = (n) => Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    function parseMoney(input) {
        if (input == null) return 0;
        let s = String(input).trim().replace(/[R$\s]/g, '');
        if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
        const n = Number(s);
        return isNaN(n) ? 0 : Number(n.toFixed(2));
    }

    // API mínimas
    async function fetchJSON(url, opts = {}) {
        const r = await fetch(url, { credentials: 'include', ...opts });
        let payload = null;
        try { payload = await r.json(); } catch { }
        if (!r.ok || (payload && (payload.error || payload.status === 'error'))) {
            const msg = payload?.message || payload?.error || 'Erro na requisição';
            throw new Error(msg);
        }
        return payload;
    }
    const apiOptions = () => fetchJSON(BASE + 'api/options');
    const apiCreate = (payload) => fetchJSON(BASE + 'api/transactions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    // Modal simples
    function toggleModal(open) {
        const m = $('#modalLancamento');
        if (!m) return;
        m.classList.toggle('active', !!open);
        m.setAttribute('aria-hidden', String(!open));
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) {
            $('#lanData').value = new Date().toISOString().slice(0, 10);
            $('#lanValor').value = '';
            $('#lanDescricao').value = '';
            $('#lanObservacao').value = '';
            $('#lanPago').checked = false;
            $('#lanTipo').dispatchEvent(new Event('change')); // carrega categorias
            (m.querySelector('input,select,textarea') || {}).focus?.();
        }
    }

    // Abrir pelo FAB existente
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-open-modal]');
        if (b) {
            e.preventDefault();
            // se veio um tipo no botão, pré-seleciona
            const type = b.getAttribute('data-open-modal');
            if (type === 'receita' || type === 'despesa') {
                $('#lanTipo').value = type;
            } else {
                $('#lanTipo').value = 'despesa'; // default
            }
            toggleModal(true);
        }
        if (
            e.target.closest('.lk-modal-close,[data-dismiss="modal"]') ||
            e.target.classList.contains('lk-modal-backdrop')
        ) {
            e.preventDefault();
            toggleModal(false);
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && $('#modalLancamento.active')) toggleModal(false);
    });

    // Máscara leve
    $('#lanValor')?.addEventListener('blur', () => {
        $('#lanValor').value = brl(parseMoney($('#lanValor').value));
    });
    $('#lanValor')?.addEventListener('focus', () => {
        const v = String(parseMoney($('#lanValor').value)).replace('.', ',');
        $('#lanValor').value = v === '0' ? '' : v;
    });

    // Carregar categorias conforme tipo
    async function fillCategorias() {
        try {
            const tipo = $('#lanTipo').value; // 'receita' | 'despesa'
            const opt = await apiOptions();
            const list = tipo === 'receita' ? (opt?.categorias?.receitas || []) : (opt?.categorias?.despesas || []);
            const sel = $('#lanCategoria');
            sel.innerHTML = '<option value="">Selecione uma categoria</option>' +
                list.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
            // Ajusta label do checkbox
            $('#lanPagoLabel').textContent = (tipo === 'receita') ? 'Foi recebido?' : 'Foi pago?';
            // Campos futuros (conta/cartão/parcelas) seguem ocultos até existir backend
            $('#grpConta').style.display = 'none';
            $('#grpCartao').style.display = 'none';
            $('#grpParcelas').style.display = 'none';
        } catch (e) {
            console.error(e);
            if (window.Swal) Swal.fire('Erro', 'Não foi possível carregar categorias.', 'error');
        }
    }
    $('#lanTipo')?.addEventListener('change', fillCategorias);

    // Submit -> usa seu POST /api/transactions
    $('#formLancamento')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const tipo = $('#lanTipo').value; // 'receita' ou 'despesa'
            const data = $('#lanData').value;
            const catId = $('#lanCategoria').value || null;
            const desc = $('#lanDescricao').value || '';
            const obs = $('#lanObservacao').value || '';
            const valor = parseMoney($('#lanValor').value);

            if (!data || !valor || valor < 0) {
                if (window.Swal) Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning');
                return;
            }

            await apiCreate({
                tipo,
                data,
                valor,
                categoria_id: catId ? Number(catId) : null,
                descricao: desc || null,
                observacao: obs || null
            });

            if (window.Swal) Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1200, showConfirmButton: false });
            toggleModal(false);

            // Callbacks opcionais se existirem
            if (window.refreshDashboard) window.refreshDashboard();
            if (window.refreshReports) window.refreshReports();
            if (window.fetchLancamentos) window.fetchLancamentos();
        } catch (err) {
            console.error(err);
            if (window.Swal) Swal.fire('Erro', err.message || 'Falha ao salvar', 'error');
        }
    });

    // Inicializa categorias na primeira abertura
    document.addEventListener('DOMContentLoaded', () => {
        // Se quiser carregar já ao abrir página, descomente:
        // fillCategorias();
    });
})();