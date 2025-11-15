<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">
<section class="c-page">

    <div class="c-card mt-4" data-aos="fade-up">
        <p class="c-subtitle">Crie sua categoria</p>

        <form id="formNova" class="c-form">
            <?= csrf_input('default') ?>
            <input class="c-input" name="nome" placeholder="Nome da categoria" required />
            <select class="lk-select" name="tipo" required>
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
            </select>
            <button class="btn lk-select btn-primary" type="submit">Adicionar</button>
        </form>
    </div>

    <div class="container-table" data-aos="fade-up" data-aos-delay="250">
        <section class="table-container">
            <div id="tabCategorias"></div>
        </section>
    </div>
</section>

<div class="modal fade" id="modalEditCategoria" tabindex="-1" aria-labelledby="modalEditCategoriaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalEditCategoriaLabel">Editar categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-0">
                <div id="editCategoriaAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formEditCategoria" novalidate>
                    <div class="mb-3">
                        <label class="form-label text-light small mb-1" for="editCategoriaNome">Nome</label>
                        <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary"
                            id="editCategoriaNome" name="nome" placeholder="Nome da categoria" required minlength="2"
                            maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small mb-1" for="editCategoriaTipo">Tipo</label>
                        <select class="form-select form-select-sm bg-dark text-light border-secondary"
                            id="editCategoriaTipo" name="tipo" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" form="formEditCategoria">Salvar</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    (() => {
        'use strict';

        function getCsrfToken() {
            return document.querySelector('input[name="_token"]')?.value ||
                document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        const BASE = (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
        const API = `${BASE}api/`;
        const catCache = new Map();
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [m] || m));

        const getCsrf = () => getCsrfToken();

        async function tryFetch(path, opts = {}) {
            const url = `${API}${path}`.replace(/([^:])\/{2,}/g, '$1/');
            opts.credentials = 'include';
            const method = (opts.method || 'GET').toUpperCase();
            const isBodyless = method === 'GET' || method === 'HEAD';
            const hasFormData = (opts.body && typeof FormData !== 'undefined' && opts.body instanceof FormData);

            if (!isBodyless && !hasFormData) {
                const token = getCsrf();
                const payload = opts.body && typeof opts.body === 'object' ? opts.body : {};
                opts.headers = Object.assign({
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                }, opts.headers || {});
                opts.body = JSON.stringify({
                    ...payload,
                    _token: token
                });
            } else if (!isBodyless && hasFormData) {
                const token = getCsrf();
                if (token && !opts.body.has('_token')) opts.body.append('_token', token);
                opts.headers = Object.assign({
                    'X-CSRF-Token': token
                }, opts.headers || {});
            }

            const res = await fetch(url, opts);
            const ct = res.headers.get('content-type') || '';
            const data = ct.includes('application/json') ? await res.json() : {
                status: 'error',
                message: await res.text()
            };

            if (!res.ok) {
                if (res.status === 403) throw new Error(
                    'Acesso negado (403). Possivel falha de CSRF ou sessao expirada.');
                if (res.status === 404) throw new Error('Endpoint nao encontrado (404): ' + url);
                throw new Error(data?.message || `Erro ${res.status}`);
            }
            return data;
        }

        const toast = (icon, title) => Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            icon,
            title
        });
        const alertError = (msg) => Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: msg || 'Algo deu errado.'
        });
        const confirmDel = (title = 'Remover?') => Swal.fire({
            icon: 'warning',
            title,
            text: 'Esta acao nao pode ser desfeita.',
            showCancelButton: true,
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar'
        }).then(r => r.isConfirmed);

        const $ = (s, sc = document) => sc.querySelector(s);
        const filtro = $('#filtroTipo');
        const form = $('#formNova');
        const tabContainer = document.getElementById('tabCategorias');
        const modalEditEl = document.getElementById('modalEditCategoria');
        let modalEdit = null;
        if (modalEditEl && window.bootstrap?.Modal) {
            if (modalEditEl.parentElement && modalEditEl.parentElement !== document.body) {
                document.body.appendChild(modalEditEl);
            }
            modalEdit = window.bootstrap.Modal.getOrCreateInstance(modalEditEl);
        }
        const ensureModalEdit = () => {
            if (modalEdit) return modalEdit;
            if (!modalEditEl) return null;
            if (window.bootstrap?.Modal) {
                if (modalEditEl.parentElement && modalEditEl.parentElement !== document.body) {
                    document.body.appendChild(modalEditEl);
                }
                modalEdit = window.bootstrap.Modal.getOrCreateInstance(modalEditEl);
                return modalEdit;
            }
            return null;
        };
        const formEdit = document.getElementById('formEditCategoria');
        const inputEditNome = document.getElementById('editCategoriaNome');
        const selectEditTipo = document.getElementById('editCategoriaTipo');
        const editAlert = document.getElementById('editCategoriaAlert');
        let editingId = null;

        const clearEditAlert = () => {
            if (!editAlert) return;
            editAlert.classList.add('d-none');
            editAlert.textContent = '';
        };
        const showEditAlert = (msg) => {
            if (!editAlert) return;
            editAlert.textContent = msg;
            editAlert.classList.remove('d-none');
        };

        let table = null;

        function ensureTable() {
            if (table || !tabContainer) return table;
            table = new Tabulator(tabContainer, {
                height: '520px',
                layout: 'fitColumns',
                placeholder: 'Nenhuma categoria encontrada.',
                index: 'id',
                columns: [{
                        title: 'Nome',
                        field: 'nome',
                        headerFilter: 'input',
                        headerFilterPlaceholder: 'Filtrar nome',
                        formatter: (cell) => escapeHtml(String(cell.getValue() || ''))
                    },
                    {
                        title: 'Filtrar por Tipo',
                        field: 'tipo',
                        headerFilter: 'select',
                        headerFilterParams: {
                            values: {
                                '': 'Todos',
                                receita: 'Receita',
                                despesa: 'Despesa'
                            }
                        },
                        formatter: (cell) => {
                            const value = String(cell.getValue() || '').toLowerCase();
                            if (value === 'receita') return 'Receita';
                            if (value === 'despesa') return 'Despesa';
                            return value ? value.charAt(0).toUpperCase() + value.slice(1) : '-';
                        }
                    },
                    {
                        title: 'Acoes',
                        field: 'acoes',
                        headerSort: false,
                        hozAlign: 'center',
                        width: 140,
                        formatter: () => {
                            return '<div class="d-flex justify-content-center gap-2">' +
                                '<button type="button" class="lk-btn ghost btn-edit" data-action="edit" title="Editar"><i class="fas fa-edit"></i></button>' +
                                '<button type="button" class="lk-btn ghost btn-del" data-action="delete" title="Excluir"><i class="fas fa-trash"></i></button>' +
                                '</div>';
                        },
                        cellClick: async (e, cell) => {
                            const btn = e.target.closest('button[data-action]');
                            if (!btn) return;
                            const action = btn.getAttribute('data-action');
                            const rowData = cell.getRow().getData();
                            const id = rowData?.id;
                            if (!id) return;
                            if (action === 'edit') {
                                await handleEditCategoria(id);
                            } else if (action === 'delete') {
                                await handleDeleteCategoria(id);
                            }
                        }
                    }
                ]
            });
            return table;
        }

        async function load() {
            const q = filtro?.value ? `?tipo=${encodeURIComponent(filtro.value)}` : '';
            try {
                const j = await tryFetch(`categorias${q}`);
                if (j.status !== 'success') throw new Error(j.message || 'Falha ao carregar categorias');
                const items = Array.isArray(j.data) ? j.data : [];
                catCache.clear();
                items.forEach((c) => {
                    const id = c?.id;
                    if (id !== undefined && id !== null) {
                        catCache.set(String(id), c);
                    }
                });
                ensureTable()?.setData(items);
            } catch (e) {
                alertError(e.message);
                ensureTable()?.setData([]);
            }
        }

        filtro?.addEventListener('change', load);

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            const nome = (fd.get('nome') || '').toString().trim();
            const tipo = (fd.get('tipo') || '').toString().trim();
            if (nome.length < 2) return alertError('Informe um nome com pelo menos 2 caracteres.');
            if (!['receita', 'despesa', 'transferencia'].includes(tipo)) return alertError(
                'Selecione um tipo valido.');

            try {
                const j = await tryFetch('categorias', {
                    method: 'POST',
                    body: fd
                });
                if (j.status !== 'success') {
                    const msg = j.message || (j.errors ? Object.values(j.errors).join('\n') :
                        'Falha ao criar categoria');
                    throw new Error(msg);
                }
                form.reset();
                toast('success', 'Categoria adicionada!');
                await load();
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'create',
                        id: j.data?.id ?? null
                    }
                }));
            } catch (err) {
                alertError(err.message);
            }
        });

        async function handleEditCategoria(id) {
            const cat = catCache.get(String(id));
            if (!cat) {
                alertError('Categoria nao encontrada.');
                return;
            }
            const modal = ensureModalEdit();
            if (!modal) {
                alertError('Modal indisponivel.');
                return;
            }
            editingId = String(id);
            clearEditAlert();
            if (inputEditNome) inputEditNome.value = cat.nome || '';
            if (selectEditTipo) {
                const tipo = ['receita', 'despesa', 'transferencia'].includes(cat.tipo) ? cat.tipo : 'despesa';
                selectEditTipo.value = tipo;
            }
            modal.show();
        }

        async function handleDeleteCategoria(id) {
            const confirmed = await confirmDel('Deseja remover esta categoria?');
            if (!confirmed) return;

            try {
                const j = await tryFetch(`categorias/${encodeURIComponent(id)}`, {
                    method: 'DELETE'
                });
                if (j.status !== 'success') throw new Error(j.message || 'Falha ao remover categoria.');

                toast('success', 'Categoria removida!');
                await load();
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'delete',
                        id: Number(id)
                    }
                }));
            } catch (err) {
                alertError(err.message);
            }
        }

        modalEditEl?.addEventListener('hidden.bs.modal', () => {
            editingId = null;
            clearEditAlert();
            formEdit?.reset();
        });

        formEdit?.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            if (!editingId) return;
            clearEditAlert();
            const submitBtn = formEdit.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            const nome = (inputEditNome?.value || '').trim();
            const tipo = (selectEditTipo?.value || '').trim();

            if (nome.length < 2) {
                showEditAlert('Informe um nome com pelo menos 2 caracteres.');
                submitBtn?.removeAttribute('disabled');
                return;
            }
            if (!['receita', 'despesa', 'transferencia'].includes(tipo)) {
                showEditAlert('Selecione um tipo valido.');
                submitBtn?.removeAttribute('disabled');
                return;
            }

            try {
                const j = await tryFetch(`categorias/${encodeURIComponent(editingId)}`, {
                    method: 'PUT',
                    body: {
                        nome,
                        tipo
                    }
                });
                if (j.status !== 'success') throw new Error(j.message || 'Falha ao atualizar categoria.');
                ensureModalEdit()?.hide();
                toast('success', 'Categoria atualizada!');
                await load();
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'update',
                        id: Number(editingId)
                    }
                }));
            } catch (err) {
                showEditAlert(err.message || 'Falha ao atualizar categoria.');
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        });

        ensureTable();
        load();
    })();
</script>