/**
 * ============================================================================
 * LUKRATO — Categorias / App (Main Application Logic)
 * ============================================================================
 * Data loading, rendering, CRUD operations, modal handling,
 * icon picker, suggestions, budget/orçamento management, and event listeners.
 * Registered as Modules.App.
 * ============================================================================
 */

import {
    CONFIG,
    STATE,
    Modules,
    Utils,
    AVAILABLE_ICONS,
    ICON_GROUPS,
    SUGGESTIONS,
    ICON_MAP,
    ICON_COLORS,
    escapeHtml,
    toastSuccess,
    toastError,
} from './state.js';

import { SubcategoriasModule } from './subcategorias.js';
import { createIconPickerModule } from './app-icon-picker.js';
import { createCategoriasActions } from './app-actions.js';
import { createCategoriasCrud } from './app-crud.js';
import { createCategoriasBudget } from './app-budget.js';
import { createCategoriasFeedback } from './app-feedback.js';
import { apiDelete, apiGet, apiPost, apiPut, getErrorMessage } from '../shared/api.js';
import { resolveCategoriesEndpoint, resolveFinanceBudgetsEndpoint } from '../api/endpoints/finance.js';

const {
    toggleIconPicker,
    closeIconPicker,
    filterIcons,
    selectIcon,
    toggleEditIconPicker,
    filterEditIcons,
    highlightSelectedIcon,
} = createIconPickerModule({
    STATE,
    Utils,
    AVAILABLE_ICONS,
    ICON_GROUPS,
});

const { renderListState, showSuccess, showError } = createCategoriasFeedback({
    STATE,
    escapeHtml,
    toastSuccess,
    toastError,
});

const {
    setButtonBusy,
    clearSearch,
    resolveAppUrl,
    refreshCategorias,
    moveCategoria,
} = createCategoriasActions({
    STATE,
    CONFIG,
    Utils,
    SubcategoriasModule,
    apiPut,
    getErrorMessage,
    toastError,
    showSuccess,
    updateRefreshButtons,
    loadAll: (...args) => loadAll(...args),
    loadCategorias: (...args) => loadCategorias(...args),
    renderCategorias: (...args) => renderCategorias(...args),
    getQueryValue: (...args) => getQueryValue(...args),
});

const {
    handleNovaCategoria,
    editarCategoria,
    handleEditarCategoria,
    excluirCategoria,
} = createCategoriasCrud({
    STATE,
    CONFIG,
    Utils,
    apiPost,
    apiPut,
    apiDelete,
    getErrorMessage,
    showSuccess,
    showError,
    setButtonBusy,
    resolveAppUrl,
    closeIconPicker,
    renderSuggestions: (...args) => renderSuggestions(...args),
    loadAll: (...args) => loadAll(...args),
    highlightSelectedIcon,
});

const { editarOrcamento } = createCategoriasBudget({
    STATE,
    CONFIG,
    Utils,
    apiPost,
    apiDelete,
    getErrorMessage,
    showSuccess,
    showError,
    getOrcamento: (...args) => getOrcamento(...args),
    loadOrcamentos: (...args) => loadOrcamentos(...args),
    renderCategorias: (...args) => renderCategorias(...args),
});

// =========================================================================
// DATA LOADING
// =========================================================================

/**
 * Carregar categorias da API
 */
async function loadCategorias() {
    try {
        const result = await apiGet(resolveCategoriesEndpoint());
        STATE.lastLoadError = null;

        // Processar resposta
        if (result.success && result.data) {
            STATE.categorias = result.data;
        } else if (Array.isArray(result.data)) {
            STATE.categorias = result.data;
        } else if (Array.isArray(result)) {
            STATE.categorias = result;
        } else if (result.categorias) {
            STATE.categorias = result.categorias;
        } else {
            STATE.categorias = [];
        }

        // Pre-populate subcategoria cache from eager-loaded data
        for (const cat of STATE.categorias) {
            if (Array.isArray(cat.subcategorias)) {
                STATE.subcategoriasCache[cat.id] = cat.subcategorias;
            }
        }

        // Nao renderizar aqui - loadAll() faz apos ambas cargas
        return true;
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
        STATE.lastLoadError = getErrorMessage(error, 'Erro ao carregar categorias. Tente novamente.');
        return false;
    }
}

