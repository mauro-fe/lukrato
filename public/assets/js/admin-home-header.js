// BASE_URL a partir do <meta name="base-url" ...>
(function () {
    const m = document.querySelector('meta[name="base-url"]');
    let base = (m && m.content) ? m.content : (location.origin + '/');
    if (!/\/$/.test(base)) base += '/';
    window.BASE_URL = base;
})();

(function () {
    const BASE = window.BASE_URL || '/';

    // ===== Utils =====
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

    // ===== API =====
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

    // ===== Modal: abrir/fechar (fixado no canto) =====
    function toggleModal(open) {
        const m = $('#modalLancamento');
        if (!m) return;

        // controla flags de abertura
        m.classList.toggle('active', !!open);
        m.setAttribute('aria-hidden', String(!open));

        // BLINDAGEM contra centralização por CSS/JS externo
        // (afasta qualquer display:flex aplicado por classes globais)
        m.style.display = open ? 'block' : '';

        // travar/destravar scroll do body
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            // reset de campos
            $('#lanData').value = new Date().toISOString().slice(0, 10);
            $('#lanValor').value = '';
            $('#lanDescricao').value = '';
            $('#lanObservacao').value = '';
            $('#lanPago').checked = false;

            // dispara carregamento de categorias conforme o tipo atual
            $('#lanTipo').dispatchEvent(new Event('change'));

            // foco no 1º input
            (m.querySelector('input,select,textarea') || {}).focus?.();
        }
    }

    // ===== Abre pelo FAB/botões com data-open-modal =====
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-open-modal]');
        if (b) {
            e.preventDefault();
            const type = b.getAttribute('data-open-modal');
            if (type === 'receita' || type === 'despesa') {
                $('#lanTipo').value = type;
            } else {
                $('#lanTipo').value = 'despesa'; // default
            }
            toggleModal(true);
        }

        // ===== FECHAR (corrigido para lkh-*) =====
        if (
            e.target.closest('.lkh-modal-close,[data-dismiss="modal"]') ||
            e.target.classList.contains('lkh-modal-backdrop')
        ) {
            e.preventDefault();
            toggleModal(false);
        }
    });

    // ESC para fechar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && $('#modalLancamento.active')) toggleModal(false);
    });

    // ===== Máscara leve de dinheiro =====
    $('#lanValor')?.addEventListener('blur', () => {
        $('#lanValor').value = brl(parseMoney($('#lanValor').value));
    });
    $('#lanValor')?.addEventListener('focus', () => {
        const v = String(parseMoney($('#lanValor').value)).replace('.', ',');
        $('#lanValor').value = v === '0' ? '' : v;
    });

    // ===== Carregar categorias conforme o tipo =====
    async function fillCategorias() {
        try {
            const tipo = $('#lanTipo').value; // 'receita' | 'despesa'
            const opt = await apiOptions();

            const list = tipo === 'receita'
                ? (opt?.categorias?.receitas || [])
                : (opt?.categorias?.despesas || []);

            const sel = $('#lanCategoria');
            sel.innerHTML = '<option value="">Selecione uma categoria</option>' +
                list.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');

            // Ajusta label do checkbox
            const lbl = $('#lanPagoLabel');
            if (lbl) lbl.textContent = (tipo === 'receita') ? 'Foi recebido?' : 'Foi pago?';

            // Campos futuros (mantidos ocultos por enquanto)
            const grpConta = $('#grpConta');
            const grpCartao = $('#grpCartao');
            const grpParcelas = $('#grpParcelas');
            if (grpConta) grpConta.style.display = 'none';
            if (grpCartao) grpCartao.style.display = 'none';
            if (grpParcelas) grpParcelas.style.display = 'none';
        } catch (e) {
            console.error(e);
            if (window.Swal) Swal.fire('Erro', 'Não foi possível carregar categorias.', 'error');
        }
    }
    $('#lanTipo')?.addEventListener('change', fillCategorias);

    // ===== Submit do formulário =====
    $('#formLancamento')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const tipo = $('#lanTipo').value; // 'receita' | 'despesa'
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

            // Recarrega visões se existirem
            if (window.refreshDashboard) window.refreshDashboard();
            if (window.refreshReports) window.refreshReports();
            if (window.fetchLancamentos) window.fetchLancamentos();
        } catch (err) {
            console.error(err);
            if (window.Swal) Swal.fire('Erro', err.message || 'Falha ao salvar', 'error');
        }
    });

    // ===== Inicialização opcional =====
    document.addEventListener('DOMContentLoaded', () => {
        // Se quiser pré-carregar categorias ao abrir a página, descomente:
        // fillCategorias();
    });
})();
