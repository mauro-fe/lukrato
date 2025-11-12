<style>
    /* Modal Backdrop */
    .modal-backdrop.show {
        backdrop-filter: blur(12px) saturate(180%);
        background: rgba(0, 0, 0, 0.5);
    }

    /* Modal Content */
    #modalLancamento .modal-content {
        background: var(--color-surface) !important;
        color: var(--color-text);
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-xl), 0 0 0 1px rgba(230, 126, 34, 0.1);
        overflow: hidden;
        position: relative;
        font-family: var(--font-primary);
    }

    /* Barra decorativa superior */
    #modalLancamento .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg,
                transparent,
                var(--color-primary) 30%,
                var(--color-primary) 70%,
                transparent);
        opacity: 0.8;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {

        0%,
        100% {
            opacity: 0.6;
        }

        50% {
            opacity: 1;
        }
    }

    /* Header */
    #modalLancamento .modal-header {
        border: 0;
        background: transparent;
        padding: var(--spacing-6) var(--spacing-6) var(--spacing-4);
        position: relative;
    }

    #modalLancamento .modal-title {
        font-size: var(--font-size-xl);
        font-weight: 700;
        color: var(--color-primary);
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }

    #modalLancamento .modal-title::before {
        content: '💸';
        font-size: var(--font-size-2xl);
        animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-4px);
        }
    }

    /* Botão Close */
    #modalLancamento .btn-close {
        background: var(--glass-bg);
        border-radius: 50%;
        width: 36px;
        height: 36px;
        opacity: 0.8;
        transition: var(--transition-normal);
        position: relative;
        backdrop-filter: blur(10px);
        color: var(--color-primary) !important;
    }

    #modalLancamento .btn-close:hover {
        opacity: 1;
        background: var(--color-danger);
        transform: rotate(90deg) scale(1.1);
    }

    /* Body */
    #modalLancamento .modal-body {
        padding: 0 var(--spacing-6) var(--spacing-5);
    }

    /* Alert */
    #modalLancamento #novoLancAlert {
        border-radius: var(--radius-md);
        border: 1px solid var(--color-danger);
        background: rgba(231, 76, 60, 0.1);
        backdrop-filter: blur(10px);
        padding: var(--spacing-3) var(--spacing-4);
        font-size: var(--font-size-sm);
        animation: slideDown 0.3s ease;
        margin-bottom: var(--spacing-4);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Labels */
    #modalLancamento .form-label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        font-weight: 600;
        margin-bottom: var(--spacing-2);
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        letter-spacing: 0.01em;
    }

    /* Inputs e Selects */
    #modalLancamento .form-control,
    #modalLancamento .form-select {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        padding: var(--spacing-3) var(--spacing-4);
        transition: var(--transition-normal);
        font-family: var(--font-primary);
    }

    #modalLancamento .form-control::placeholder {
        color: var(--color-text-muted);
        opacity: 0.6;
    }

    #modalLancamento .form-control:focus,
    #modalLancamento .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
        background: var(--color-surface);
        transform: translateY(-1px);
    }

    #modalLancamento .form-control:hover:not(:focus),
    #modalLancamento .form-select:hover:not(:focus) {
        border-color: rgba(230, 126, 34, 0.4);
    }

    /* Select customizado */
    #modalLancamento .form-select {
        cursor: pointer;
        background-position: right var(--spacing-3) center;
        background-size: 16px;
        padding-right: var(--spacing-6);
    }

    /* Input de Data customizado */
    #modalLancamento input[type="date"] {
        position: relative;
    }

    #modalLancamento input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(0.6) sepia(1) saturate(5) hue-rotate(360deg);
        cursor: pointer;
        opacity: 0.8;
        transition: var(--transition-fast);
    }

    #modalLancamento input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    /* Badge de tipo (visual indicator) */
    #modalLancamento #lanTipo {
        font-weight: 600;
        position: relative;
    }

    #modalLancamento #lanTipo option[value="despesa"] {
        background: rgba(231, 76, 60, 0.2);
    }

    #modalLancamento #lanTipo option[value="receita"] {
        background: rgba(46, 204, 113, 0.2);
    }

    /* Footer */
    #modalLancamento .modal-footer {
        border: 0;
        padding: var(--spacing-4) var(--spacing-6) var(--spacing-6);
        background: transparent;
        gap: var(--spacing-3);
    }

    /* Botões */
    #modalLancamento .btn {
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        padding: var(--spacing-3) var(--spacing-6);
        transition: var(--transition-normal);
        border: none;
        font-family: var(--font-primary);
        letter-spacing: 0.02em;
    }

    #modalLancamento .btn-outline-secondary {
        background: var(--glass-bg);
        border: 2px solid var(--glass-border);
        color: var(--color-text);
    }

    #modalLancamento .btn-outline-secondary:hover {
        background: var(--color-surface-muted);
        border-color: var(--color-text-muted);
        color: var(--color-text);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    #modalLancamento .btn-primary {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: var(--branco);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        position: relative;
        overflow: hidden;
    }

    #modalLancamento .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    #modalLancamento .btn-primary:hover::before {
        left: 100%;
    }

    #modalLancamento .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }

    #modalLancamento .btn-primary:active {
        transform: translateY(0);
    }

    /* Grid */
    #modalLancamento .row {
        --bs-gutter-x: var(--spacing-4);
        --bs-gutter-y: var(--spacing-4);
    }

    /* Espaçamento dos campos */
    #modalLancamento .mb-3 {
        margin-bottom: var(--spacing-4) !important;
    }

    /* Animação de entrada */
    #modalLancamento .modal-dialog {
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Loading state */
    #modalLancamento .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    #modalLancamento .btn-primary:disabled::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin-left: 8px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Responsivo */
    @media (max-width: 576px) {
        #modalLancamento .modal-content {
            border-radius: var(--radius-lg);
        }

        #modalLancamento .modal-body,
        #modalLancamento .modal-header,
        #modalLancamento .modal-footer {
            padding-left: var(--spacing-4);
            padding-right: var(--spacing-4);
        }

        #modalLancamento .col-md-3,
        #modalLancamento .col-md-6,
        #modalLancamento .col-md-9 {
            width: 100%;
        }
    }

    /* Demo button */
    .demo-btn {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: white;
        border: none;
        padding: var(--spacing-4) var(--spacing-6);
        border-radius: var(--radius-md);
        font-size: var(--font-size-base);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-normal);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        font-family: var(--font-primary);
    }

    .demo-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }
