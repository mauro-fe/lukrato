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
    SUGGESTIONS,
    ICON_MAP,
    ICON_COLORS,
    escapeHtml,
    toastSuccess,
    toastError,
} from './state.js';

import { SubcategoriasModule } from './subcategorias.js';

// =========================================================================
// DATA LOADING
// =========================================================================

/**
 * Carregar categorias da API
 */
async function loadCategorias() {
    try {
        const response = await fetch(`${CONFIG.API_URL}categorias`);

        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }

        const result = await response.json();

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

        // Não renderizar aqui — loadAll() faz após ambas cargas
    } catch (error) {
        console.error('❌ Erro ao carregar categorias:', error);
        showError('Erro ao carregar categorias. Tente novamente.');
    }
}

/**
 * Carregar orçamentos do mês atual
 */
async function loadOrcamentos() {
    try {
        const mes = STATE.mesSelecionado;
        const ano = STATE.anoSelecionado;
        const response = await fetch(`${CONFIG.API_URL}financas/orcamentos?mes=${mes}&ano=${ano}`);
        if (!response.ok) return;
        const result = await response.json();
        if (result.success !== false && Array.isArray(result.data)) {
            STATE.orcamentos = result.data;
        }
    } catch (e) {
        console.error('Erro ao carregar orçamentos:', e);
    }
}

/**
 * Carregar tudo em paralelo
 */
async function loadAll() {
    const page = document.querySelector('.cat-page');
    const isFirstLoad = page && !page.classList.contains('is-ready');

    await Promise.all([loadCategorias(), loadOrcamentos()]);
    renderCategorias();
    SubcategoriasModule.initSubcategoriaEvents();

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
    const receitas = STATE.categorias.filter(c => c.tipo === 'receita');
    const despesas = STATE.categorias.filter(c => c.tipo === 'despesa');

    // Atualizar contadores
    document.getElementById('receitasCount').textContent = receitas.length;
    document.getElementById('despesasCount').textContent = despesas.length;

    // Preparar HTML antes de inserir no DOM (evita flash de conteúdo sem ícones)
    const receitasContainer = document.getElementById('receitasList');
    const despesasContainer = document.getElementById('despesasList');

    // Construir HTML em memória
    const receitasHtml = receitas.length === 0
        ? '<div class="empty-state"><i data-lucide="inbox"></i><p>Nenhuma categoria de receita cadastrada</p></div>'
        : receitas.map(cat => renderCategoriaItem(cat, 'receita')).join('');

    const despesasHtml = despesas.length === 0
        ? '<div class="empty-state"><i data-lucide="inbox"></i><p>Nenhuma categoria de despesa cadastrada</p></div>'
        : despesas.map(cat => renderCategoriaItem(cat, 'despesa')).join('');

    // Inserir tudo no DOM de uma vez
    receitasContainer.innerHTML = receitasHtml;
    despesasContainer.innerHTML = despesasHtml;

    // Atualizar sugestões (marca as já existentes)
    renderSuggestions();

    // Processar ícones Lucide APENAS nos elementos <i> não processados
    Utils.processNewIcons();
}

/**
 * Renderizar item de categoria como card
 */