/**
 * Carregar orçamentos do mês atual
 */
async function loadOrcamentos() {
    try {
        const mes = STATE.mesSelecionado;
        const ano = STATE.anoSelecionado;
        const result = await apiGet(resolveFinanceBudgetsEndpoint(), { mes, ano });
        if (result.success !== false) {
            if (Array.isArray(result.data)) {
                STATE.orcamentos = result.data;
            } else if (Array.isArray(result.data?.orcamentos)) {
                STATE.orcamentos = result.data.orcamentos;
            }
        }
        return true;
    } catch (e) {
        console.error('Erro ao carregar orcamentos:', e);
        return false;
    }
}

/**
 * Carregar tudo em paralelo
 */
async function loadAll(options = {}) {
    const { showErrorToast = false } = options;
    const page = document.querySelector('.cat-page');
    const isFirstLoad = page && !page.classList.contains('is-ready');
    STATE.isLoading = true;
    updateRefreshButtons();

    const [categoriasOk] = await Promise.all([loadCategorias(), loadOrcamentos()]);
    STATE.isLoading = false;
    renderCategorias();
    SubcategoriasModule.initSubcategoriaEvents();

    if (!categoriasOk && showErrorToast && STATE.lastLoadError) {
        showError(STATE.lastLoadError);
    }

    // Na primeira carga, revela a página (remove visibility:hidden)
    if (isFirstLoad && page) {
        requestAnimationFrame(() => page.classList.add('is-ready'));
    }
}

// Failsafe: se JS demorar, mostra a página após 3s
setTimeout(() => {
    const p = document.querySelector('.cat-page');
    if (p && !p.classList.contains('is-ready')) p.classList.add('is-ready');
}, 3000);

// =========================================================================
// HELPERS
// =========================================================================

/**
 * Obter orçamento de uma categoria pelo ID
 */
function getOrcamento(categoriaId) {
    return STATE.orcamentos.find(o => Number(o.categoria_id) === Number(categoriaId)) || null;
}

function getQueryValue() {
    return (STATE.filterQuery || '').toLowerCase().trim();
}

function getCategoriasByType(tipo) {
    return STATE.categorias.filter(cat => cat.tipo === tipo || cat.tipo === 'ambas');
}

function buildSearchMatches(query) {
    if (!query) return {};

    return STATE.categorias.reduce((acc, cat) => {
        const nome = String(cat.nome || '').toLowerCase();
        const subs = STATE.subcategoriasCache[cat.id] || cat.subcategorias || [];
        const subMatches = subs
            .filter(sub => String(sub.nome || '').toLowerCase().includes(query))
            .map(sub => sub.nome);
        const categoryMatch = nome.includes(query);

        if (categoryMatch || subMatches.length) {
            acc[cat.id] = {
                categoryMatch,
                subMatches,
            };
        }

        return acc;
    }, {});
}

function countAllSubcategorias() {
    return STATE.categorias.reduce((total, cat) => {
        const subs = STATE.subcategoriasCache[cat.id] || cat.subcategorias || [];
        return total + subs.length;
    }, 0);
}

function getBudgetedCategoryCount() {
    return new Set(
        STATE.orcamentos
            .map(item => Number(item.categoria_id))
            .filter(id => Number.isFinite(id) && id > 0)
    ).size;
}

