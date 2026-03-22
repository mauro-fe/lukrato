/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Step Transaction
 * ============================================================================
 * Tela para criar o primeiro lancamento com feedback imediato de saldo.
 * ============================================================================
 */

import { useOnboarding } from '../context/OnboardingContext.js';

function formatCurrency(value) {
    const num = parseFloat(value) || 0;
    return num.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function parseCurrency(str) {
    if (!str) {
        return 0;
    }

    return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
}

function normalizeText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function getSignedValue(type, value) {
    const amount = Math.abs(Number(value) || 0);
    return type === 'receita' ? amount : -amount;
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getCategoryVisual(name = '') {
    const normalized = normalizeText(name);
    const visualMap = [
        { match: ['aliment', 'mercado', 'restaurante', 'lanche'], icon: '\u{1F354}', color: '#f59e0b' },
        { match: ['transport', 'uber', 'combust', 'mobilidade'], icon: '\u{1F697}', color: '#3b82f6' },
        { match: ['lazer', 'cinema', 'jogo', 'divers'], icon: '\u{1F3AE}', color: '#8b5cf6' },
        { match: ['casa', 'moradia', 'aluguel'], icon: '\u{1F3E0}', color: '#14b8a6' },
        { match: ['saude', 'farmacia', 'medic'], icon: '\u{1F48A}', color: '#ef4444' },
        { match: ['educ', 'curso', 'livro'], icon: '\u{1F4DA}', color: '#6366f1' },
        { match: ['salario', 'receita', 'renda'], icon: '\u{1F4B0}', color: '#10b981' },
        { match: ['invest', 'reserva'], icon: '\u{1F4C8}', color: '#06b6d4' }
    ];

    const matched = visualMap.find((item) => item.match.some((term) => normalized.includes(term)));
    return matched || { icon: '\u{1F4CC}', color: '#64748b' };
}

function normalizeCategories(categories = []) {
    if (!Array.isArray(categories)) {
        return [];
    }

    return categories
        .filter((category) => category && category.id && category.nome)
        .map((category) => ({
            ...category,
            nome: String(category.nome).trim(),
            tipo: String(category.tipo || '').trim(),
            searchKey: normalizeText(category.nome)
        }))
        .sort((first, second) => first.nome.localeCompare(second.nome, 'pt-BR'));
}

function findCategoryByQuery(categories, query) {
    const normalizedQuery = normalizeText(query);
    if (normalizedQuery === '') {
        return null;
    }

    return categories.find((category) => category.searchKey === normalizedQuery) || null;
}

export function renderTransactionStep(container, options = {}) {
    const { state, saveTransaction, skipTransaction, nextStep, prevStep } = useOnboarding();
    const { categorias = [], conta = null } = options;

    const normalizedCategories = normalizeCategories(categorias);
    const categoriasDespesa = normalizedCategories.filter((category) => category.tipo === 'despesa' || category.tipo === 'expense');
    const categoriasReceita = normalizedCategories.filter((category) => category.tipo === 'receita' || category.tipo === 'income');
    const account = state.data.account || conta || {};
    const accountName = account?.nome || 'Sua conta';
    const baseBalance = Number(account?.saldo || 0);
    const initialTransaction = state.data.transaction || {};
    const initialType = initialTransaction.tipo === 'receita' ? 'receita' : 'despesa';
    const initialCategory = initialTransaction.categoria_id
        ? normalizedCategories.find((category) => String(category.id) === String(initialTransaction.categoria_id)) || null
        : null;
    const initialCategoryQuery = initialCategory?.nome || '';

    container.innerHTML = `
        <div class="lk-ob2-step lk-ob2-transaction" data-step="transaction">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box lk-ob2-icon-despesa">
                        <i data-lucide="receipt"></i>
                    </div>
                    <h1 class="lk-ob2-title">Registre um gasto ou entrada recente</h1>
                    <p class="lk-ob2-subtitle">
                        Coloque algo real de agora para ver seu painel ganhar vida na hora.
                    </p>
                </div>

                <div class="lk-ob2-type-toggle">
                    <button type="button" class="lk-ob2-type-btn ${initialType === 'despesa' ? 'active' : ''}" data-type="despesa">
                        <i data-lucide="arrow-down"></i>
                        <span>Despesa</span>
                    </button>
                    <button type="button" class="lk-ob2-type-btn ${initialType === 'receita' ? 'active' : ''}" data-type="receita">
                        <i data-lucide="arrow-up"></i>
                        <span>Receita</span>
                    </button>
                </div>

                <form class="lk-ob2-form" id="transactionForm">
                    <input type="hidden" id="transactionType" name="tipo" value="${initialType}">
                    <input type="hidden" id="contaId" name="conta_id" value="${state.data.account?.id || ''}">

                    <div class="lk-ob2-form-group lk-ob2-value-group">
                        <div class="lk-ob2-big-value">
                            <span class="lk-ob2-currency-big">R$</span>
                            <input type="text"
                                   class="lk-ob2-input-big"
                                   id="transactionValue"
                                   name="valor"
                                   value="${formatCurrency(initialTransaction.valor || 0)}"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   required>
                        </div>

                        <div class="lk-ob2-balance-preview" id="transactionBalancePreview">
                            <div class="lk-ob2-balance-card">
                                <span class="lk-ob2-balance-label">Conta usada</span>
                                <strong class="lk-ob2-balance-title">${escapeHtml(accountName)}</strong>
                                <span class="lk-ob2-balance-value" id="currentBalanceText">R$ ${formatCurrency(baseBalance)}</span>
                            </div>
                            <div class="lk-ob2-balance-card lk-ob2-balance-card-highlight">
                                <span class="lk-ob2-balance-label">Saldo depois deste registro</span>
                                <strong class="lk-ob2-balance-title" id="projectedBalanceTitle">Vai ficar assim</strong>
                                <span class="lk-ob2-balance-value" id="projectedBalanceText">R$ ${formatCurrency(baseBalance)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="transactionDesc">
                            <i data-lucide="text"></i>
                            O que foi?
                        </label>
                        <input type="text"
                               class="lk-ob2-input"
                               id="transactionDesc"
                               name="descricao"
                               placeholder="Ex: Almoco, Uber, salario..."
                               maxlength="100"
                               value="${escapeHtml(initialTransaction.descricao || '')}"
                               autocomplete="off">
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label">
                            <i data-lucide="tag"></i>
                            Categoria
                            <span class="lk-ob2-label-hint">(opcional)</span>
                        </label>
                        <div class="lk-ob2-institution-toolbar">
                            <div class="lk-ob2-search-field">
                                <i data-lucide="search"></i>
                                <input
                                    type="search"
                                    class="lk-ob2-input lk-ob2-search-input"
                                    id="transactionCategorySearch"
                                    placeholder="Busque ou deixe para depois"
                                    value="${escapeHtml(initialCategoryQuery)}"
                                    autocomplete="off">
                            </div>
                            <button type="button" class="lk-ob2-btn-ghost" id="btnCategoryClear">
                                <i data-lucide="eraser"></i>
                                <span>Depois</span>
                            </button>
                        </div>
                        <div class="lk-ob2-combobox-results" id="transactionCategoryResults" hidden></div>
                        <div class="lk-ob2-combobox-empty" id="transactionCategoryEmpty" hidden>
                            Nenhuma categoria encontrada. Voce pode salvar agora e categorizar depois.
                        </div>
                        <div class="lk-ob2-field-caption">
                            Escolher categoria acelera seu painel, mas nao precisa travar o onboarding.
                        </div>
                    </div>

                    <div class="lk-ob2-error" id="transactionError" style="display: none;">
                        <i data-lucide="alert-circle"></i>
                        <span></span>
                    </div>

                    <div class="lk-ob2-actions">
                        <button type="button" class="lk-ob2-btn-back" id="btnTransactionBack">
                            <i data-lucide="arrow-left"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="submit" class="lk-ob2-btn-primary lk-ob2-btn-despesa" id="btnTransactionNext">
                            <span>Salvar registro</span>
                            <i data-lucide="check"></i>
                        </button>
                    </div>
                </form>

                <div class="lk-ob2-skip-section">
                    <div class="lk-ob2-skip-divider">
                        <span>ou</span>
                    </div>
                    <button type="button" class="lk-ob2-btn-skip" id="btnSkipTransaction">
                        <span>Pular e explorar o Lukrato</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                    <p class="lk-ob2-skip-hint">
                        <i data-lucide="info"></i>
                        Voce pode adicionar mais lancamentos depois pelo menu
                    </p>
                </div>
            </div>
        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    const stepEl = container.querySelector('.lk-ob2-step');
    const form = container.querySelector('#transactionForm');
    const typeInput = container.querySelector('#transactionType');
    const valueInput = container.querySelector('#transactionValue');
    const descInput = container.querySelector('#transactionDesc');
    const categorySearch = container.querySelector('#transactionCategorySearch');
    const categoryResults = container.querySelector('#transactionCategoryResults');
    const categoryEmpty = container.querySelector('#transactionCategoryEmpty');
    const clearCategoryBtn = container.querySelector('#btnCategoryClear');
    const errorEl = container.querySelector('#transactionError');
    const submitBtn = container.querySelector('#btnTransactionNext');
    const backBtn = container.querySelector('#btnTransactionBack');
    const skipBtn = container.querySelector('#btnSkipTransaction');
    const typeBtns = container.querySelectorAll('.lk-ob2-type-btn');
    const iconBox = container.querySelector('.lk-ob2-icon-box');
    const currentBalanceText = container.querySelector('#currentBalanceText');
    const projectedBalanceText = container.querySelector('#projectedBalanceText');
    const projectedBalanceTitle = container.querySelector('#projectedBalanceTitle');
    const balancePreview = container.querySelector('#transactionBalancePreview');

    let currentType = initialType;
    let selectedCategory = initialCategory;
    let selectedCategoryId = initialCategory ? String(initialCategory.id) : '';
    let isCategoryMenuOpen = false;

    function getCurrentCategories(type = currentType) {
        return type === 'receita' ? categoriasReceita : categoriasDespesa;
    }

    function getVisibleCategories(query, type = currentType) {
        const normalizedQuery = normalizeText(query);
        const currentCategories = getCurrentCategories(type);

        if (normalizedQuery === '') {
            return currentCategories.slice(0, 8);
        }

        return currentCategories
            .filter((category) => category.searchKey.includes(normalizedQuery))
            .slice(0, 8);
    }

    function selectCategory(category) {
        selectedCategory = category || null;
        selectedCategoryId = category ? String(category.id) : '';
        if (categorySearch) {
            categorySearch.value = category?.nome || '';
        }
        isCategoryMenuOpen = false;
        renderCategoryResults();
    }

    function clearCategorySelection(clearQuery = true) {
        selectedCategory = null;
        selectedCategoryId = '';
        if (clearQuery && categorySearch) {
            categorySearch.value = '';
        }
        isCategoryMenuOpen = false;
        renderCategoryResults();
    }

    function renderCategoryResults() {
        if (!categoryResults || !categoryEmpty) {
            return;
        }

        if (!isCategoryMenuOpen) {
            categoryResults.hidden = true;
            categoryEmpty.hidden = true;
            categoryResults.innerHTML = '';
            return;
        }

        const visibleCategories = getVisibleCategories(categorySearch?.value || '');
        if (visibleCategories.length === 0) {
            categoryResults.hidden = true;
            categoryResults.innerHTML = '';
            categoryEmpty.hidden = false;
            return;
        }

        categoryEmpty.hidden = true;
        categoryResults.hidden = false;
        categoryResults.innerHTML = visibleCategories.map((category) => {
            const visual = getCategoryVisual(category.nome);
            const isSelected = selectedCategory && String(selectedCategory.id) === String(category.id);

            return `
                <button
                    type="button"
                    class="lk-ob2-combobox-item ${isSelected ? 'selected' : ''}"
                    data-category-id="${escapeHtml(category.id)}">
                    <span class="lk-ob2-combobox-item-name">
                        <span class="lk-ob2-combobox-item-icon" style="--category-color: ${visual.color}">
                            ${visual.icon}
                        </span>
                        <span>${escapeHtml(category.nome)}</span>
                    </span>
                    <span class="lk-ob2-combobox-item-badge">
                        ${isSelected ? 'Selecionada' : 'Usar'}
                    </span>
                </button>
            `;
        }).join('');

        categoryResults.querySelectorAll('.lk-ob2-combobox-item').forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            button.addEventListener('click', () => {
                const category = getCurrentCategories().find((item) => String(item.id) === String(button.dataset.categoryId));
                if (!category) {
                    return;
                }

                selectCategory(category);

                if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            });
        });
    }

    function updateBalancePreview() {
        const typedValue = parseCurrency(valueInput.value);
        const projectedBalance = baseBalance + getSignedValue(currentType, typedValue);

        if (currentBalanceText) {
            currentBalanceText.textContent = `R$ ${formatCurrency(baseBalance)}`;
        }

        if (projectedBalanceText) {
            projectedBalanceText.textContent = `R$ ${formatCurrency(projectedBalance)}`;
        }

        if (projectedBalanceTitle) {
            projectedBalanceTitle.textContent = currentType === 'receita'
                ? 'Vai entrar na conta'
                : 'Vai sair da conta';
        }

        if (balancePreview) {
            balancePreview.classList.toggle('is-income', currentType === 'receita');
            balancePreview.classList.toggle('is-expense', currentType === 'despesa');
        }
    }

    function updateTypeUI(type) {
        currentType = type;
        typeInput.value = type;

        typeBtns.forEach((button) => {
            button.classList.toggle('active', button.dataset.type === type);
        });

        iconBox.classList.toggle('lk-ob2-icon-receita', type === 'receita');
        iconBox.classList.toggle('lk-ob2-icon-despesa', type === 'despesa');

        submitBtn.classList.toggle('lk-ob2-btn-receita', type === 'receita');
        submitBtn.classList.toggle('lk-ob2-btn-despesa', type === 'despesa');

        const availableCategories = getCurrentCategories(type);
        const matchedSelectedCategory = selectedCategory
            ? availableCategories.find((category) => String(category.id) === String(selectedCategory.id)) || null
            : null;

        if (matchedSelectedCategory) {
            selectedCategory = matchedSelectedCategory;
            selectedCategoryId = String(matchedSelectedCategory.id);
            if (categorySearch) {
                categorySearch.value = matchedSelectedCategory.nome;
            }
        } else {
            clearCategorySelection();
        }

        renderCategoryResults();
        updateBalancePreview();
    }

    function showError(message) {
        if (!errorEl) {
            return;
        }

        errorEl.querySelector('span').textContent = message;
        errorEl.style.display = 'flex';
        errorEl.classList.add('shake');
        setTimeout(() => errorEl.classList.remove('shake'), 500);
    }

    function hideError() {
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    typeBtns.forEach((button) => {
        button.addEventListener('click', () => {
            updateTypeUI(button.dataset.type);
        });
    });

    if (categorySearch) {
        categorySearch.addEventListener('focus', () => {
            isCategoryMenuOpen = true;
            renderCategoryResults();
        });

        categorySearch.addEventListener('input', () => {
            const typedValue = categorySearch.value.trim();

            if (!typedValue) {
                selectedCategory = null;
                selectedCategoryId = '';
            } else if (selectedCategory && normalizeText(typedValue) !== selectedCategory.searchKey) {
                selectedCategory = null;
                selectedCategoryId = '';
            }

            isCategoryMenuOpen = true;
            renderCategoryResults();
        });

        categorySearch.addEventListener('blur', () => {
            setTimeout(() => {
                const matchedCategory = selectedCategory || findCategoryByQuery(getCurrentCategories(), categorySearch.value);
                if (!selectedCategory && matchedCategory && normalizeText(categorySearch.value) === matchedCategory.searchKey) {
                    selectCategory(matchedCategory);
                    return;
                }

                isCategoryMenuOpen = false;
                renderCategoryResults();
            }, 120);
        });
    }

    if (clearCategoryBtn) {
        clearCategoryBtn.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        clearCategoryBtn.addEventListener('click', (event) => {
            event.preventDefault();
            clearCategorySelection();
        });
    }

    valueInput.addEventListener('focus', function handleFocus() {
        setTimeout(() => this.select(), 50);
    });

    valueInput.addEventListener('input', function handleValueInput(event) {
        let value = event.target.value.replace(/[^\d]/g, '');

        if (value === '') {
            event.target.value = '0,00';
            updateBalancePreview();
            return;
        }

        value = parseInt(value, 10);
        event.target.value = formatCurrency(value / 100);
        updateBalancePreview();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError();

        const value = parseCurrency(valueInput.value);
        if (value <= 0) {
            showError('Digite um valor maior que zero.');
            valueInput.focus();
            return;
        }

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        try {
            const matchedCategory = selectedCategory || findCategoryByQuery(getCurrentCategories(), categorySearch?.value || '');

            await saveTransaction({
                tipo: currentType,
                valor: value,
                descricao: descInput.value.trim() || (currentType === 'receita' ? 'Receita' : 'Despesa'),
                categoria_id: matchedCategory?.id || null,
                conta_id: state.data.account?.id
            });

            stepEl?.classList.add('lk-ob2-step-saved');

            if (navigator.vibrate) {
                navigator.vibrate([10, 20, 10]);
            }

            setTimeout(nextStep, 450);
        } catch (error) {
            showError(error.message || 'Erro ao criar lancamento. Tente novamente.');
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });

    if (backBtn) {
        backBtn.addEventListener('click', prevStep);
    }

    if (skipBtn) {
        skipBtn.addEventListener('click', async () => {
            skipBtn.classList.add('loading');
            skipBtn.disabled = true;
            await skipTransaction();
        });
    }

    updateTypeUI(initialType);
    renderCategoryResults();

    setTimeout(() => valueInput?.focus(), 300);

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}
