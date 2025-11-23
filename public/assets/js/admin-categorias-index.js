
(() => {
    'use strict';

    // Previne inicialização dupla
    if (window.__LK_CATEGORIAS_LOADER__) return;
    window.__LK_CATEGORIAS_LOADER__ = true;

    // ==================== CONFIGURAÇÃO ====================
    const CONFIG = {
        BASE_URL: (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/'),
        TABLE_HEIGHT: '520px',
        ALLOWED_TYPES: ['receita', 'despesa', 'transferencia'],
        MIN_NAME_LENGTH: 2,
        MAX_NAME_LENGTH: 100
    };

    CONFIG.API_URL = `${CONFIG.BASE_URL}api/`;

    // ==================== SELETORES DOM ====================
    const DOM = {
        // Formulário novo
        formNova: document.getElementById('formNova'),

        // Filtro
        filtroTipo: document.getElementById('filtroTipo'),

        // Tabela
        tabContainer: document.getElementById('tabCategorias'),
        cardContainer: document.getElementById('catCards'),

        // Modal
        modalEditEl: document.getElementById('modalEditCategoria'),
        formEdit: document.getElementById('formEditCategoria'),
        inputEditNome: document.getElementById('editCategoriaNome'),
        selectEditTipo: document.getElementById('editCategoriaTipo'),
        editAlert: document.getElementById('editCategoriaAlert')
    };

    // ==================== ESTADO ====================
    const STATE = {
        table: null,
        modalEdit: null,
        editingId: null,
        categoriaCache: new Map()
    };

    // ==================== UTILITÁRIOS ====================
    const Utils = {
        getCsrfToken: () => {
            return document.querySelector('input[name="_token"]')?.value ||
                document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        escapeHtml: (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m] || m)),

        capitalize: (str) => {
            const s = String(str || '');
            return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
        },

        validateName: (name) => {
            const trimmed = (name || '').trim();
            if (trimmed.length < CONFIG.MIN_NAME_LENGTH) {
                return `Nome deve ter pelo menos ${CONFIG.MIN_NAME_LENGTH} caracteres.`;
            }
            if (trimmed.length > CONFIG.MAX_NAME_LENGTH) {
                return `Nome deve ter no máximo ${CONFIG.MAX_NAME_LENGTH} caracteres.`;
            }
            return null;
        },

        validateType: (type) => {
            if (!CONFIG.ALLOWED_TYPES.includes(type)) {
                return 'Selecione um tipo válido.';
            }
            return null;
        }
    };

    // ==================== API ====================
    const API = {
        buildUrl: (path) => {
            return `${CONFIG.API_URL}${path}`.replace(/([^:])\/{2,}/g, '$1/');
        },

        fetch: async (path, opts = {}) => {
            const url = API.buildUrl(path);
            opts.credentials = 'include';

            const method = (opts.method || 'GET').toUpperCase();
            const isBodyless = method === 'GET' || method === 'HEAD';
            const hasFormData = opts.body instanceof FormData;

            if (!isBodyless && !hasFormData) {
                const token = Utils.getCsrfToken();
                const payload = opts.body && typeof opts.body === 'object' ? opts.body : {};

                opts.headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token,
                    ...(opts.headers || {})
                };

                opts.body = JSON.stringify({
                    ...payload,
                    _token: token
                });
            } else if (!isBodyless && hasFormData) {
                const token = Utils.getCsrfToken();
                if (token && !opts.body.has('_token')) {
                    opts.body.append('_token', token);
                }
                opts.headers = {
                    'X-CSRF-Token': token,
                    ...(opts.headers || {})
                };
            }

            const res = await fetch(url, opts);
            const ct = res.headers.get('content-type') || '';
            const data = ct.includes('application/json') ?
                await res.json() : {
                    status: 'error',
                    message: await res.text()
                };

            if (!res.ok) {
                if (res.status === 403) {
                    throw new Error('Acesso negado. Possível falha de autenticação ou sessão expirada.');
                }
                if (res.status === 404) {
                    throw new Error('Recurso não encontrado: ' + url);
                }
                throw new Error(data?.message || `Erro ${res.status}`);
            }

            return data;
        },

        loadCategorias: async (tipo = '') => {
            const query = tipo ? `?tipo=${encodeURIComponent(tipo)}` : '';
            return await API.fetch(`categorias${query}`);
        },

        createCategoria: async (formData) => {
            return await API.fetch('categorias', {
                method: 'POST',
                body: formData
            });
        },

        updateCategoria: async (id, data) => {
            return await API.fetch(`categorias/${encodeURIComponent(id)}`, {
                method: 'PUT',
                body: data
            });
        },

        deleteCategoria: async (id) => {
            return await API.fetch(`categorias/${encodeURIComponent(id)}`, {
                method: 'DELETE'
            });
        }
    };

    // ==================== NOTIFICAÇÕES ====================
    const Notifications = {
        toast: (icon, title) => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                icon,
                title
            });
        },

        error: (msg) => {
            Swal.fire({
                icon: 'error',
                title: 'Ops...',
                text: msg || 'Algo deu errado.',
                confirmButtonColor: 'var(--color-primary)'
            });
        },

        confirm: async (title = 'Confirmar?', text = 'Esta ação não pode ser desfeita.') => {
            const result = await Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: 'Sim, confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--color-danger)',
                cancelButtonColor: 'var(--color-text-muted)'
            });
            return result.isConfirmed;
        }
    };

    // ==================== GERENCIAMENTO DE TABELA ====================
    const TableManager = {
        buildColumns: () => [{
            title: "Nome",
            field: "nome",
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar por nome",
            widthGrow: 2,
            formatter: (cell) => Utils.escapeHtml(cell.getValue() || "-")
        },
        {
            title: "Tipo",
            field: "tipo",
            headerFilter: "select",
            headerFilterParams: {
                values: {
                    "": "Todos",
                    receita: "Receita",
                    despesa: "Despesa"
                }
            },
            width: 300,
            hozAlign: "center",

            formatter: (cell) => {
                const value = String(cell.getValue() || "").toLowerCase();
                const tipo = ["receita", "despesa"].includes(value) ? value : "";

                if (!tipo) return "-";

                return `<span class="badge-tipo ${tipo}">${Utils.capitalize(tipo)}</span>`;
            }
        },
        {
            title: "Ações",
            field: "acoes",
            headerSort: false,
            hozAlign: "center",
            width: 300,
            formatter: () => {
                return `
        <div class="actions-cell">
            <button type="button" class="lk-btn ghost btn-edit" data-action="edit" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="lk-btn ghost btn-del" data-action="delete" title="Excluir">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        `;
            },
            cellClick: async (e, cell) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');
                const rowData = cell.getRow().getData();
                const id = rowData?.id;

                if (!id) return;

                btn.disabled = true;

                if (action === 'edit') {
                    await ModalManager.openEdit(id);
                } else if (action === 'delete') {
                    await DataManager.delete(id);
                }

                btn.disabled = false;
            }
        }
        ],

        build: () => {
            if (!DOM.tabContainer) return null;

            const instance = new Tabulator(DOM.tabContainer, {
                height: CONFIG.TABLE_HEIGHT,
                layout: 'fitColumns',
                placeholder: 'Nenhuma categoria encontrada.',
                index: 'id',
                columns: TableManager.buildColumns()
            });

            return instance;
        },

        ensure: () => {
            if (STATE.table || !DOM.tabContainer) return STATE.table;
            STATE.table = TableManager.build();
            return STATE.table;
        },

        setData: (items) => {
            const table = TableManager.ensure();
            if (table) {
                table.setData(Array.isArray(items) ? items : []);
            }

            MobileCards.render(items);
        }
    };

    // ==================== RENDERIZAÇÃO MOBILE (CARDS) ====================
    const MobileCards = {
        render: (items) => {
            if (!DOM.cardContainer) return;

            const list = Array.isArray(items) ? items : [];

            if (!list.length) {
                DOM.cardContainer.innerHTML = '';
                return;
            }

            const header = `
                <div class="c-mobile-card-header">
                    <span>Nome</span>
                    <span>Tipo</span>
                    <span>Ações</span>
                </div>
            `;

            const body = list.map((item) => {
                const nome = Utils.escapeHtml(item?.nome || '-');
                const tipoRaw = String(item?.tipo || '').toLowerCase();
                const tipoClass = ['receita', 'despesa'].includes(tipoRaw) ? tipoRaw : '';
                const tipoLabel = Utils.capitalize(tipoRaw || '-');
                const id = item?.id ?? '';

                return `
                    <div class="c-mobile-card" data-id="${id}">
                        <div class="c-cat-name">${nome}</div>
                        <div class="c-cat-type">
                            <span class="badge-tipo ${tipoClass}">${tipoLabel}</span>
                        </div>
                        <div class="c-cat-actions">
                            <button type="button" class="lk-btn ghost btn-edit" data-action="edit" data-id="${id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="lk-btn ghost btn-del" data-action="delete" data-id="${id}" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            DOM.cardContainer.innerHTML = header + body;
        }
    };

    // ==================== GERENCIAMENTO DE MODAL ====================
    const ModalManager = {
        ensure: () => {
            if (STATE.modalEdit) return STATE.modalEdit;
            if (!DOM.modalEditEl) return null;

            if (window.bootstrap?.Modal) {
                if (DOM.modalEditEl.parentElement && DOM.modalEditEl.parentElement !== document.body) {
                    document.body.appendChild(DOM.modalEditEl);
                }
                STATE.modalEdit = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditEl);
                return STATE.modalEdit;
            }
            return null;
        },

        clearAlert: () => {
            if (!DOM.editAlert) return;
            DOM.editAlert.classList.add('d-none');
            DOM.editAlert.textContent = '';
        },

        showAlert: (msg) => {
            if (!DOM.editAlert) return;
            DOM.editAlert.textContent = msg;
            DOM.editAlert.classList.remove('d-none');
        },

        openEdit: async (id) => {
            const categoria = STATE.categoriaCache.get(String(id));
            if (!categoria) {
                Notifications.error('Categoria não encontrada.');
                return;
            }

            const modal = ModalManager.ensure();
            if (!modal) {
                Notifications.error('Modal indisponível.');
                return;
            }

            STATE.editingId = String(id);
            ModalManager.clearAlert();

            if (DOM.inputEditNome) {
                DOM.inputEditNome.value = categoria.nome || '';
            }

            if (DOM.selectEditTipo) {
                const tipo = CONFIG.ALLOWED_TYPES.includes(categoria.tipo) ? categoria.tipo : 'despesa';
                DOM.selectEditTipo.value = tipo;
            }

            modal.show();
        },

        submitEdit: async (ev) => {
            ev.preventDefault();
            if (!STATE.editingId) return;

            ModalManager.clearAlert();
            const submitBtn = DOM.formEdit.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            const nome = (DOM.inputEditNome?.value || '').trim();
            const tipo = (DOM.selectEditTipo?.value || '').trim();

            // Validações
            const nameError = Utils.validateName(nome);
            if (nameError) {
                ModalManager.showAlert(nameError);
                submitBtn?.removeAttribute('disabled');
                return;
            }

            const typeError = Utils.validateType(tipo);
            if (typeError) {
                ModalManager.showAlert(typeError);
                submitBtn?.removeAttribute('disabled');
                return;
            }

            try {
                const result = await API.updateCategoria(STATE.editingId, {
                    nome,
                    tipo
                });

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Falha ao atualizar categoria.');
                }

                ModalManager.ensure()?.hide();
                Notifications.toast('success', 'Categoria atualizada com sucesso!');
                await DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'update',
                        id: Number(STATE.editingId)
                    }
                }));
            } catch (err) {
                ModalManager.showAlert(err.message || 'Falha ao atualizar categoria.');
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        }
    };

    // ==================== GERENCIAMENTO DE DADOS ====================
    const DataManager = {
        load: async () => {
            const tipo = DOM.filtroTipo?.value || '';

            try {
                const result = await API.loadCategorias(tipo);

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Falha ao carregar categorias');
                }

                const items = Array.isArray(result.data) ? result.data : [];

                // Atualizar cache
                STATE.categoriaCache.clear();
                items.forEach((cat) => {
                    const id = cat?.id;
                    if (id !== undefined && id !== null) {
                        STATE.categoriaCache.set(String(id), cat);
                    }
                });

                TableManager.setData(items);
            } catch (err) {
                console.error('Erro ao carregar categorias:', err);
                Notifications.error(err.message);
                TableManager.setData([]);
            }
        },

        create: async (ev) => {
            ev.preventDefault();

            const formData = new FormData(DOM.formNova);
            const nome = (formData.get('nome') || '').trim();
            const tipo = (formData.get('tipo') || '').trim();

            // Validações
            const nameError = Utils.validateName(nome);
            if (nameError) {
                Notifications.error(nameError);
                return;
            }

            const typeError = Utils.validateType(tipo);
            if (typeError) {
                Notifications.error(typeError);
                return;
            }

            const submitBtn = DOM.formNova.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            try {
                const result = await API.createCategoria(formData);

                if (result.status !== 'success') {
                    const msg = result.message ||
                        (result.errors ? Object.values(result.errors).join('\n') :
                            'Falha ao criar categoria');
                    throw new Error(msg);
                }

                DOM.formNova.reset();
                Notifications.toast('success', 'Categoria criada com sucesso!');
                await DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'create',
                        id: result.data?.id ?? null
                    }
                }));
            } catch (err) {
                console.error('Erro ao criar categoria:', err);
                Notifications.error(err.message);
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        },

        delete: async (id) => {
            const confirmed = await Notifications.confirm(
                'Deseja remover esta categoria?',
                'Esta ação não pode ser desfeita e pode afetar lançamentos vinculados.'
            );

            if (!confirmed) return;

            try {
                const result = await API.deleteCategoria(id);

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Falha ao remover categoria.');
                }

                Notifications.toast('success', 'Categoria removida com sucesso!');
                await DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'delete',
                        id: Number(id)
                    }
                }));
            } catch (err) {
                console.error('Erro ao deletar categoria:', err);
                Notifications.error(err.message);
            }
        }
    };

    // ==================== EVENT LISTENERS ====================
    const EventListeners = {
        init: () => {
            // Filtro de tipo
            DOM.filtroTipo?.addEventListener('change', DataManager.load);

            // Formulário de nova categoria
            DOM.formNova?.addEventListener('submit', DataManager.create);

            // Modal fechou - limpar dados
            DOM.modalEditEl?.addEventListener('hidden.bs.modal', () => {
                STATE.editingId = null;
                ModalManager.clearAlert();
                DOM.formEdit?.reset();
            });

            // Formulário de edição
            DOM.formEdit?.addEventListener('submit', ModalManager.submitEdit);

            // Ações dos cards (mobile)
            DOM.cardContainer?.addEventListener('click', async (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');
                if (!id) return;

                btn.disabled = true;
                if (action === 'edit') {
                    await ModalManager.openEdit(id);
                } else if (action === 'delete') {
                    await DataManager.delete(id);
                }
                btn.disabled = false;
            });

            // Eventos globais
            document.addEventListener('lukrato:data-changed', (e) => {
                const resource = e.detail?.resource;
                if (resource === 'categorias') {
                    DataManager.load();
                }
            });
        }
    };

    // ==================== INICIALIZAÇÃO ====================
    const init = async () => {
        console.log('🚀 Inicializando Sistema de Categorias...');

        // Inicializar componentes
        TableManager.ensure();
        ModalManager.ensure();
        EventListeners.init();

        // Carregar dados iniciais
        await DataManager.load();

        console.log('✅ Sistema de Categorias carregado com sucesso!');
        console.log(`📊 ${STATE.categoriaCache.size} categoria(s) carregada(s)`);
    };

    // Iniciar aplicação
    init();
})();