function updateFilterSummary({ query, visibleCount, totalCount }) {
    const summary = document.getElementById('catFilterSummary');
    if (!summary) return;

    summary.classList.remove('d-none');

    if (STATE.lastLoadError && totalCount === 0) {
        summary.innerHTML = `
            <div class="cat-filter-summary-content error">
                <i data-lucide="triangle-alert"></i>
                <span>${escapeHtml(STATE.lastLoadError)}</span>
            </div>
        `;
        return;
    }

    if (!query) {
        summary.innerHTML = '';
        summary.classList.add('d-none');
        return;
    }

    const matchSourceCount = Object.values(STATE.searchMatches).filter(meta => meta.subMatches?.length > 0).length;
    summary.innerHTML = `
        <div class="cat-filter-summary-content">
            <i data-lucide="search-check"></i>
            <span>Mostrando ${visibleCount} de ${totalCount} categorias para "${escapeHtml(STATE.filterQuery.trim())}". ${matchSourceCount > 0 ? 'Cards com match em subcategoria são abertos automaticamente.' : ''}</span>
        </div>
    `;
}

function updateContextCard({ receitasTotal, despesasTotal }) {
    const query = STATE.filterQuery.trim();
    const ownCount = STATE.categorias.filter(cat => !!cat.user_id).length;
    const totalCategorias = Number(receitasTotal || 0) + Number(despesasTotal || 0);

    const clearSearchButton = document.getElementById('catClearSearchButton');
    const refreshButton = document.getElementById('catRefreshButton');

    if (clearSearchButton) {
        clearSearchButton.classList.toggle('d-none', !query);
    }

    if (refreshButton) {
        refreshButton.classList.toggle('is-busy', STATE.isRefreshing || STATE.isLoading);
    }

    document.getElementById('catTotalCount').textContent = totalCategorias;
    document.getElementById('catOwnCount').textContent = ownCount;
    document.getElementById('catSubCount').textContent = countAllSubcategorias();
    document.getElementById('catBudgetCount').textContent = getBudgetedCategoryCount();
}

function updateRefreshButtons() {
    const isBusy = STATE.isRefreshing || STATE.isLoading;
    const topButton = document.getElementById('catRefreshButton');

    document.querySelectorAll('[data-action="refresh-categorias"]').forEach(button => {
        button.disabled = isBusy;
        button.classList.toggle('is-busy', isBusy);
    });

    if (topButton) {
        const label = topButton.querySelector('span');
        if (label) {
            label.textContent = isBusy ? 'Atualizando' : 'Atualizar';
        }
    }
}

// =========================================================================
// RENDERING
// =========================================================================

/**
 * Renderizar categorias na tela
 */
function renderCategorias() {
    const q = getQueryValue();
    STATE.searchMatches = buildSearchMatches(q);

    const receitasTotal = getCategoriasByType('receita');
    const despesasTotal = getCategoriasByType('despesa');
    const receitas = filterCategoriasWithQuery(receitasTotal, q);
    const despesas = filterCategoriasWithQuery(despesasTotal, q);

    document.getElementById('receitasCount').textContent = receitas.length;
    document.getElementById('despesasCount').textContent = despesas.length;
    document.getElementById('receitasTotalCount').textContent = receitasTotal.length;
    document.getElementById('despesasTotalCount').textContent = despesasTotal.length;

    const receitasContainer = document.getElementById('receitasList');
    const despesasContainer = document.getElementById('despesasList');

    const receitasHtml = receitas.length === 0
        ? renderListState({ tipoLabel: 'receita', totalCount: receitasTotal.length, query: q })
        : receitas.map(cat => renderCategoriaItem(cat, 'receita')).join('');

    const despesasHtml = despesas.length === 0
        ? renderListState({ tipoLabel: 'despesa', totalCount: despesasTotal.length, query: q })
        : despesas.map(cat => renderCategoriaItem(cat, 'despesa')).join('');

    receitasContainer.innerHTML = receitasHtml;
    despesasContainer.innerHTML = despesasHtml;
    updateContextCard({
        receitas,
        despesas,
        receitasTotal: receitasTotal.length,
        despesasTotal: despesasTotal.length,
    });
    updateFilterSummary({
        query: q,
        visibleCount: new Set([...receitas, ...despesas].map(cat => cat.id)).size,
        totalCount: STATE.categorias.length,
    });
    updateRefreshButtons();

    renderSuggestions();
    Utils.processNewIcons();
}

