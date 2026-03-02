/**
 * ============================================================================
 * LUKRATO — Categorias / Subcategorias Module
 * ============================================================================
 * Accordion expand/collapse, subcategoria CRUD inside the edit modal,
 * lazy-loading + caching, and event delegation.
 * Imported by app.js — never runs standalone.
 * ============================================================================
 */

import {
    CONFIG, STATE, Modules, Utils,
    AVAILABLE_ICONS, ICON_COLORS,
    escapeHtml, toastSuccess, toastError,
} from './state.js';

// =========================================================================
// API HELPERS
// =========================================================================

async function fetchSubcategorias(categoriaId) {
    const res = await fetch(`${CONFIG.API_URL}categorias/${categoriaId}/subcategorias`);
    if (!res.ok) throw new Error('Erro ao carregar subcategorias');
    const json = await res.json();
    const subs = json?.data?.subcategorias ?? json?.data ?? [];
    STATE.subcategoriasCache[categoriaId] = subs;
    return subs;
}

async function apiCreateSubcategoria(parentId, data) {
    const res = await fetch(`${CONFIG.API_URL}categorias/${parentId}/subcategorias`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': Utils.getCsrfToken() },
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Erro ao criar subcategoria');
    }
    return res.json();
}

async function apiUpdateSubcategoria(id, data) {
    const res = await fetch(`${CONFIG.API_URL}subcategorias/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': Utils.getCsrfToken() },
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Erro ao atualizar subcategoria');
    }
    return res.json();
}

async function apiDeleteSubcategoria(id) {
    const res = await fetch(`${CONFIG.API_URL}subcategorias/${id}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': Utils.getCsrfToken() },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Erro ao excluir subcategoria');
    }
}

// =========================================================================
// ACCORDION — Card expand/collapse
// =========================================================================

/**
 * Gera o botão de expand (chevron) para o card de categoria.
 */
export function renderExpandButton(categoriaId) {
    const isOpen = STATE.expandedCategorias.has(categoriaId);
    return `
        <button type="button"
                class="cat-card-btn expand ${isOpen ? 'is-open' : ''}"
                data-expand-cat="${categoriaId}"
                title="Ver subcategorias"
                aria-expanded="${isOpen}">
            <i data-lucide="chevron-down"></i>
        </button>`;
}

/**
 * Gera o painel oculto que receberá a lista de subcategorias.
 */
export function renderAccordionPanel(categoriaId) {
    const isOpen = STATE.expandedCategorias.has(categoriaId);
    return `
        <div class="cat-subcategorias-panel ${isOpen ? 'is-open' : ''}"
             id="subcat-panel-${categoriaId}"
             aria-hidden="${!isOpen}">
            <div class="subcat-panel-inner">
                <div class="subcat-list" id="subcat-list-${categoriaId}">
                    ${isOpen ? renderSubcategoriasList(categoriaId) : '<div class="subcat-loading"><i data-lucide="loader"></i> Carregando...</div>'}
                </div>
            </div>
        </div>`;
}

/**
 * Alterna o estado do acordeão de um card de categoria.
 */
async function toggleAccordion(categoriaId) {
    const panel = document.getElementById(`subcat-panel-${categoriaId}`);
    const btn = document.querySelector(`[data-expand-cat="${categoriaId}"]`);
    if (!panel) return;

    const isOpen = STATE.expandedCategorias.has(categoriaId);

    if (isOpen) {
        // Fechar
        STATE.expandedCategorias.delete(categoriaId);
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        btn?.classList.remove('is-open');
        btn?.setAttribute('aria-expanded', 'false');
    } else {
        // Abrir — lazy-load
        STATE.expandedCategorias.add(categoriaId);
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        btn?.classList.add('is-open');
        btn?.setAttribute('aria-expanded', 'true');

        // Fetch se não estiver em cache
        if (!STATE.subcategoriasCache[categoriaId]) {
            const listEl = document.getElementById(`subcat-list-${categoriaId}`);
            if (listEl) listEl.innerHTML = '<div class="subcat-loading"><i data-lucide="loader"></i> Carregando...</div>';
            Utils.processNewIcons();
            try {
                await fetchSubcategorias(categoriaId);
            } catch {
                if (listEl) listEl.innerHTML = '<div class="subcat-empty">Erro ao carregar</div>';
                return;
            }
        }
        refreshSubcategoriasList(categoriaId);
    }
}

// =========================================================================
// RENDERING — Subcategorias list (inside accordion panel)
// =========================================================================

