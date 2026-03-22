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
import { apiDelete, apiGet, apiPost, apiPut, getErrorMessage } from '../shared/api.js';

// =========================================================================
// DATA LOADING
// =========================================================================

/**
 * Carregar categorias da API
 */
async function loadCategorias() {
    try {
        const result = await apiGet(`${CONFIG.API_URL}categorias`);
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
        const result = await apiGet(`${CONFIG.API_URL}financas/orcamentos`, { mes, ano });
        if (result.success !== false && Array.isArray(result.data)) {
            STATE.orcamentos = result.data;
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

function getMonthReferenceLabel() {
    const baseDate = new Date(STATE.anoSelecionado, Math.max(0, STATE.mesSelecionado - 1), 1);
    const label = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(baseDate);
    return label.charAt(0).toUpperCase() + label.slice(1);
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

function renderContextChip(label, tone = 'default') {
    return `<span class="cat-context-chip ${tone}">${escapeHtml(label)}</span>`;
}

function updateFilterSummary({ query, visibleCount, totalCount }) {
    const summary = document.getElementById('catFilterSummary');
    if (!summary) return;

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
        summary.innerHTML = `
            <div class="cat-filter-summary-content">
                <i data-lucide="info"></i>
                <span>As ações de reordenação valem para categorias criadas por você. Os limites mensais seguem ${escapeHtml(getMonthReferenceLabel())}.</span>
            </div>
        `;
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

function updateContextCard({ receitas, despesas, receitasTotal, despesasTotal }) {
    const totalCategorias = STATE.categorias.length;
    const visibleCount = new Set([...receitas, ...despesas].map(cat => cat.id)).size;
    const query = STATE.filterQuery.trim();
    const defaultCount = STATE.categorias.filter(cat => !cat.user_id).length;
    const ownCount = STATE.categorias.filter(cat => !!cat.user_id).length;

    const title = document.getElementById('catContextTitle');
    const description = document.getElementById('catContextDescription');
    const chips = document.getElementById('catContextChips');
    const clearSearchButton = document.getElementById('catClearSearchButton');
    const refreshButton = document.getElementById('catRefreshButton');

    if (title) {
        title.textContent = query
            ? `${visibleCount} resultado(s) para "${STATE.filterQuery.trim()}"`
            : `${totalCategorias} categorias para organizar receitas e despesas`;
    }

    if (description) {
        if (STATE.lastLoadError && totalCategorias === 0) {
            description.textContent = STATE.lastLoadError;
        } else if (query) {
            description.textContent = 'A busca considera nome da categoria e nome da subcategoria, com destaque automático nos matches.';
        } else {
            description.textContent = `Os limites mensais e o gasto atual exibidos abaixo consideram ${getMonthReferenceLabel()}.`;
        }
    }

    if (chips) {
        const contextualChips = [
            renderContextChip(`Orçamentos: ${getMonthReferenceLabel()}`, 'info'),
            renderContextChip(`Receitas ${receitas.length}/${receitasTotal}`, 'success'),
            renderContextChip(`Despesas ${despesas.length}/${despesasTotal}`, 'danger'),
        ];

        if (defaultCount > 0) {
            contextualChips.push(renderContextChip(`${defaultCount} padrão`, 'neutral'));
        }

        if (query) {
            contextualChips.push(renderContextChip(`Busca: ${STATE.filterQuery.trim()}`, 'accent'));
        }

        if (STATE.filterQuery.trim()) {
            contextualChips.push(renderContextChip('Reordenação pausada durante a busca', 'warning'));
        }

        chips.innerHTML = contextualChips.join('');
    }

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
            label.textContent = isBusy ? 'Atualizando...' : 'Atualizar dados';
        }
    }
}

function renderListState({ tipoLabel, totalCount, query }) {
    if (STATE.lastLoadError && STATE.categorias.length === 0) {
        return `
            <div class="category-state error">
                <i data-lucide="triangle-alert"></i>
                <p>${escapeHtml(STATE.lastLoadError)}</p>
                <button type="button" class="category-state-btn" data-action="refresh-categorias">Tentar novamente</button>
            </div>
        `;
    }

    if (query) {
        return `
            <div class="category-state empty">
                <i data-lucide="search-x"></i>
                <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} corresponde à busca atual.</p>
                <button type="button" class="category-state-btn" data-action="clear-categoria-search">Limpar busca</button>
            </div>
        `;
    }

    if (totalCount === 0) {
        return `
            <div class="category-state empty">
                <i data-lucide="inbox"></i>
                <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} cadastrada.</p>
            </div>
        `;
    }

    return `
        <div class="category-state empty">
            <i data-lucide="inbox"></i>
            <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} disponível neste momento.</p>
        </div>
    `;
}

/**
 * Mostrar mensagem de sucesso
 */
function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        toastSuccess(message);
    }
}

/**
 * Mostrar mensagem de erro
 */
function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: message,
            confirmButtonText: 'OK'
        });
    } else {
        toastError(message);
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

    const badges = [
        `<span class="cat-card-badge ${isCustom ? 'own' : 'default'}">${isCustom ? 'Sua' : 'Padrão'}</span>`,
        searchMeta.subMatches?.length
            ? `<span class="cat-card-badge search">Match em ${searchMeta.subMatches.length} subcategoria(s)</span>`
            : '',
    ].filter(Boolean).join('');

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

/**
 * Renderizar lista de despesas
 */
function renderListaDespesas(despesas) {
    const container = document.getElementById('despesasList');

    if (despesas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <p>Nenhuma categoria de despesa cadastrada</p>
            </div>
        `;
        return;
    }

    container.innerHTML = despesas.map(cat => renderCategoriaItem(cat, 'despesa')).join('');
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
// ICON PICKER — CREATE FORM
// =========================================================================

/**
 * Retorna os ícones usados recentemente (localStorage).
 */
function getRecentIcons() {
    try {
        return JSON.parse(localStorage.getItem('lk_recent_icons') || '[]').slice(0, 8);
    } catch { return []; }
}

function pushRecentIcon(iconName) {
    const recent = getRecentIcons().filter(n => n !== iconName);
    recent.unshift(iconName);
    localStorage.setItem('lk_recent_icons', JSON.stringify(recent.slice(0, 8)));
}

/**
 * Renderizar grid de ícones agrupados em um container
 */
function renderIconGrid(containerId, onSelect) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = '';

    // Recentes
    const recent = getRecentIcons();
    if (recent.length > 0) {
        html += `<div class="icon-group-label">Recentes</div><div class="icon-group-grid">`;
        html += recent.map(name =>
            `<button type="button" class="icon-pick-item" data-icon="${name}" title="${name}"><i data-lucide="${name}"></i></button>`
        ).join('');
        html += `</div>`;
    }

    // Grupos
    for (const group of ICON_GROUPS) {
        html += `<div class="icon-group-label">${group.label}</div><div class="icon-group-grid">`;
        html += group.icons.map(name =>
            `<button type="button" class="icon-pick-item" data-icon="${name}" title="${name}"><i data-lucide="${name}"></i></button>`
        ).join('');
        html += `</div>`;
    }

    container.innerHTML = html;

    // Event delegation (apenas uma vez por container)
    if (!container._lkDelegated) {
        container.addEventListener('click', (e) => {
            const item = e.target.closest('.icon-pick-item');
            if (!item) return;
            onSelect(item.dataset.icon);
        });
        container._lkDelegated = true;
    }

    Utils.processNewIcons();
}

/**
 * Toggle icon picker drawer
 */
function toggleIconPicker() {
    const drawer = document.getElementById('iconPickerDrawer');
    if (!drawer) return;

    // Lazy-load icon grid na primeira abertura
    if (!STATE._iconGridCreateReady) {
        renderIconGrid('iconPickerGrid', (icon) => selectIcon(icon));
        STATE._iconGridCreateReady = true;
    }

    drawer.classList.toggle('open');
    toggleIconPickerBackdrop(drawer.classList.contains('open'));
    if (drawer.classList.contains('open')) {
        const input = document.getElementById('iconSearchInput');
        if (input) { input.value = ''; input.focus(); }
        filterIcons('');
        highlightSelectedIcon('iconPickerGrid', STATE.selectedIcon);
    }
}

/**
 * Fechar icon picker
 */
function closeIconPicker() {
    const drawer = document.getElementById('iconPickerDrawer');
    if (drawer) drawer.classList.remove('open');
    toggleIconPickerBackdrop(false);
}

/**
 * Backdrop (mobile bottom-sheet)
 */
function toggleIconPickerBackdrop(show) {
    let bd = document.getElementById('iconPickerBackdrop');
    if (show && !bd) {
        bd = document.createElement('div');
        bd.id = 'iconPickerBackdrop';
        bd.className = 'icon-picker-backdrop';
        bd.addEventListener('click', closeIconPicker);
        document.body.appendChild(bd);
    }
    if (bd) bd.classList.toggle('show', show);
}

/**
 * Selecionar ícone no form de criação
 */
function selectIcon(iconName) {
    STATE.selectedIcon = iconName;
    document.getElementById('catIcone').value = iconName;
    updateIconPreview(iconName);
    highlightSelectedIcon('iconPickerGrid', iconName);
    pushRecentIcon(iconName);
    closeIconPicker();
}

/**
 * Atualizar preview do ícone no create-icon-area
 */
function updateIconPreview(iconName) {
    const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
    if (!inner) return;

    // Recria o <i> para que Lucide processe corretamente
    inner.innerHTML = `<i data-lucide="${iconName}" class="create-main-icon" id="iconPreview"></i>`;
    Utils.processNewIcons();

    // Efeito visual de troca
    const ring = document.getElementById('iconPreviewRing');
    if (ring) {
        ring.style.transform = 'scale(1.1)';
        setTimeout(() => ring.style.transform = '', 300);
    }
}

/**
 * Highlight icon selecionado no grid
 */
function highlightSelectedIcon(containerId, iconName) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.querySelectorAll('.icon-pick-item').forEach(item => {
        item.classList.toggle('selected', item.dataset.icon === iconName);
    });
}

/**
 * Filtrar ícones por busca
 */
function filterIcons(query) {
    const container = document.getElementById('iconPickerGrid');
    if (!container) return;
    const q = query.toLowerCase().trim();

    container.querySelectorAll('.icon-group-grid').forEach(grid => {
        let visibleCount = 0;
        grid.querySelectorAll('.icon-pick-item').forEach(item => {
            if (!q) { item.style.display = ''; visibleCount++; return; }
            const iconName = item.dataset.icon;
            const iconData = AVAILABLE_ICONS.find(i => i.name === iconName);
            const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
            const visible = searchText.includes(q);
            item.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });
        const label = grid.previousElementSibling;
        if (label?.classList.contains('icon-group-label')) {
            label.style.display = visibleCount > 0 || !q ? '' : 'none';
        }
        grid.style.display = visibleCount > 0 || !q ? '' : 'none';
    });
}

// =========================================================================
// ICON PICKER — EDIT MODAL
// =========================================================================

function toggleEditIconPicker() {
    const panel = document.getElementById('editIconPickerPanel');
    if (!panel) return;

    // Lazy-load icon grid na primeira abertura
    if (!STATE._iconGridEditReady) {
        renderIconGrid('editIconPickerGrid', (icon) => selectEditIcon(icon));
        STATE._iconGridEditReady = true;
    }

    panel.classList.toggle('d-none');
    if (!panel.classList.contains('d-none')) {
        const input = document.getElementById('editIconSearchInput');
        if (input) { input.value = ''; input.focus(); }
        filterEditIcons('');
        highlightSelectedIcon('editIconPickerGrid', STATE.editSelectedIcon);
    }
}

function selectEditIcon(iconName) {
    STATE.editSelectedIcon = iconName;
    document.getElementById('editCategoriaIcone').value = iconName;

    const preview = document.getElementById('editIconPreview');
    if (preview) {
        preview.innerHTML = `<i data-lucide="${iconName}"></i>`;
        Utils.processNewIcons();
    }

    highlightSelectedIcon('editIconPickerGrid', iconName);
    pushRecentIcon(iconName);

    const panel = document.getElementById('editIconPickerPanel');
    if (panel) panel.classList.add('d-none');
}

function filterEditIcons(query) {
    const container = document.getElementById('editIconPickerGrid');
    if (!container) return;
    const q = query.toLowerCase().trim();

    container.querySelectorAll('.icon-group-grid').forEach(grid => {
        let visibleCount = 0;
        grid.querySelectorAll('.icon-pick-item').forEach(item => {
            if (!q) { item.style.display = ''; visibleCount++; return; }
            const iconName = item.dataset.icon;
            const iconData = AVAILABLE_ICONS.find(i => i.name === iconName);
            const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
            const visible = searchText.includes(q);
            item.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });
        const label = grid.previousElementSibling;
        if (label?.classList.contains('icon-group-label')) {
            label.style.display = visibleCount > 0 || !q ? '' : 'none';
        }
        grid.style.display = visibleCount > 0 || !q ? '' : 'none';
    });
}

// =========================================================================
// REORDER — Move categorias up/down
// =========================================================================

function setButtonBusy(button, isBusy, busyLabel = 'Salvando...') {
    if (!button) return;

    if (isBusy) {
        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }
        button.disabled = true;
        button.classList.add('is-busy');
        button.innerHTML = `<i data-lucide="loader-circle"></i><span>${busyLabel}</span>`;
        Utils.processNewIcons();
        return;
    }

    button.disabled = false;
    button.classList.remove('is-busy');
    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
    }
    Utils.processNewIcons();
}

function clearSearch() {
    const searchInput = document.getElementById('catSearchInput');
    const searchClear = document.getElementById('catSearchClear');

    if (searchInput) searchInput.value = '';
    STATE.filterQuery = '';
    if (searchClear) searchClear.classList.add('d-none');
    renderCategorias();
    SubcategoriasModule.initSubcategoriaEvents();
}

function resolveAppUrl(path) {
    if (!path) return CONFIG.BASE_URL;
    if (/^https?:\/\//i.test(path)) return path;
    return `${CONFIG.BASE_URL}${String(path).replace(/^\/+/, '')}`;
}

async function refreshCategorias(options = {}) {
    const { silent = false } = options;

    if (STATE.isRefreshing || STATE.isLoading) return;

    STATE.isRefreshing = true;
    updateRefreshButtons();

    try {
        await loadAll({ showErrorToast: !silent });
        if (!silent && !STATE.lastLoadError) {
            showSuccess('Categorias atualizadas.');
        }
    } finally {
        STATE.isRefreshing = false;
        updateRefreshButtons();
    }
}

async function moveCategoria(categoriaId, direction, bucketType = 'despesa') {
    const categoria = STATE.categorias.find(c => c.id === categoriaId);
    if (!categoria || !categoria.user_id || categoria.is_seeded) return;

    if (getQueryValue()) {
        toastError('Limpe a busca para reordenar categorias.');
        return;
    }

    const orderedIds = STATE.categorias
        .filter(cat => (cat.tipo === bucketType || cat.tipo === 'ambas') && cat.user_id && !cat.is_seeded)
        .map(cat => Number(cat.id));

    const idx = orderedIds.indexOf(Number(categoriaId));
    if (idx < 0) return;
    if (direction === 'up' && idx === 0) return;
    if (direction === 'down' && idx === orderedIds.length - 1) return;

    const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
    [orderedIds[idx], orderedIds[swapIdx]] = [orderedIds[swapIdx], orderedIds[idx]];

    try {
        await apiPut(`${CONFIG.API_URL}categorias/reorder`, { ids: orderedIds });
        await loadCategorias();
        renderCategorias();
        SubcategoriasModule.initSubcategoriaEvents();
    } catch (error) {
        toastError(getErrorMessage(error, 'Erro ao reordenar categorias.'));
    }
}

// =========================================================================
// CRUD OPERATIONS
// =========================================================================

/**
 * Reset form state (icon, suggestions)
 */
function resetCreateForm() {
    STATE.selectedIcon = '';
    const catIcone = document.getElementById('catIcone');
    if (catIcone) catIcone.value = '';

    // Recriar preview do ícone padrão
    const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
    if (inner) {
        inner.innerHTML = `<i data-lucide="tag" class="create-main-icon" id="iconPreview"></i>`;
    }

    closeIconPicker();
    renderSuggestions();

    // Processar ícones novos
    Utils.processNewIcons();
}

/**
 * Criar nova categoria
 */
async function handleNovaCategoria(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    try {
        setButtonBusy(submitButton, true, 'Adicionando...');
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            icone: formData.get('icone') || null
        };

        const result = await apiPost(`${CONFIG.API_URL}categorias`, data);

        if (result?.success === false) {
            throw new Error(getErrorMessage({ data: result }, 'Erro ao criar categoria.'));
        }

        if (result.data?.gamification?.achievements && Array.isArray(result.data.gamification.achievements)) {
            if (typeof window.notifyMultipleAchievements === 'function') {
                window.notifyMultipleAchievements(result.data.gamification.achievements);
            }
        }

        showSuccess('Categoria criada com sucesso!');
        form.reset();
        resetCreateForm();
        await loadAll();
    } catch (error) {
        const limitInfo = error?.data?.errors;

        if (error?.status === 403 && limitInfo?.limit_reached && typeof Swal !== 'undefined') {
            const decision = await Swal.fire({
                icon: 'info',
                title: 'Limite do plano atingido',
                text: getErrorMessage(error, 'Limite do plano atingido.'),
                showCancelButton: true,
                confirmButtonText: 'Ver planos',
                cancelButtonText: 'Fechar',
            });

            if (decision.isConfirmed && limitInfo.upgrade_url) {
                window.location.href = resolveAppUrl(limitInfo.upgrade_url);
            }
            return;
        }

        console.error('Erro ao criar categoria:', error);
        showError(getErrorMessage(error, 'Erro ao criar categoria. Tente novamente.'));
    } finally {
        setButtonBusy(submitButton, false);
    }
}

/**
 * Editar categoria — abre modal
 */
function editarCategoria(id) {
    const categoria = STATE.categorias.find(c => c.id === id);
    if (!categoria || !categoria.user_id || categoria.is_seeded) return;

    STATE.categoriaEmEdicao = categoria;

    // Preencher formulário
    document.getElementById('editCategoriaNome').value = categoria.nome;
    document.getElementById('editCategoriaTipo').value = categoria.tipo;

    // Preencher ícone
    const currentIcon = categoria.icone || 'tag';
    STATE.editSelectedIcon = currentIcon;
    document.getElementById('editCategoriaIcone').value = currentIcon;
    const editPreview = document.getElementById('editIconPreview');
    if (editPreview) {
        editPreview.innerHTML = `<i data-lucide="${currentIcon}"></i>`;
        Utils.processNewIcons();
    }
    // Esconder panel de ícones
    const editIconPanel = document.getElementById('editIconPickerPanel');
    if (editIconPanel) editIconPanel.classList.add('d-none');
    highlightSelectedIcon('editIconPickerGrid', currentIcon);

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditCategoria'));
    modal.show();
}

/**
 * Salvar edição de categoria
 */
async function handleEditarCategoria(form) {
    if (!STATE.categoriaEmEdicao) return;

    const submitButton = form.querySelector('button[type="submit"]') || document.querySelector('[form="formEditCategoria"]');
    try {
        setButtonBusy(submitButton, true, 'Salvando...');
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            icone: formData.get('icone') || null
        };

        await apiPut(`${CONFIG.API_URL}categorias/${STATE.categoriaEmEdicao.id}`, data);

        showSuccess('Categoria atualizada com sucesso!');

        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCategoria'));
        modal.hide();

        await loadAll();
    } catch (error) {
        console.error('Erro ao editar categoria:', error);
        showError(getErrorMessage(error, 'Erro ao editar categoria. Tente novamente.'));
    } finally {
        setButtonBusy(submitButton, false);
    }
}

/**
 * Excluir categoria
 */
async function excluirCategoria(id) {
    const categoria = STATE.categorias.find(c => c.id === id);
    if (!categoria || !categoria.user_id || categoria.is_seeded) return;

    const confirmacao = await Swal.fire({
        title: 'Confirmar exclusao',
        html: `Deseja realmente excluir a categoria <strong>${categoria.nome}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmacao.isConfirmed) return;

    try {
        await apiDelete(`${CONFIG.API_URL}categorias/${id}`, {});
    } catch (error) {
        if (error?.status === 422 && error?.data?.errors?.confirm_delete) {
            const counts = error.data.errors.counts || {};
            const forceDelete = await Swal.fire({
                title: 'Categoria com vinculos',
                html: `
                    <p>Esta categoria ainda possui itens vinculados.</p>
                    <ul class="swal2-html-container" style="text-align:left; margin:1rem 0 0;">
                        <li>${counts.subcategorias || 0} subcategoria(s)</li>
                        <li>${counts.lancamentos || 0} lancamento(s)</li>
                    </ul>
                    <p>Se continuar, as subcategorias serao removidas e os lancamentos ficarao sem categoria.</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Excluir mesmo assim',
                cancelButtonText: 'Cancelar'
            });

            if (!forceDelete.isConfirmed) return;

            try {
                await apiDelete(`${CONFIG.API_URL}categorias/${id}`, { force: true });
            } catch (forcedError) {
                console.error('Erro ao excluir categoria:', forcedError);
                showError(getErrorMessage(forcedError, 'Erro ao excluir categoria. Pode haver lancamentos vinculados.'));
                return;
            }
        } else {
            console.error('Erro ao excluir categoria:', error);
            showError(getErrorMessage(error, 'Erro ao excluir categoria. Pode haver lancamentos vinculados.'));
            return;
        }
    }

    showSuccess('Categoria excluida com sucesso!');
    await loadAll();
}

// =========================================================================
// ORÇAMENTO (Budget) Management
// =========================================================================

/**
 * Editar/criar orçamento via Modal Bootstrap
 */
function editarOrcamento(categoriaId, event) {
    if (event) event.stopPropagation();

    const cat = STATE.categorias.find(c => c.id === categoriaId);
    if (!cat) return;

    const orc = getOrcamento(categoriaId);
    const currentValue = orc ? parseFloat(orc.valor_limite) : 0;

    // Preencher modal
    document.getElementById('orcCategoriaNome').textContent = cat.nome;
    const gastoEl = document.getElementById('orcGastoAtual');
    const gastoValorEl = document.getElementById('orcGastoValor');
    const btnRemover = document.getElementById('btnRemoverOrcamento');
    const btnText = document.getElementById('btnOrcText');
    const inputValor = document.getElementById('orcValorLimite');
    const alertEl = document.getElementById('orcAlertError');

    // Reset
    alertEl.classList.add('d-none');
    inputValor.value = currentValue > 0 ? Utils.formatOrcamentoInput(currentValue) : '';

    if (orc) {
        gastoEl.classList.remove('d-none');
        gastoValorEl.textContent = Utils.formatCurrency(orc.gasto_real);
        btnRemover.classList.remove('d-none');
        btnText.textContent = 'Atualizar';
    } else {
        gastoEl.classList.add('d-none');
        btnRemover.classList.add('d-none');
        btnText.textContent = 'Definir';
    }

    // Salvar categoriaId no form
    const form = document.getElementById('formOrcamento');
    form.dataset.categoriaId = categoriaId;

    // Eventos (remover anteriores para evitar duplicatas)
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);

    // Máscara de moeda no input
    const newInput = newForm.querySelector('#orcValorLimite');
    newInput.addEventListener('input', () => {
        Utils.applyCurrencyMask(newInput);
    });

    newForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const raw = document.getElementById('orcValorLimite').value;
        const val = Utils.parseCurrencyInput(raw);
        const errEl = document.getElementById('orcAlertError');
        if (!val || isNaN(val) || val <= 0) {
            errEl.textContent = 'Informe um valor maior que zero';
            errEl.classList.remove('d-none');
            return;
        }
        errEl.classList.add('d-none');
        await salvarOrcamento(parseInt(newForm.dataset.categoriaId), val);
        bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
    });

    // Botão remover
    const newBtnRemover = document.getElementById('btnRemoverOrcamento');
    const clonedBtn = newBtnRemover.cloneNode(true);
    newBtnRemover.parentNode.replaceChild(clonedBtn, newBtnRemover);
    clonedBtn.addEventListener('click', async () => {
        if (orc) {
            await removerOrcamento(orc.id);
            bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
        }
    });

    // Re-apontar o botão submit ao novo form
    document.getElementById('btnSalvarOrcamento').setAttribute('form', 'formOrcamento');

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalOrcamento'));
    modal.show();

    // Focar no input após abrir
    document.getElementById('modalOrcamento').addEventListener('shown.bs.modal', () => {
        document.getElementById('orcValorLimite').focus();
    }, { once: true });
}

/**
 * Salvar orçamento via API (usa o mesmo endpoint de financas)
 */
async function salvarOrcamento(categoriaId, valorLimite) {
    try {
        const mes = STATE.mesSelecionado;
        const ano = STATE.anoSelecionado;

        await apiPost(`${CONFIG.API_URL}financas/orcamentos`, {
            categoria_id: categoriaId,
            valor_limite: valorLimite,
            mes,
            ano
        });

        showSuccess('Limite atualizado!');
        await loadOrcamentos();
        renderCategorias();
    } catch (e) {
        console.error('Erro ao salvar orcamento:', e);
        showError(getErrorMessage(e, 'Erro ao salvar limite. Tente novamente.'));
    }
}

/**
 * Remover orçamento via API
 */
async function removerOrcamento(orcamentoId) {
    try {
        await apiDelete(`${CONFIG.API_URL}financas/orcamentos/${orcamentoId}`);

        showSuccess('Limite removido!');
        await loadOrcamentos();
        renderCategorias();
    } catch (e) {
        console.error('Erro ao remover orcamento:', e);
        showError(getErrorMessage(e, 'Erro ao remover limite. Tente novamente.'));
    }
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