/**
 * Filtrar categorias por query de busca.
 * Retorna categorias cujo nome ou subcategorias contenham a query.
 */
function filterCategoriasWithQuery(categorias, query) {
    if (!query) return categorias;
    return categorias.filter(cat => Boolean(STATE.searchMatches[cat.id]));
}

/**
 * Renderizar item de categoria como card
 */
function renderCategoriaItem(categoria, tipo) {
    const query = getQueryValue();
    const searchMeta = STATE.searchMatches[categoria.id] || { categoryMatch: false, subMatches: [] };
    const isSearchExpanded = Boolean(query && searchMeta.subMatches?.length);
    const isExpanded = STATE.expandedCategorias.has(categoria.id) || isSearchExpanded;
    const isCustom = Boolean(categoria.user_id);
    const canManage = isCustom && !categoria.is_seeded;
    const canReorder = canManage && !query;

    // Remover emoji se presente no nome (legacy)
    const displayName = categoria.nome.replace(/[\u{1F300}-\u{1F9FF}]\s*/gu, '').trim() || categoria.nome;

    // Prioridade: icone do banco → iconMap por nome → fallback 'tag'
    const lucideIcon = categoria.icone || ICON_MAP[displayName.toLowerCase()] || 'tag';
    const iconColor = ICON_COLORS[lucideIcon] || '#f97316';
    const iconHtml = `<i data-lucide="${lucideIcon}" style="color:${iconColor}"></i>`;

    // Seção de orçamento (apenas despesas)
    let budgetHtml = '';
    if (tipo === 'despesa') {
        const orc = getOrcamento(categoria.id);
        if (orc) {
            const pct = Math.round(orc.percentual || 0);
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            budgetHtml = `
                <div class="cat-card-budget has-budget ${statusClass}" onclick="categoriasManager.editarOrcamento(${categoria.id}, event)" title="Clique para editar orçamento">
                    <div class="cat-budget-info">
                        <span class="cat-budget-text">${Utils.formatCurrency(orc.gasto_real)} / ${Utils.formatCurrency(orc.valor_limite)}</span>
                        <span class="cat-budget-pct ${statusClass}">${pct}%</span>
                    </div>
                    <div class="cat-budget-bar">
                        <div class="cat-budget-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                </div>`;
        } else {
            budgetHtml = `
                <div class="cat-card-budget no-budget" onclick="categoriasManager.editarOrcamento(${categoria.id}, event)" title="Defina quanto deseja gastar no máximo por mês nesta categoria">
                    <i data-lucide="pie-chart"></i>
                    <span>Definir orçamento mensal</span>
                </div>`;
        }
    }

    // Preview chips de subcategorias (mostrar até 3 quando fechado)
    const subs = STATE.subcategoriasCache[categoria.id] || categoria.subcategorias || [];
    const previewSource = searchMeta.subMatches?.length
        ? [...subs].sort((a, b) => Number(b.nome.toLowerCase().includes(query)) - Number(a.nome.toLowerCase().includes(query)))
        : subs;
    let previewHtml = '';
    if (!isExpanded && subs.length > 0) {
        const previewSubs = previewSource.slice(0, 3);
        const remaining = subs.length - previewSubs.length;
        previewHtml = `
            <div class="subcat-preview" data-expand-cat="${categoria.id}">
                ${previewSubs.map(sub => {
            const sIcon = sub.icone || 'tag';
            const sColor = ICON_COLORS[sIcon] || '#94a3b8';
            const isMatch = Boolean(query && sub.nome.toLowerCase().includes(query));
            return `<span class="subcat-chip ${isMatch ? 'match-highlight' : ''}" title="${escapeHtml(sub.nome)}"><i data-lucide="${sIcon}" style="color:${sColor}"></i>${escapeHtml(sub.nome)}</span>`;
        }).join('')}
                ${remaining > 0 ? `<span class="subcat-chip more">+${remaining}</span>` : ''}
            </div>`;
    }

    const actionsHtml = canManage
        ? `
            <button type="button" class="cat-card-btn reorder"
                    onclick="categoriasManager.moveCategoria(${categoria.id}, 'up', '${tipo}')"
                    title="${query ? 'Limpe a busca para reordenar' : 'Mover para cima'}"
                    ${canReorder ? '' : 'disabled'}>
                <i data-lucide="chevron-up"></i>
            </button>
            <button type="button" class="cat-card-btn reorder"
                    onclick="categoriasManager.moveCategoria(${categoria.id}, 'down', '${tipo}')"
                    title="${query ? 'Limpe a busca para reordenar' : 'Mover para baixo'}"
                    ${canReorder ? '' : 'disabled'}>
                <i data-lucide="chevron-down"></i>
            </button>
            <button type="button" class="cat-card-btn edit"
                    onclick="categoriasManager.editarCategoria(${categoria.id})"
                    title="Editar">
                <i data-lucide="pen"></i>
            </button>
            <button type="button" class="cat-card-btn delete"
                    onclick="categoriasManager.excluirCategoria(${categoria.id})"
                    title="Excluir">
                <i data-lucide="trash-2"></i>
            </button>
        `
        : '';

    return `
        <div class="cat-card ${tipo} ${isCustom ? 'is-custom' : 'is-default'} ${searchMeta.subMatches?.length ? 'match-subcat' : ''}" data-id="${categoria.id}">
            <div class="cat-card-header">
                <div class="cat-card-main" data-expand-cat="${categoria.id}" role="button" tabindex="0"
                     title="Ver subcategorias">
                    <div class="cat-card-icon ${tipo}">
                        ${iconHtml}
                    </div>
                    <span class="cat-card-name">${escapeHtml(displayName)}</span>
                    ${SubcategoriasModule.renderExpandButton(categoria.id)}
                </div>
                <div class="cat-card-actions">
                    ${actionsHtml}
                </div>
            </div>
            ${previewHtml}
            ${budgetHtml}
            ${SubcategoriasModule.renderAccordionPanel(categoria.id)}
        </div>
    `;
}