function renderSubcategoriasList(categoriaId) {
    const subs = STATE.subcategoriasCache[categoriaId] || [];
    if (subs.length === 0) {
        return '<div class="subcat-empty"><i data-lucide="inbox"></i> Nenhuma subcategoria</div>';
    }
    return subs.map(sub => renderSubcategoriaItem(sub)).join('');
}

function renderSubcategoriaItem(sub) {
    const icon = sub.icone || 'tag';
    const color = ICON_COLORS[icon] || '#94a3b8';
    const isOwn = !!sub.user_id; // só pode editar/excluir se for do usuário

    return `
        <div class="subcat-item" data-subcat-id="${sub.id}">
            <div class="subcat-item-icon" style="color:${color}">
                <i data-lucide="${icon}"></i>
            </div>
            <span class="subcat-item-name">${escapeHtml(sub.nome)}</span>
            ${isOwn ? `
            <div class="subcat-item-actions">
                <button type="button" class="subcat-btn edit" data-edit-subcat="${sub.id}" title="Editar">
                    <i data-lucide="pen"></i>
                </button>
                <button type="button" class="subcat-btn delete" data-delete-subcat="${sub.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                </button>
            </div>` : '<span class="subcat-badge-global">padrão</span>'}
        </div>`;
}

function refreshSubcategoriasList(categoriaId) {
    const listEl = document.getElementById(`subcat-list-${categoriaId}`);
    if (!listEl) return;
    listEl.innerHTML = renderSubcategoriasList(categoriaId);
    Utils.processNewIcons();
}

// =========================================================================
// EDIT MODAL — Subcategorias section
// =========================================================================

/**
 * Popula a seção de subcategorias dentro do modal de edição da categoria.
 * Chamado por app.js quando `editarCategoria()` abre o modal.
 */
export async function renderEditModalSubcategorias(categoriaId) {
    const container = document.getElementById('editSubcategoriasSection');
    if (!container) return;

    container.style.display = '';
    const listEl = document.getElementById('editSubcategoriasList');
    const countEl = document.getElementById('editSubcategoriasCount');

    // Carregar
    if (listEl) listEl.innerHTML = '<div class="subcat-loading"><i data-lucide="loader"></i></div>';
    Utils.processNewIcons();

    try {
        const subs = await fetchSubcategorias(categoriaId);
        if (countEl) countEl.textContent = subs.length;

        if (listEl) {
            if (subs.length === 0) {
                listEl.innerHTML = '<div class="subcat-empty-modal">Nenhuma subcategoria ainda</div>';
            } else {
                listEl.innerHTML = subs.map(sub => renderModalSubcategoriaItem(sub)).join('');
            }
        }
    } catch {
        if (listEl) listEl.innerHTML = '<div class="subcat-empty-modal">Erro ao carregar</div>';
    }

    // Limpar form de criação
    const nomeInput = document.getElementById('newSubcategoriaNome');
    if (nomeInput) nomeInput.value = '';
    STATE.editSubcategoriaIcon = '';
    const iconPreview = document.getElementById('newSubcategoriaIconPreview');
    if (iconPreview) {
        iconPreview.innerHTML = '<i data-lucide="tag"></i>';
    }

    Utils.processNewIcons();
}

function renderModalSubcategoriaItem(sub) {
    const icon = sub.icone || 'tag';
    const color = ICON_COLORS[icon] || '#94a3b8';
    const isOwn = !!sub.user_id;

    return `
        <div class="edit-subcat-item" data-subcat-id="${sub.id}">
            <div class="edit-subcat-icon" style="color:${color}">
                <i data-lucide="${icon}"></i>
            </div>
            <span class="edit-subcat-name">${escapeHtml(sub.nome)}</span>
            ${isOwn ? `
            <div class="edit-subcat-actions">
                <button type="button" class="subcat-btn edit" data-modal-edit-subcat="${sub.id}" title="Editar">
                    <i data-lucide="pen"></i>
                </button>
                <button type="button" class="subcat-btn delete" data-modal-delete-subcat="${sub.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                </button>
            </div>` : '<span class="subcat-badge-global">padrão</span>'}
        </div>`;
}

// =========================================================================
// CRUD — Create / Edit / Delete (inside edit modal)
// =========================================================================

