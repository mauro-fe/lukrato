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

export function renderAccountStep(container, instituicoes = []) {
    const { state, saveAccount, nextStep, prevStep } = useOnboarding();
    const accountState = state.data.account || {};
    const goalMessage = state.data.goal ? GOAL_MESSAGES[state.data.goal]?.welcome : null;
    const institutions = normalizeInstitutions(instituicoes);
    const initialInstitution = accountState?.instituicao_financeira_id
        ? institutions.find((institution) => String(institution.id) === String(accountState.instituicao_financeira_id)) || null
        : institutions.find((institution) => normalizeText(institution.nome) === normalizeText(accountState?.instituicao || '')) || null;
    const initialName = accountState?.nome || initialInstitution?.nome || accountState?.instituicao || '';
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
                        <label class="lk-ob2-label" for="accountInstitution">
                            <i data-lucide="building-2"></i>
                            Instituicao
                            <span class="lk-ob2-label-hint">(opcional)</span>
                        </label>
                        <select
                            class="lk-ob2-select"
                            id="accountInstitution"
                            name="instituicao_financeira_id">
                            <option value="">Selecionar depois</option>
                            ${institutions.map((institution) => `
                                <option
                                    value="${escapeHtml(institution.id)}"
                                    ${initialInstitution && String(initialInstitution.id) === String(institution.id) ? 'selected' : ''}>
                                    ${escapeHtml(institution.nome)}
                                </option>
                            `).join('')}
                        </select>
                        <div class="lk-ob2-account-caption">
                            Se voce escolher agora, usamos a instituicao como sugestao de nome.
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
    const institutionSelect = container.querySelector('#accountInstitution');
    const balanceInput = container.querySelector('#accountBalance');
    const previewName = container.querySelector('#accountPreviewName');
    const previewInstitution = container.querySelector('#accountPreviewInstitution');
    const previewBalance = container.querySelector('#accountPreviewBalance');
    const errorEl = container.querySelector('#accountError');
    const backBtn = container.querySelector('#btnAccountBack');
    const submitBtn = container.querySelector('#btnAccountNext');
    let selectedInstitution = initialInstitution || null;
    let lastSuggestedName = selectedInstitution?.nome || '';

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

    if (institutionSelect) {
        institutionSelect.addEventListener('change', () => {
            selectedInstitution = institutions.find((institution) => String(institution.id) === institutionSelect.value) || null;

            const currentName = nameInput.value.trim();
            if (selectedInstitution && (currentName === '' || currentName === lastSuggestedName)) {
                nameInput.value = selectedInstitution.nome;
                lastSuggestedName = selectedInstitution.nome;
            } else if (!selectedInstitution && currentName === lastSuggestedName) {
                lastSuggestedName = '';
            }

            updatePreview();
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

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        try {
            await saveAccount({
                nome: name,
                saldo_inicial: parseCurrency(balanceInput.value),
                instituicao_financeira_id: selectedInstitution?.id || null,
                instituicao: selectedInstitution?.nome || name
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

    setTimeout(() => {
        nameInput?.focus();
        nameInput?.setSelectionRange(nameInput.value.length, nameInput.value.length);
    }, 300);

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}
