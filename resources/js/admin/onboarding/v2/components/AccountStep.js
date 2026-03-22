/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Step Account
 * ============================================================================
 * Tela simplificada para criar a primeira conta com o minimo de atrito.
 * ============================================================================
 */

import { useOnboarding } from '../context/OnboardingContext.js';
import { GOAL_MESSAGES } from '../types.js';

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

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function normalizeInstitutions(instituicoes = []) {
    if (!Array.isArray(instituicoes)) {
        return [];
    }

    return instituicoes
        .filter((instituicao) => instituicao && instituicao.id && instituicao.nome)
        .map((instituicao) => ({
            id: instituicao.id,
            nome: String(instituicao.nome).trim(),
            searchKey: normalizeText(instituicao.nome)
        }))
        .sort((first, second) => first.nome.localeCompare(second.nome, 'pt-BR'));
}

function findInstitutionByQuery(institutions, query) {
    const normalizedQuery = normalizeText(query);
    if (normalizedQuery === '') {
        return null;
    }

    return institutions.find((institution) => institution.searchKey === normalizedQuery) || null;
}

export function renderAccountStep(container, instituicoes = []) {
    const { state, saveAccount, nextStep, prevStep } = useOnboarding();
    const accountState = state.data.account || {};
    const goalMessage = state.data.goal ? GOAL_MESSAGES[state.data.goal]?.welcome : null;
    const institutions = normalizeInstitutions(instituicoes);
    const initialInstitution = accountState?.instituicao_financeira_id
        ? institutions.find((institution) => String(institution.id) === String(accountState.instituicao_financeira_id)) || null
        : institutions.find((institution) => normalizeText(institution.nome) === normalizeText(accountState?.instituicao || '')) || null;
    const initialName = accountState?.nome || initialInstitution?.nome || accountState?.instituicao || '';
    const initialInstitutionQuery = initialInstitution?.nome || accountState?.instituicao || '';
    const initialBalance = Number.isFinite(Number(accountState?.saldo))
        ? formatCurrency(Number(accountState.saldo))
        : '0,00';

    container.innerHTML = `
        <div class="lk-ob2-step lk-ob2-account" data-step="account">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box">
                        <i data-lucide="wallet"></i>
                    </div>
                    <h1 class="lk-ob2-title">Como voce chama sua conta principal?</h1>
                    <p class="lk-ob2-subtitle">
                        Use um nome simples como Nubank, Carteira ou Banco. Os detalhes ficam para depois.
                    </p>
                </div>

                <div class="lk-ob2-account-intro">
                    <div class="lk-ob2-account-intro-copy">
                        <strong>Comece simples</strong>
                        <span>${escapeHtml(goalMessage || 'O importante agora e ver seu dinheiro aparecer no painel o mais rapido possivel.')}</span>
                    </div>
                    <span class="lk-ob2-account-intro-badge">Leva segundos</span>
                </div>

                <form class="lk-ob2-form" id="accountForm">
                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountName">
                            <i data-lucide="pen-line"></i>
                            Nome da conta
                        </label>
                        <input
                            type="text"
                            class="lk-ob2-input"
                            id="accountName"
                            name="nome"
                            placeholder="Ex: Nubank, Carteira, Banco"
                            value="${escapeHtml(initialName)}"
                            required
                            autocomplete="off"
                            maxlength="50">
                        <div class="lk-ob2-account-caption">
                            Esse nome vai aparecer no seu dashboard inicial.
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountInstitutionSearch">
                            <i data-lucide="building-2"></i>
                            Instituicao
                            <span class="lk-ob2-label-hint">(opcional)</span>
                        </label>

                        <div class="lk-ob2-institution-toolbar">
                            <div class="lk-ob2-search-field">
                                <i data-lucide="search"></i>
                                <input
                                    type="search"
                                    class="lk-ob2-input lk-ob2-search-input"
                                    id="accountInstitutionSearch"
                                    placeholder="Busque sua instituicao"
                                    value="${escapeHtml(initialInstitutionQuery)}"
                                    autocomplete="off">
                            </div>
                            <button type="button" class="lk-ob2-btn-ghost" id="btnInstitutionClear">
                                <i data-lucide="eraser"></i>
                                <span>Depois</span>
                            </button>
                        </div>

                        <div class="lk-ob2-combobox-results" id="institutionResults" hidden></div>
                        <div class="lk-ob2-combobox-empty" id="institutionEmpty" hidden>
                            Nenhuma instituicao encontrada. Voce pode seguir sem escolher agora.
                        </div>

                        <div class="lk-ob2-account-caption">
                            Buscar aqui e opcional. Se selecionar uma instituicao, usamos como sugestao de nome.
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountBalance">
                            <i data-lucide="coins"></i>
                            Saldo atual
                            <span class="lk-ob2-label-hint">(pode ser aproximado)</span>
                        </label>
                        <div class="lk-ob2-money-input">
                            <span class="lk-ob2-currency">R$</span>
                            <input
                                type="text"
                                class="lk-ob2-input lk-ob2-input-money"
                                id="accountBalance"
                                name="saldo_inicial"
                                value="${escapeHtml(initialBalance)}"
                                inputmode="decimal"
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="lk-ob2-account-preview" id="accountPreview">
                        <div class="lk-ob2-account-preview-main">
                            <div class="lk-ob2-account-preview-icon">
                                <i data-lucide="wallet"></i>
                            </div>
                            <div class="lk-ob2-account-preview-copy">
                                <span class="lk-ob2-account-preview-label">Vai aparecer assim no seu painel</span>
                                <strong id="accountPreviewName">${escapeHtml(initialName || 'Sua conta principal')}</strong>
                                <span class="lk-ob2-account-preview-label" id="accountPreviewInstitution">
                                    ${initialInstitution ? `Instituicao: ${escapeHtml(initialInstitution.nome)}` : 'Instituicao opcional'}
                                </span>
                            </div>
                        </div>
                        <strong class="lk-ob2-account-preview-balance" id="accountPreviewBalance">
                            R$ ${escapeHtml(initialBalance)}
                        </strong>
                    </div>

                    <div class="lk-ob2-error" id="accountError" style="display: none;">
                        <i data-lucide="alert-circle"></i>
                        <span></span>
                    </div>

                    <div class="lk-ob2-actions">
                        <button type="button" class="lk-ob2-btn-back" id="btnAccountBack">
                            <i data-lucide="arrow-left"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="submit" class="lk-ob2-btn-primary" id="btnAccountNext">
                            <span>Criar conta</span>
                            <i data-lucide="arrow-right"></i>
                        </button>
                    </div>
                </form>

                <div class="lk-ob2-progress-hint">
                    <i data-lucide="shield-check"></i>
                    <span>Voce pode conectar banco, carteira ou outras contas depois.</span>
                </div>
            </div>
        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    const form = container.querySelector('#accountForm');
    const nameInput = container.querySelector('#accountName');
    const searchInput = container.querySelector('#accountInstitutionSearch');
    const clearInstitutionBtn = container.querySelector('#btnInstitutionClear');
    const resultsEl = container.querySelector('#institutionResults');
    const emptyEl = container.querySelector('#institutionEmpty');
    const balanceInput = container.querySelector('#accountBalance');
    const previewName = container.querySelector('#accountPreviewName');
    const previewInstitution = container.querySelector('#accountPreviewInstitution');
    const previewBalance = container.querySelector('#accountPreviewBalance');
    const errorEl = container.querySelector('#accountError');
    const backBtn = container.querySelector('#btnAccountBack');
    const submitBtn = container.querySelector('#btnAccountNext');

    let selectedInstitution = initialInstitution || null;
    let lastSuggestedName = selectedInstitution?.nome || '';
    let isInstitutionMenuOpen = false;

    function getVisibleInstitutions(query) {
        const normalizedQuery = normalizeText(query);
        if (normalizedQuery === '') {
            return institutions.slice(0, 8);
        }

        return institutions
            .filter((institution) => institution.searchKey.includes(normalizedQuery))
            .slice(0, 8);
    }

    function updatePreview() {
        if (previewName) {
            previewName.textContent = nameInput.value.trim() || 'Sua conta principal';
        }

        if (previewInstitution) {
            previewInstitution.textContent = selectedInstitution
                ? `Instituicao: ${selectedInstitution.nome}`
                : 'Instituicao opcional';
        }

        if (previewBalance) {
            previewBalance.textContent = `R$ ${balanceInput.value || '0,00'}`;
        }
    }

    function selectInstitution(institution) {
        selectedInstitution = institution;
        searchInput.value = institution?.nome || '';
        isInstitutionMenuOpen = false;

        const currentName = nameInput.value.trim();
        if (institution && (currentName === '' || currentName === lastSuggestedName)) {
            nameInput.value = institution.nome;
            lastSuggestedName = institution.nome;
        }

        updatePreview();
        renderInstitutionResults();
    }

    function clearInstitutionSelection(clearQuery = true) {
        selectedInstitution = null;
        if (clearQuery) {
            searchInput.value = '';
        }
        isInstitutionMenuOpen = false;
        updatePreview();
        renderInstitutionResults();
    }

    function renderInstitutionResults() {
        if (!resultsEl || !emptyEl) {
            return;
        }

        if (!isInstitutionMenuOpen) {
            resultsEl.hidden = true;
            emptyEl.hidden = true;
            resultsEl.innerHTML = '';
            return;
        }

        const visibleInstitutions = getVisibleInstitutions(searchInput.value);
        if (visibleInstitutions.length === 0) {
            resultsEl.hidden = true;
            resultsEl.innerHTML = '';
            emptyEl.hidden = false;
            return;
        }

        emptyEl.hidden = true;
        resultsEl.hidden = false;
        resultsEl.innerHTML = visibleInstitutions.map((institution) => `
            <button
                type="button"
                class="lk-ob2-combobox-item ${selectedInstitution && String(selectedInstitution.id) === String(institution.id) ? 'selected' : ''}"
                data-institution-id="${escapeHtml(institution.id)}">
                <span class="lk-ob2-combobox-item-name">${escapeHtml(institution.nome)}</span>
                <span class="lk-ob2-combobox-item-badge">
                    ${selectedInstitution && String(selectedInstitution.id) === String(institution.id) ? 'Selecionada' : 'Usar'}
                </span>
            </button>
        `).join('');

        resultsEl.querySelectorAll('.lk-ob2-combobox-item').forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            button.addEventListener('click', () => {
                const institution = institutions.find((item) => String(item.id) === String(button.dataset.institutionId));
                if (!institution) {
                    return;
                }

                selectInstitution(institution);
            });
        });
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

    balanceInput.addEventListener('focus', function handleFocus() {
        setTimeout(() => this.select(), 50);
    });

    balanceInput.addEventListener('input', function handleBalanceInput(event) {
        let value = event.target.value.replace(/[^\d]/g, '');

        if (value === '') {
            event.target.value = '0,00';
            updatePreview();
            return;
        }

        value = parseInt(value, 10);
        event.target.value = formatCurrency(value / 100);
        updatePreview();
    });

    nameInput.addEventListener('input', updatePreview);

    searchInput.addEventListener('focus', () => {
        isInstitutionMenuOpen = true;
        renderInstitutionResults();
    });

    searchInput.addEventListener('input', () => {
        const typedValue = searchInput.value.trim();

        if (!typedValue) {
            selectedInstitution = null;
            lastSuggestedName = '';
        } else if (selectedInstitution && normalizeText(typedValue) !== selectedInstitution.searchKey) {
            selectedInstitution = null;
        }

        isInstitutionMenuOpen = true;
        updatePreview();
        renderInstitutionResults();
    });

    searchInput.addEventListener('blur', () => {
        setTimeout(() => {
            const matchedInstitution = selectedInstitution || findInstitutionByQuery(institutions, searchInput.value);
            if (!selectedInstitution && matchedInstitution && normalizeText(searchInput.value) === matchedInstitution.searchKey) {
                selectInstitution(matchedInstitution);
                return;
            }

            isInstitutionMenuOpen = false;
            renderInstitutionResults();
        }, 120);
    });

    if (clearInstitutionBtn) {
        clearInstitutionBtn.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        clearInstitutionBtn.addEventListener('click', (event) => {
            event.preventDefault();
            clearInstitutionSelection();
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError();

        const name = nameInput.value.trim();
        if (!name) {
            showError('Digite um nome para a conta.');
            nameInput.focus();
            return;
        }

        const matchedInstitution = selectedInstitution || findInstitutionByQuery(institutions, searchInput.value);

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        try {
            await saveAccount({
                nome: name,
                saldo_inicial: parseCurrency(balanceInput.value),
                instituicao_financeira_id: matchedInstitution?.id || null,
                instituicao: matchedInstitution?.nome || name
            });

            nextStep();
        } catch (error) {
            showError(error.message || 'Erro ao criar conta. Tente novamente.');
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });

    if (backBtn) {
        backBtn.addEventListener('click', prevStep);
    }

    updatePreview();
    renderInstitutionResults();

    setTimeout(() => {
        nameInput?.focus();
        nameInput?.setSelectionRange(nameInput.value.length, nameInput.value.length);
    }, 300);

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}