async function handleAddSubcategoria() {
    const catId = STATE.categoriaEmEdicao?.id;
    if (!catId) return;

    const nomeInput = document.getElementById('newSubcategoriaNome');
    const nome = (nomeInput?.value || '').trim();
    if (!nome || nome.length < 2) {
        toastError('Nome deve ter pelo menos 2 caracteres');
        nomeInput?.focus();
        return;
    }

    const icone = STATE.editSubcategoriaIcon || null;

    try {
        await apiCreateSubcategoria(catId, { nome, icone });
        toastSuccess('Subcategoria criada!');
        if (nomeInput) nomeInput.value = '';
        STATE.editSubcategoriaIcon = '';
        const iconPreview = document.getElementById('newSubcategoriaIconPreview');
        if (iconPreview) {
            iconPreview.innerHTML = '<i data-lucide="tag"></i>';
            Utils.processNewIcons();
        }
        // Invalidar cache e re-renderizar
        delete STATE.subcategoriasCache[catId];
        await renderEditModalSubcategorias(catId);
    } catch (err) {
        toastError(err.message);
    }
}

async function handleEditSubcategoriaInModal(subcatId) {
    const catId = STATE.categoriaEmEdicao?.id;
    if (!catId) return;

    const subs = STATE.subcategoriasCache[catId] || [];
    const sub = subs.find(s => s.id === subcatId);
    if (!sub) return;

    const { value: nome } = await Swal.fire({
        title: 'Editar subcategoria',
        input: 'text',
        inputLabel: 'Nome',
        inputValue: sub.nome,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        inputValidator: (v) => {
            if (!v || v.trim().length < 2) return 'Nome deve ter pelo menos 2 caracteres';
        }
    });

    if (!nome) return;

    try {
        await apiUpdateSubcategoria(subcatId, { nome: nome.trim() });
        toastSuccess('Subcategoria atualizada!');
        delete STATE.subcategoriasCache[catId];
        await renderEditModalSubcategorias(catId);
    } catch (err) {
        toastError(err.message);
    }
}

async function handleDeleteSubcategoriaInModal(subcatId) {
    const catId = STATE.categoriaEmEdicao?.id;
    if (!catId) return;

    const subs = STATE.subcategoriasCache[catId] || [];
    const sub = subs.find(s => s.id === subcatId);
    if (!sub) return;

    const confirm = await Swal.fire({
        title: 'Excluir subcategoria',
        html: `Deseja excluir <strong>${escapeHtml(sub.nome)}</strong>?<br/><small class="text-muted">Lançamentos vinculados perderão a subcategoria.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
    });

    if (!confirm.isConfirmed) return;

    try {
        await apiDeleteSubcategoria(subcatId);
        toastSuccess('Subcategoria excluída!');
        delete STATE.subcategoriasCache[catId];
        await renderEditModalSubcategorias(catId);
    } catch (err) {
        toastError(err.message);
    }
}

// Delete from accordion card (same logic, different refresh)
async function handleDeleteSubcategoriaInCard(subcatId) {
    // Find parent category
    let parentId = null;
    for (const [catId, subs] of Object.entries(STATE.subcategoriasCache)) {
        if (subs.find(s => s.id === subcatId)) { parentId = Number(catId); break; }
    }
    if (!parentId) return;

    const sub = STATE.subcategoriasCache[parentId]?.find(s => s.id === subcatId);
    if (!sub) return;

    const confirm = await Swal.fire({
        title: 'Excluir subcategoria',
        html: `Deseja excluir <strong>${escapeHtml(sub.nome)}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
    });

    if (!confirm.isConfirmed) return;

    try {
        await apiDeleteSubcategoria(subcatId);
        toastSuccess('Subcategoria excluída!');
        delete STATE.subcategoriasCache[parentId];
        await fetchSubcategorias(parentId);
        refreshSubcategoriasList(parentId);
    } catch (err) {
        toastError(err.message);
    }
}

// =========================================================================
// ICON PICKER — Subcategoria (inside edit modal)
// =========================================================================

let _subcatIconPickerReady = false;

function toggleSubcatIconPicker() {
    const panel = document.getElementById('newSubcategoriaIconPanel');
    if (!panel) return;

    const isHidden = panel.classList.contains('d-none');
    if (isHidden) {
        // Lazy-init grid
        if (!_subcatIconPickerReady) {
            const grid = document.getElementById('newSubcategoriaIconGrid');
            if (grid) {
                grid.innerHTML = AVAILABLE_ICONS.map(ic => `
                    <button type="button" class="icon-grid-item" data-subcat-icon="${ic.name}"
                            title="${ic.label}" aria-label="${ic.label}">
                        <i data-lucide="${ic.name}"></i>
                    </button>
                `).join('');
                Utils.processNewIcons();
            }
            _subcatIconPickerReady = true;
        }
        panel.classList.remove('d-none');
    } else {
        panel.classList.add('d-none');
    }
}