function renderCategoriaItem(categoria, tipo) {
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

    return `
        <div class="cat-card ${tipo}" data-id="${categoria.id}">
            <div class="cat-card-header">
                <div class="cat-card-icon ${tipo}">
                    ${iconHtml}
                </div>
                <span class="cat-card-name">${escapeHtml(displayName)}</span>
                <div class="cat-card-actions">
                    ${SubcategoriasModule.renderExpandButton(categoria.id)}
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
                </div>
            </div>
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
        .filter(c => c.tipo === tipo)
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
 * Renderizar grid de ícones em um container
 */
function renderIconGrid(containerId, onSelect) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = AVAILABLE_ICONS.map(icon => `
        <button type="button" class="icon-pick-item" data-icon="${icon.name}" title="${icon.name}">
            <i data-lucide="${icon.name}"></i>
        </button>
    `).join('');

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
}

/**
 * Selecionar ícone no form de criação
 */
function selectIcon(iconName) {
    STATE.selectedIcon = iconName;
    document.getElementById('catIcone').value = iconName;

    // Atualizar preview
    updateIconPreview(iconName);

    // Highlight
    highlightSelectedIcon('iconPickerGrid', iconName);

    // Fechar picker
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

    container.querySelectorAll('.icon-pick-item').forEach(item => {
        if (!q) {
            item.style.display = '';
            return;
        }
        const iconName = item.dataset.icon;
        const iconData = AVAILABLE_ICONS.find(i => i.name === iconName);
        const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
        item.style.display = searchText.includes(q) ? '' : 'none';
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

    // Atualizar preview
    const preview = document.getElementById('editIconPreview');
    if (preview) {
        preview.innerHTML = `<i data-lucide="${iconName}"></i>`;
        Utils.processNewIcons();
    }

    // Highlight
    highlightSelectedIcon('editIconPickerGrid', iconName);

    // Fechar panel
    const panel = document.getElementById('editIconPickerPanel');
    if (panel) panel.classList.add('d-none');
}

function filterEditIcons(query) {
    const container = document.getElementById('editIconPickerGrid');
    if (!container) return;
    const q = query.toLowerCase().trim();

    container.querySelectorAll('.icon-pick-item').forEach(item => {
        if (!q) {
            item.style.display = '';
            return;
        }
        const iconName = item.dataset.icon;
        const iconData = AVAILABLE_ICONS.find(i => i.name === iconName);
        const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
        item.style.display = searchText.includes(q) ? '' : 'none';
    });
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
    try {
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            icone: formData.get('icone') || null
        };

        const response = await fetch(`${CONFIG.API_URL}categorias`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Utils.getCsrfToken()
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erro ao criar categoria');
        }

        const result = await response.json();

        // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
        if (result.data?.gamification?.achievements && Array.isArray(result.data.gamification.achievements)) {
            if (typeof window.notifyMultipleAchievements === 'function') {
                window.notifyMultipleAchievements(result.data.gamification.achievements);
            }
        }

        showSuccess('Categoria criada com sucesso!');
        form.reset();
        resetCreateForm();

        // Recarregar tudo
        await loadAll();

    } catch (error) {
        console.error('❌ Erro ao criar categoria:', error);
        showError(error.message || 'Erro ao criar categoria. Tente novamente.');
    }
}

/**
 * Editar categoria — abre modal
 */
function editarCategoria(id) {
    const categoria = STATE.categorias.find(c => c.id === id);
    if (!categoria) return;

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

    try {
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            icone: formData.get('icone') || null
        };

        const response = await fetch(`${CONFIG.API_URL}categorias/${STATE.categoriaEmEdicao.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Utils.getCsrfToken()
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erro ao editar categoria');
        }

        const result = await response.json();

        showSuccess('Categoria atualizada com sucesso!');

        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCategoria'));
        modal.hide();

        // Recarregar tudo
        await loadAll();

    } catch (error) {
        console.error('❌ Erro ao editar categoria:', error);
        showError(error.message || 'Erro ao editar categoria. Tente novamente.');
    }
}

/**
 * Excluir categoria
 */
async function excluirCategoria(id) {
    const categoria = STATE.categorias.find(c => c.id === id);
    if (!categoria) return;

    const confirmacao = await Swal.fire({
        title: 'Confirmar exclusão',
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
        const response = await fetch(`${CONFIG.API_URL}categorias/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Utils.getCsrfToken()
            }
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erro ao excluir categoria');
        }

        showSuccess('Categoria excluída com sucesso!');

        // Recarregar tudo
        await loadAll();

    } catch (error) {
        console.error('❌ Erro ao excluir categoria:', error);
        showError(error.message || 'Erro ao excluir categoria. Pode haver lançamentos vinculados.');
    }
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

        const response = await fetch(`${CONFIG.API_URL}financas/orcamentos`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Utils.getCsrfToken()
            },
            body: JSON.stringify({
                categoria_id: categoriaId,
                valor_limite: valorLimite,
                mes: mes,
                ano: ano
            })
        });

        if (!response.ok) {
            throw new Error('Erro ao salvar orçamento');
        }

        showSuccess('Limite atualizado!');
        await loadOrcamentos();
        renderCategorias();
    } catch (e) {
        console.error('Erro ao salvar orçamento:', e);
        showError('Erro ao salvar limite. Tente novamente.');
    }
}

/**
 * Remover orçamento via API
 */
async function removerOrcamento(orcamentoId) {
    try {
        const response = await fetch(`${CONFIG.API_URL}financas/orcamentos/${orcamentoId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Utils.getCsrfToken()
            }
        });

        if (!response.ok) {
            throw new Error('Erro ao remover orçamento');
        }

        showSuccess('Limite removido!');
        await loadOrcamentos();
        renderCategorias();
    } catch (e) {
        console.error('Erro ao remover orçamento:', e);
        showError('Erro ao remover limite. Tente novamente.');
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

    // Expose for programmatic access
    loadAll,
    loadCategorias,
    loadOrcamentos,
    renderCategorias,
};

// Register in Modules
Modules.App = CategoriasManager;