</style>

<div class="modal fade" id="modalLancamento" tabindex="-1" aria-labelledby="modalLancamentoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLancamentoTitle">Novo lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div id="novoLancAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formNovoLancamento" novalidate autocomplete="off">
                    <div class="row g-3">

                        <div class="mb-3">
                            <label for="lanData" class="form-label">📅 Data</label>
                            <input type="date" id="lanData" name="data" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lanTipo" class="form-label">💼 Tipo</label>
                            <select id="lanTipo" name="tipo" class="form-select" required>
                                <option value="despesa">💸 Despesa</option>
                                <option value="receita">💰 Receita</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lanCategoria" class="form-label">🏷️ Categoria</label>
                            <select id="lanCategoria" name="categoria_id" class="form-select" required>
                                <option value="">Selecione uma categoria</option>

                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="headerConta" class="form-label">🏦 Conta</label>
                            <select id="headerConta" name="conta_id" class="form-select">
                                <option value="">Todas as contas (opcional)</option>

                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="lanValor" class="form-label">💵 Valor</label>
                            <input type="text" id="lanValor" name="valor" class="form-control money-mask"
                                placeholder="R$ 0,00" required>
                        </div>

                        <div class="col-md-9 mb-3">
                            <label for="lanDescricao" class="form-label">📝 Descrição</label>
                            <input type="text" id="lanDescricao" name="descricao" class="form-control"
                                placeholder="Descrição do lançamento (opcional)">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" form="formNovoLancamento" class="btn btn-primary">
                    Salvar Lançamento
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function() {
        const API_BASE = (window.LK?.apiBase) || ((document.querySelector('meta[name="base-url"]')?.content ||
            '/') + 'api/');
        const CSRF = (window.LK?.getCSRF?.()) || (document.querySelector('meta[name="csrf"]')?.content) || '';

        const $form = document.getElementById('formNovoLancamento');
        const $alert = document.getElementById('novoLancAlert');
        const $data = document.getElementById('lanData');
        const $tipo = document.getElementById('lanTipo');
        const $categoria = document.getElementById('lanCategoria');
        const $conta = document.getElementById('headerConta');
        const $valor = document.getElementById('lanValor');
        const $submitBtn = document.querySelector(
            '#formNovoLancamento button[type="submit"], button[form="formNovoLancamento"]');

        // Utils
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

        // Loaders
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
                    data = await fetchJSON(API_BASE + 'categorias' + qs);
                } else {
                    throw e;
                }
            }
            clearAndFill($categoria, data, it => it.id, it => it.nome, 'Selecione uma categoria');
        };

        // Máscara BRL
        const BRL = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        const unformatBRL = (s) => Number(String(s).replace(/\s|[R$]/g, '').replace(/\./g, '').replace(',', '.')) ||
            0;

        $valor?.addEventListener('input', (e) => {
            const raw = e.target.value.replace(/[^\d]/g, '');
            const num = (Number(raw) / 100).toFixed(2);
            e.target.value = BRL.format(num);
        }, {
            passive: true
        });

        // Submit
        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            $alert.classList.add('d-none');
            $alert.textContent = '';

            // Disable button and show loading
            if ($submitBtn) {
                $submitBtn.disabled = true;
                $submitBtn.textContent = 'Salvando...';
            }

            const payload = {
                data: $data.value,
                tipo: $tipo.value,
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

                // Success notification
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Lançamento salvo!',
                        text: 'Seu lançamento foi registrado com sucesso.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Lançamento salvo com sucesso!');
                }

                if (window.LK?.refreshDashboard) window.LK.refreshDashboard();
                if (window.LK?.refreshTable) window.LK.refreshTable();

                $form.reset();
                $data.value = isoTodayLocal();
                await loadCategorias($tipo.value);

            } catch (err) {
                $alert.textContent = 'Erro ao salvar: ' + (err?.message || 'Tente novamente.');
                $alert.classList.remove('d-none');
            } finally {
                // Re-enable button
                if ($submitBtn) {
                    $submitBtn.disabled = false;
                    $submitBtn.textContent = 'Salvar Lançamento';
                }
            }
        });

        // Quando muda o tipo, recarrega categorias
        $tipo?.addEventListener('change', () => {
            loadCategorias($tipo.value).catch(console.error);
        });

        // Ao abrir o modal
        document.getElementById('modalLancamento')?.addEventListener('shown.bs.modal', async () => {
            if (!$data.value) $data.value = isoTodayLocal();
            try {
                await Promise.all([loadContas(), loadCategorias($tipo.value)]);
            } catch (err) {
                $alert.textContent = 'Erro ao carregar dados: ' + (err?.message || '');
                $alert.classList.remove('d-none');
            }
        });

        // Inicial
        if ($data && !$data.value) $data.value = isoTodayLocal();
    })();
</script>