// =========================================================================
// SUGGESTIONS
// =========================================================================

/**
 * Renderizar sugestões baseadas no tipo selecionado
 */
function renderSuggestions() {
    const container = document.getElementById('suggestionsChips');
    if (!container) return;

    const tipo = document.querySelector('input[name="tipo"]:checked')?.value || 'despesa';
    const items = SUGGESTIONS[tipo] || [];

    // Verificar quais já existem
    const existingNames = STATE.categorias
        .filter(c => c.tipo === tipo || c.tipo === 'ambas')
        .map(c => c.nome.toLowerCase());

    container.innerHTML = items.map(item => {
        const isUsed = existingNames.includes(item.nome.toLowerCase());
        return `
            <button type="button" class="suggestion-chip ${isUsed ? 'used' : ''}"
                data-nome="${item.nome}" data-icone="${item.icone}" data-tipo="${tipo}"
                ${isUsed ? 'disabled title="Já criada"' : ''}>
                <i data-lucide="${item.icone}"></i>
                <span>${item.nome}</span>
            </button>
        `;
    }).join('');

    // Event listeners
    container.querySelectorAll('.suggestion-chip:not(.used)').forEach(chip => {
        chip.addEventListener('click', () => {
            const nome = chip.dataset.nome;
            const icone = chip.dataset.icone;

            // Preencher formulário
            const nomeInput = document.getElementById('catNome');
            if (nomeInput) nomeInput.value = nome;

            // Selecionar ícone
            selectIcon(icone);

            // Efeito visual no chip
            chip.style.transform = 'scale(0.95)';
            chip.style.background = 'color-mix(in srgb, var(--color-primary) 20%, transparent)';
            setTimeout(() => {
                chip.style.transform = '';
                chip.style.background = '';
            }, 300);

            // Focus no botão de submit
            const submitBtn = document.querySelector('.create-submit-btn');
            if (submitBtn) submitBtn.focus();
        });
    });
}