function selectSubcatIcon(iconName) {
    STATE.editSubcategoriaIcon = iconName;
    const preview = document.getElementById('newSubcategoriaIconPreview');
    if (preview) {
        preview.innerHTML = `<i data-lucide="${iconName}"></i>`;
        Utils.processNewIcons();
    }
    // Highlight
    document.querySelectorAll('#newSubcategoriaIconGrid .icon-grid-item').forEach(btn => {
        btn.classList.toggle('selected', btn.dataset.subcatIcon === iconName);
    });
    // Close panel
    const panel = document.getElementById('newSubcategoriaIconPanel');
    if (panel) panel.classList.add('d-none');
}

function filterSubcatIcons(query) {
    const q = (query || '').toLowerCase();
    document.querySelectorAll('#newSubcategoriaIconGrid .icon-grid-item').forEach(btn => {
        const label = (btn.title || '').toLowerCase();
        const name = (btn.dataset.subcatIcon || '').toLowerCase();
        btn.style.display = (!q || label.includes(q) || name.includes(q)) ? '' : 'none';
    });
}

// =========================================================================
// EVENT DELEGATION
// =========================================================================

export function initSubcategoriaEvents() {
    // Accordion expand buttons — delegated on category lists
    ['receitasList', 'despesasList'].forEach(containerId => {
        const el = document.getElementById(containerId);
        if (!el || el.dataset.subcatEventsAttached) return;
        el.dataset.subcatEventsAttached = '1';

        el.addEventListener('click', (e) => {
            // Expand button
            const expandBtn = e.target.closest('[data-expand-cat]');
            if (expandBtn) {
                e.stopPropagation();
                toggleAccordion(Number(expandBtn.dataset.expandCat));
                return;
            }
            // Delete subcategoria from card
            const deleteBtn = e.target.closest('[data-delete-subcat]');
            if (deleteBtn) {
                e.stopPropagation();
                handleDeleteSubcategoriaInCard(Number(deleteBtn.dataset.deleteSubcat));
                return;
            }
        });
    });

    // Edit modal — subcategoria events (delegated on the section)
    const editSection = document.getElementById('editSubcategoriasSection');
    if (editSection && !editSection.dataset.subcatEventsAttached) {
        editSection.dataset.subcatEventsAttached = '1';

        // Add button
        const addBtn = document.getElementById('btnAddSubcategoria');
        if (addBtn) addBtn.addEventListener('click', () => handleAddSubcategoria());

        // Enter key on name input
        const nomeInput = document.getElementById('newSubcategoriaNome');
        if (nomeInput) {
            nomeInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); handleAddSubcategoria(); }
            });
        }

        // Icon picker toggle
        const iconBtn = document.getElementById('btnSubcatIconPicker');
        if (iconBtn) iconBtn.addEventListener('click', () => toggleSubcatIconPicker());

        // Icon search
        const iconSearch = document.getElementById('newSubcategoriaIconSearch');
        if (iconSearch) iconSearch.addEventListener('input', (e) => filterSubcatIcons(e.target.value));

        // Icon selection + edit/delete (delegated)
        editSection.addEventListener('click', (e) => {
            // Icon grid selection
            const iconItem = e.target.closest('[data-subcat-icon]');
            if (iconItem) { selectSubcatIcon(iconItem.dataset.subcatIcon); return; }

            // Edit subcategoria
            const editBtn = e.target.closest('[data-modal-edit-subcat]');
            if (editBtn) { handleEditSubcategoriaInModal(Number(editBtn.dataset.modalEditSubcat)); return; }

            // Delete subcategoria
            const deleteBtn = e.target.closest('[data-modal-delete-subcat]');
            if (deleteBtn) { handleDeleteSubcategoriaInModal(Number(deleteBtn.dataset.modalDeleteSubcat)); return; }
        });
    }
}

// =========================================================================
// PUBLIC API
// =========================================================================

export const SubcategoriasModule = {
    renderExpandButton,
    renderAccordionPanel,
    renderEditModalSubcategorias,
    initSubcategoriaEvents,
    toggleAccordion,
    invalidateCache(categoriaId) {
        if (categoriaId) {
            delete STATE.subcategoriasCache[categoriaId];
        } else {
            STATE.subcategoriasCache = {};
        }
    },
};

Modules.Subcategorias = SubcategoriasModule;
