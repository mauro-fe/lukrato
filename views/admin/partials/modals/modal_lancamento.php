<div class="modal fade" id="modalLancamento" tabindex="-1" aria-labelledby="modalLancamentoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalLancamentoTitle">Novo lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-0">
                <div id="novoLancAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formNovoLancamento" novalidate autocomplete="off">
                    <div class="row g-3">

                        <div class="mb-3">
                            <label for="lanData" class="form-label text-light small mb-1">Data</label>
                            <input type="date" id="lanData" name="data"
                                class="form-control form-control-sm bg-dark text-light border-secondary" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lanTipo" class="form-label text-light small mb-1">Tipo</label>
                            <select id="lanTipo" name="tipo"
                                class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lanCategoria" class="form-label text-light small mb-1">Categoria</label>
                            <select id="lanCategoria" name="categoria_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="headerConta" class="form-label text-light small mb-1">Conta</label>
                            <select id="headerConta" name="conta_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary">
                                <option value="">Todas as contas (opcional)</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="lanValor" class="form-label text-light small mb-1">Valor</label>
                            <input type="text" id="lanValor" name="valor"
                                class="form-control form-control-sm bg-dark text-light border-secondary money-mask"
                                placeholder="R$ 0,00" required>
                        </div>

                        <div class="col-md-9 mb-3">
                            <label for="lanDescricao" class="form-label text-light small mb-1">Descrição</label>
                            <input type="text" id="lanDescricao" name="descricao"
                                class="form-control form-control-sm bg-dark text-light border-secondary"
                                placeholder="Descrição do lançamento (opcional)">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formNovoLancamento" class="btn btn-primary btn-sm">Salvar</button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    const API_BASE = (window.LK?.apiBase) || ((document.querySelector('meta[name="base-url"]')?.content || '/') +
        'api/');
    const CSRF = (window.LK?.getCSRF?.()) || (document.querySelector('meta[name="csrf"]')?.content) || '';

    const $form = document.getElementById('formNovoLancamento');
    const $alert = document.getElementById('novoLancAlert');
    const $data = document.getElementById('lanData');
    const $tipo = document.getElementById('lanTipo');
    const $categoria = document.getElementById('lanCategoria');
    const $conta = document.getElementById('headerConta');
    const $valor = document.getElementById('lanValor');

    // ---------------- Utils ----------------
    const isoTodayLocal = () => {
        const d = new Date();
        const off = d.getTimezoneOffset();
        const local = new Date(d.getTime() - off * 60000);
        return local.toISOString().slice(0, 10);
    };

    const fetchJSON = async (url, opts = {}) => {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                ...(opts.body instanceof FormData ? {} : {
                    'Content-Type': 'application/json'
                }),
                'X-CSRF-TOKEN': CSRF
            },
            credentials: 'same-origin',
            ...opts
        });
        if (!res.ok) {
            let msg = `HTTP ${res.status}`;
            try {
                const j = await res.json();
                if (j?.message) msg = j.message;
            } catch {}
            throw new Error(msg);
        }
        try {
            return await res.json();
        } catch {
            return null;
        }
    };

    const toArray = (items) => {
        if (Array.isArray(items)) return items;
        if (Array.isArray(items?.data)) return items.data;
        if (Array.isArray(items?.items)) return items.items;
        return [];
    };

    const clearAndFill = (select, items, getValue, getLabel, placeholder) => {
        select.innerHTML = '';
        if (placeholder) {
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = placeholder;
            select.appendChild(opt0);
        }
        toArray(items).forEach(it => {
            const opt = document.createElement('option');
            opt.value = getValue(it);
            opt.textContent = getLabel(it);
            select.appendChild(opt);
        });
    };

    // ---------------- Loaders ----------------
    // --- carrega CONTAS ---
    const loadContas = async () => {
        const data = await fetchJSON(API_BASE + 'accounts');
        clearAndFill($conta, data, it => it.id, it => it.nome, 'Todas as contas (opcional)');
    };

    const loadCategorias = async (tipo) => {
        const qs = tipo ? ('?tipo=' + encodeURIComponent(tipo)) : '';
        let data;
        try {
            data = await fetchJSON(API_BASE + 'categorias' + qs);
        } catch (e) {
            if (String(e?.message || '').includes('404')) {
                data = await fetchJSON(API_BASE + 'categorias' + qs); // fallback se tua rota for singular
            } else {
                throw e;
            }
        }
        clearAndFill($categoria, data, it => it.id, it => it.nome, 'Selecione uma categoria');
    };



    // ---------------- máscara BRL ----------------
    const BRL = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    const unformatBRL = (s) => Number(String(s).replace(/\s|[R$]/g, '').replace(/\./g, '').replace(',', '.')) || 0;

    $valor?.addEventListener('input', (e) => {
        const raw = e.target.value.replace(/[^\d]/g, '');
        const num = (Number(raw) / 100).toFixed(2);
        e.target.value = BRL.format(num);
    }, {
        passive: true
    });

    // ---------------- submit ----------------
    $form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        $alert.classList.add('d-none');
        $alert.textContent = '';

        const payload = {
            data: $data.value,
            tipo: $tipo.value, // 'despesa' | 'receita'
            categoria_id: $categoria.value || null,
            conta_id: $conta.value || null,
            valor: unformatBRL($valor.value),
            descricao: document.getElementById('lanDescricao')?.value || ''
        };

        try {
            await fetchJSON(API_BASE + 'lancamentos', {
                method: 'POST',
                body: JSON.stringify(payload)
            });

            const modalEl = document.getElementById('modalLancamento');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();

            if (window.Swal) Swal.fire({
                icon: 'success',
                title: 'Lançamento salvo!',
                timer: 1500,
                showConfirmButton: false
            });
            if (window.LK?.refreshDashboard) window.LK.refreshDashboard();
            if (window.LK?.refreshTable) window.LK.refreshTable();

            $form.reset();
            $data.value = isoTodayLocal();
            await loadCategorias($tipo.value);

        } catch (err) {
            $alert.textContent = 'Erro ao salvar: ' + (err?.message || 'Tente novamente.');
            $alert.classList.remove('d-none');
        }
    });

    // quando muda o tipo, recarrega categorias
    $tipo?.addEventListener('change', () => {
        loadCategorias($tipo.value).catch(console.error);
    });

    // ao abrir o modal
    document.getElementById('modalLancamento')?.addEventListener('shown.bs.modal', async () => {
        if (!$data.value) $data.value = isoTodayLocal();
        try {
            await Promise.all([loadContas(), loadCategorias($tipo.value)]);
        } catch (err) {
            $alert.textContent = 'Erro ao carregar dados: ' + (err?.message || '');
            $alert.classList.remove('d-none');
        }
    });

    // inicial
    if ($data && !$data.value) $data.value = isoTodayLocal();
})();
</script>