// =========================================================================
// EVENT LISTENERS
// =========================================================================

export const EventListeners = {
    init() {
        // Formulário de nova categoria
        const formNova = document.getElementById('formNova');
        if (formNova) {
            formNova.addEventListener('submit', (e) => {
                e.preventDefault();
                handleNovaCategoria(e.target);
            });
        }

        // Formulário de edição
        const formEdit = document.getElementById('formEditCategoria');
        if (formEdit) {
            formEdit.addEventListener('submit', (e) => {
                e.preventDefault();
                handleEditarCategoria(e.target);
            });
        }

        // Tipo toggle → atualizar sugestões
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', () => {
                renderSuggestions();
                Utils.processNewIcons();
            });
        });

        // Icon Picker — Create form
        const btnIconPicker = document.getElementById('btnIconPicker');
        const btnCloseIconPicker = document.getElementById('btnCloseIconPicker');
        const iconSearchInput = document.getElementById('iconSearchInput');

        if (btnIconPicker) {
            btnIconPicker.addEventListener('click', () => toggleIconPicker());
        }
        if (btnCloseIconPicker) {
            btnCloseIconPicker.addEventListener('click', () => closeIconPicker());
        }
        if (iconSearchInput) {
            iconSearchInput.addEventListener('input', (e) => filterIcons(e.target.value));
        }

        // Icon Picker — Edit modal
        const btnEditIconPicker = document.getElementById('btnEditIconPicker');
        const editIconSearchInput = document.getElementById('editIconSearchInput');

        if (btnEditIconPicker) {
            btnEditIconPicker.addEventListener('click', () => toggleEditIconPicker());
        }
        if (editIconSearchInput) {
            editIconSearchInput.addEventListener('input', (e) => filterEditIcons(e.target.value));
        }

        // Render sugestões iniciais
        renderSuggestions();
        Utils.processNewIcons();

        document.addEventListener('click', (e) => {
            const actionTarget = e.target.closest('[data-action]');
            if (!actionTarget || !actionTarget.closest('.cat-page')) return;

            const action = actionTarget.dataset.action;
            if (action === 'refresh-categorias') {
                e.preventDefault();
                refreshCategorias();
            }

            if (action === 'clear-categoria-search') {
                e.preventDefault();
                clearSearch();
            }
        });

        // ── Search / Filter ──────────────────────────────────────────────
        const searchInput = document.getElementById('catSearchInput');
        const searchClear = document.getElementById('catSearchClear');
        let _searchTimer = null;

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(_searchTimer);
                _searchTimer = setTimeout(() => {
                    STATE.filterQuery = searchInput.value;
                    if (searchClear) searchClear.classList.toggle('d-none', !searchInput.value);
                    renderCategorias();
                    SubcategoriasModule.initSubcategoriaEvents();
                }, 200);
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                clearSearch();
            });
        }

        // Escutar mudança de mês do header global
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month; // "2026-02"
            if (ym) {
                const [y, m] = ym.split('-').map(Number);
                STATE.mesSelecionado = m;
                STATE.anoSelecionado = y;
                loadOrcamentos().then(() => renderCategorias());
            }
        });
    }
};

// =========================================================================
// CATEGORIAS MANAGER — Public API (exposed to window)
// =========================================================================

export const CategoriasManager = {
    init() {
        Utils.syncMesFromHeader();
        EventListeners.init();
        loadAll();
    },

    // Methods referenced by onclick="" in rendered HTML
    editarCategoria,
    excluirCategoria,
    editarOrcamento,
    moveCategoria,
    refreshCategorias,

    // Expose for programmatic access
    loadAll,
    loadCategorias,
    loadOrcamentos,
    renderCategorias,
};

// Register in Modules
Modules.App = CategoriasManager;
