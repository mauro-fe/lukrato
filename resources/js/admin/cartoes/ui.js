/**
 * Cartoes Manager - UI module
 * Extracted from cartoes-manager.js (monolith -> modules)
 */

import { STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

const FILTER_LABELS = {
    all: 'Todos',
    visa: 'Visa',
    mastercard: 'Mastercard',
    elo: 'Elo',
};

const clamp = (value, min = 0, max = 100) => Math.min(max, Math.max(min, Number(value) || 0));
const hasActiveFilters = () => Boolean(STATE.searchTerm) || STATE.currentFilter !== 'all';
const safeText = (value, fallback = '') => Utils.escapeHtml(String(value ?? fallback));
const getCurrentMonthKey = () => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};
const getCardColor = (cartao) => (
    cartao?.cor_cartao ||
    cartao?.conta?.instituicao_financeira?.cor_primaria ||
    cartao?.instituicao_cor ||
    Utils.getAccentColor(cartao?.bandeira)
);

export const CartoesUI = {
    setupEventListeners() {
        document.getElementById('btnNovoCartao')?.addEventListener('click', () => {
            CartoesUI.openModal('create');
        });

        document.getElementById('btnNovoCartaoEmpty')?.addEventListener('click', () => {
            CartoesUI.openModal('create');
        });

        document.getElementById('btnLimparFiltrosEmpty')?.addEventListener('click', () => {
            CartoesUI.clearFilters();
        });

        const modalOverlay = document.getElementById('modalCartaoOverlay');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (event) => {
                if (event.target === modalOverlay) {
                    CartoesUI.closeModal();
                }
            });
        }

        document.querySelectorAll('#modalCartaoOverlay .modal-close, #modalCartaoOverlay .modal-close-btn').forEach((btn) => {
            btn.addEventListener('click', () => CartoesUI.closeModal());
        });

        document.getElementById('limiteTotal')?.addEventListener('input', (event) => {
            event.target.value = CartoesUI.formatMoneyInput(event.target.value);
        });

        document.getElementById('ultimosDigitos')?.addEventListener('input', (event) => {
            event.target.value = String(event.target.value || '').replace(/\D/g, '').slice(0, 4);
        });

        ['diaFechamento', 'diaVencimento'].forEach((inputId) => {
            document.getElementById(inputId)?.addEventListener('input', (event) => {
                event.target.value = CartoesUI.normalizeDayValue(event.target.value);
            });
        });

        document.addEventListener('keydown', (event) => {
            const overlay = document.getElementById('modalCartaoOverlay');
            if (event.key === 'Escape' && overlay?.classList.contains('active')) {
                CartoesUI.closeModal();
            }
        });

        document.getElementById('formCartao')?.addEventListener('submit', (event) => {
            event.preventDefault();
            Modules.API.saveCartao();
        });

        document.getElementById('cartaoLembreteAviso')?.addEventListener('change', () => {
            CartoesUI.syncReminderChannels();
        });

        document.getElementById('btnReload')?.addEventListener('click', () => {
            Modules.API.loadCartoes();
        });

        const searchInput = document.getElementById('searchCartoes');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((event) => {
                STATE.searchTerm = String(event.target.value || '').trim().toLowerCase();
                CartoesUI.filterCartoes();
            }, 250));
        }

        document.querySelectorAll('.filter-btn:not(.btn-clear-filters)').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const button = event.currentTarget;
                STATE.currentFilter = button.dataset.filter || 'all';
                CartoesUI.filterCartoes();
            });
        });

        document.getElementById('btnLimparFiltrosCartoes')?.addEventListener('click', () => {
            CartoesUI.clearFilters();
        });

        document.querySelectorAll('.view-btn').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const button = event.currentTarget;
                STATE.currentView = button.dataset.view || 'grid';
                CartoesUI.updateView();
            });
        });

        document.getElementById('btnExportar')?.addEventListener('click', () => {
            CartoesUI.exportarRelatorio();
        });

        CartoesUI.syncReminderChannels();
        CartoesUI.updateClearButtons();
    },

    restoreViewPreference() {
        const savedView = localStorage.getItem('cartoes_view_mode');
        if (savedView === 'grid' || savedView === 'list') {
            STATE.currentView = savedView;
        }

        CartoesUI.updateView();
    },

    formatMoneyInput(value) {
        const digits = String(value || '').replace(/[^\d]/g, '');
        const numericValue = parseInt(digits, 10) || 0;
        return (numericValue / 100)
            .toFixed(2)
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },

    formatMoneyValue(value) {
        const numericValue = Number(value) || 0;
        return numericValue
            .toFixed(2)
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },

    normalizeDayValue(value) {
        let normalized = String(value || '').replace(/\D/g, '').slice(0, 2);
        if (normalized && parseInt(normalized, 10) > 31) {
            normalized = '31';
        }
        return normalized;
    },

    setScrollLock(locked) {
        const overflow = locked ? 'hidden' : '';
        document.body.style.overflow = overflow;
        document.documentElement.style.overflow = overflow;
    },

    syncReminderChannels() {
        const lembreteSelect = document.getElementById('cartaoLembreteAviso');
        const canaisDiv = document.getElementById('cartaoCanaisLembrete');

        if (!lembreteSelect || !canaisDiv) {
            return;
        }

        const shouldShow = Boolean(lembreteSelect.value);
        canaisDiv.style.display = shouldShow ? 'block' : 'none';

        if (!shouldShow) {
            return;
        }

        const canalInapp = document.getElementById('cartaoCanalInapp');
        const canalEmail = document.getElementById('cartaoCanalEmail');

        if (canalInapp && canalEmail && !canalInapp.checked && !canalEmail.checked) {
            canalInapp.checked = true;
        }
    },

    clearFilters() {
        const searchInput = document.getElementById('searchCartoes');
        if (searchInput) {
            searchInput.value = '';
        }

        STATE.searchTerm = '';
        STATE.currentFilter = 'all';
        CartoesUI.filterCartoes();
    },

    updateClearButtons() {
        const shouldShow = hasActiveFilters();
        const toolbarClear = document.getElementById('btnLimparFiltrosCartoes');
        const emptyClear = document.getElementById('btnLimparFiltrosEmpty');

        if (toolbarClear) {
            toolbarClear.style.display = shouldShow ? '' : 'none';
        }

        if (emptyClear) {
            emptyClear.style.display = shouldShow ? '' : 'none';
        }
    },

    filterCartoes() {
        const query = STATE.searchTerm;

        STATE.filteredCartoes = STATE.cartoes.filter((cartao) => {
            const nomeCartao = String(cartao.nome_cartao || cartao.nome || '').toLowerCase();
            const finalCartao = String(cartao.ultimos_digitos || '').toLowerCase();
            const contaNome = String(cartao.conta?.nome || '').toLowerCase();
            const instituicaoNome = String(cartao.conta?.instituicao_financeira?.nome || '').toLowerCase();

            const matchSearch = !query ||
                nomeCartao.includes(query) ||
                finalCartao.includes(query) ||
                contaNome.includes(query) ||
                instituicaoNome.includes(query);

            const matchFilter = STATE.currentFilter === 'all' ||
                String(cartao.bandeira || '').toLowerCase() === STATE.currentFilter;

            return matchSearch && matchFilter;
        });

        CartoesUI.renderCartoes();
        CartoesUI.renderFilterSummary();
        CartoesUI.updateClearButtons();
    },

    renderCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');

        if (!grid || !emptyState) {
            return;
        }

        grid.setAttribute('aria-busy', 'false');
        CartoesUI.updateEmptyState();

        if (STATE.filteredCartoes.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            refreshIcons();
            return;
        }

        emptyState.style.display = 'none';
        grid.innerHTML = STATE.filteredCartoes.map((cartao) => CartoesUI.createCardHTML(cartao)).join('');
        CartoesUI.updateView();
        CartoesUI.setupCardActions();
        refreshIcons();
    },

    updateEmptyState() {
        const emptyState = document.getElementById('emptyState');
        const title = emptyState?.querySelector('h3');
        const description = emptyState?.querySelector('p');
        const clearButton = document.getElementById('btnLimparFiltrosEmpty');

        if (!emptyState || !title || !description || !clearButton) {
            return;
        }

        if (hasActiveFilters()) {
            title.textContent = 'Nenhum cartao encontrado';
            description.textContent = 'Revise a busca ou limpe os filtros para voltar a ver os cartoes ativos.';
            clearButton.style.display = '';
            return;
        }

        title.textContent = 'Nenhum cartao cadastrado';
        description.textContent = 'Adicione seu primeiro cartao para acompanhar limite, vencimentos e faturas em tempo real.';
        clearButton.style.display = 'none';
    },

    createCardHTML(cartao) {
        const limiteTotal = parseFloat(cartao.limite_total) || 0;
        const limiteDisponivel = parseFloat(cartao.limite_disponivel_real ?? cartao.limite_disponivel) || 0;
        const limiteUtilizado = parseFloat(cartao.limite_utilizado) || Math.max(0, limiteTotal - limiteDisponivel);
        const percentualUso = clamp(
            cartao.percentual_uso ?? (limiteTotal > 0 ? (limiteUtilizado / limiteTotal) * 100 : 0),
            0,
            100
        );
        const percentualDisponivel = clamp(100 - percentualUso, 0, 100);
        const brandIcon = Utils.getBrandIcon(cartao.bandeira);
        const corBg = getCardColor(cartao) || Utils.getDefaultColor(cartao.bandeira);
        const usageTone = percentualUso >= 90 ? 'is-danger' : percentualUso >= 70 ? 'is-warning' : 'is-safe';
        const contaNome = safeText(cartao.conta?.nome, 'Conta nao vinculada');
        const instituicaoNome = safeText(cartao.conta?.instituicao_financeira?.nome, 'Sem instituicao');
        const cardName = safeText(cartao.nome_cartao || cartao.nome, 'Cartao');
        const brandName = safeText(cartao.bandeira, 'Cartao');
        const closingLabel = cartao.dia_fechamento ? `Dia ${cartao.dia_fechamento}` : 'Nao informado';
        const dueLabel = cartao.dia_vencimento ? `Dia ${cartao.dia_vencimento}` : 'Nao informado';

        return `
            <div
                class="credit-card"
                data-id="${cartao.id}"
                data-brand="${String(cartao.bandeira || 'outros').toLowerCase()}"
                style="background: ${corBg};"
                tabindex="0"
                role="button"
                aria-label="Abrir detalhes do cartao ${cardName}"
            >
                ${cartao.temFaturaPendente ? `
                    <div class="card-badge-fatura" title="Fatura pendente">
                        <i data-lucide="circle-alert"></i>
                        Fatura pendente
                    </div>
                ` : ''}

                <div class="card-header">
                    <div class="card-brand">
                        <img
                            src="${brandIcon}"
                            alt="${brandName}"
                            class="brand-logo"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
                        >
                        <i class="brand-icon-fallback" data-lucide="credit-card" style="display: none;" aria-hidden="true"></i>
                        <div class="card-brand-copy">
                            <span class="card-name">${cardName}</span>
                            <span class="card-institution">${instituicaoNome}</span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button
                            type="button"
                            class="lk-info"
                            data-lk-tooltip-title="Exclusao de cartoes"
                            data-lk-tooltip="Para evitar perda de historico e faturas, cartoes so podem ser excluidos apos serem arquivados."
                            aria-label="Ajuda: Exclusao de cartoes"
                        >
                            <i data-lucide="info" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.verFatura(${cartao.id})"
                            title="Ver fatura"
                        >
                            <i data-lucide="file-text" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.editCartao(${cartao.id})"
                            title="Editar"
                        >
                            <i data-lucide="pencil" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.arquivarCartao(${cartao.id})"
                            title="Arquivar"
                        >
                            <i data-lucide="archive" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="card-account">${contaNome}</div>

                <div class="card-number">
                    •••• •••• •••• ${safeText(cartao.ultimos_digitos, '0000')}
                </div>

                <div class="card-metrics">
                    <div class="card-metric">
                        <div class="card-label">Fechamento</div>
                        <div class="card-value">${closingLabel}</div>
                    </div>
                    <div class="card-metric">
                        <div class="card-label">Vencimento</div>
                        <div class="card-value">${dueLabel}</div>
                    </div>
                    <div class="card-metric ${usageTone}">
                        <div class="card-label">Uso</div>
                        <div class="card-value">${percentualUso.toFixed(1)}%</div>
                    </div>
                </div>

                <div class="card-limit-summary">
                    <div class="card-limit-row">
                        <span class="card-label">Disponivel</span>
                        <span class="card-value">${Utils.formatMoney(limiteDisponivel)}</span>
                    </div>
                    <div class="limit-bar">
                        <div class="limit-fill ${usageTone}" style="width: ${percentualDisponivel}%"></div>
                    </div>
                    <div class="limit-caption">Usado ${Utils.formatMoney(limiteUtilizado)} de ${Utils.formatMoney(limiteTotal)}</div>
                </div>
            </div>
        `;
    },

    updateStats() {
        const stats = STATE.cartoes.reduce((acc, cartao) => {
            const limiteTotal = parseFloat(cartao.limite_total) || 0;
            const limiteDisponivel = parseFloat(cartao.limite_disponivel_real ?? cartao.limite_disponivel) || 0;
            const limiteUtilizado = parseFloat(cartao.limite_utilizado) || Math.max(0, limiteTotal - limiteDisponivel);

            acc.total += 1;
            acc.limiteTotal += limiteTotal;
            acc.limiteDisponivel += limiteDisponivel;
            acc.limiteUtilizado += limiteUtilizado;
            return acc;
        }, { total: 0, limiteTotal: 0, limiteDisponivel: 0, limiteUtilizado: 0 });

        document.getElementById('totalCartoes').textContent = String(stats.total);
        document.getElementById('statLimiteTotal').textContent = Utils.formatMoney(stats.limiteTotal);
        document.getElementById('limiteDisponivel').textContent = Utils.formatMoney(stats.limiteDisponivel);
        document.getElementById('limiteUtilizado').textContent = Utils.formatMoney(stats.limiteUtilizado);

        CartoesUI.animateStats();
    },

    animateStats() {
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            card.style.animation = 'none';
            setTimeout(() => {
                card.style.animation = 'fadeIn 0.5s ease forwards';
            }, index * 100);
        });
    },

    renderFilterSummary() {
        const summary = document.getElementById('cartoesFilterSummary');
        if (!summary) {
            return;
        }

        const total = STATE.cartoes.length;
        const visiveis = STATE.filteredCartoes.length;
        const faturasPendentes = STATE.cartoes.filter((cartao) => cartao.temFaturaPendente).length;
        const cartoesCriticos = STATE.cartoes.filter((cartao) => clamp(cartao.percentual_uso) >= 80).length;
        const updatedAt = STATE.lastLoadedAt
            ? new Date(STATE.lastLoadedAt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
            : null;

        const message = hasActiveFilters()
            ? `Mostrando ${visiveis} de ${total} cartoes com os filtros atuais.`
            : total
                ? 'Painel consolidado com limite, faturas e cartoes que pedem atencao.'
                : 'Cadastre seu primeiro cartao para acompanhar limite e vencimentos aqui.';

        const pills = [
            `<span class="cartoes-summary-pill neutral">${visiveis} visiveis</span>`,
        ];

        if (STATE.currentFilter !== 'all') {
            pills.push(`<span class="cartoes-summary-pill accent">Bandeira: ${safeText(FILTER_LABELS[STATE.currentFilter] || STATE.currentFilter)}</span>`);
        }

        if (STATE.searchTerm) {
            pills.push(`<span class="cartoes-summary-pill info">Busca: ${safeText(STATE.searchTerm)}</span>`);
        }

        if (!hasActiveFilters()) {
            pills.push(`<span class="cartoes-summary-pill ${faturasPendentes ? 'warning' : 'success'}">${faturasPendentes} com fatura pendente</span>`);
            pills.push(`<span class="cartoes-summary-pill ${cartoesCriticos ? 'danger' : 'success'}">${cartoesCriticos} com uso alto</span>`);
        }

        if (updatedAt) {
            pills.push(`<span class="cartoes-summary-pill subtle">Atualizado as ${safeText(updatedAt)}</span>`);
        }

        summary.innerHTML = `
            <div class="cartoes-summary-row">
                <div class="cartoes-summary-copy">
                    <i data-lucide="${hasActiveFilters() ? 'filter' : 'sparkles'}"></i>
                    <span>${message}</span>
                </div>
                <div class="cartoes-summary-pills">
                    ${pills.join('')}
                </div>
            </div>
        `;

        refreshIcons();
    },

    updateView() {
        const grid = document.getElementById('cartoesGrid');
        if (!grid) {
            return;
        }

        grid.classList.toggle('list-view', STATE.currentView === 'list');

        document.querySelectorAll('.view-btn').forEach((button) => {
            button.classList.toggle('active', button.dataset.view === STATE.currentView);
        });

        localStorage.setItem('cartoes_view_mode', STATE.currentView);
        CartoesUI.renderFilterSummary();
    },

    setModalSubmitState(loading, isEdit = false) {
        const submitButton = document.getElementById('btnSalvarCartao');
        const submitLabel = document.getElementById('cartaoSubmitLabel');

        if (!submitButton || !submitLabel) {
            return;
        }

        submitButton.disabled = loading;
        submitButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        submitLabel.textContent = loading
            ? (isEdit ? 'Salvando alteracoes...' : 'Salvando cartao...')
            : (isEdit ? 'Salvar alteracoes' : 'Salvar cartao');

        const iconContainer = submitButton.querySelector('[data-lucide], svg');
        if (iconContainer?.getAttribute) {
            iconContainer.setAttribute('data-lucide', loading ? 'loader-2' : 'save');
            iconContainer.classList.toggle('icon-spin', loading);
        }

        refreshIcons();
    },

    async openModal(mode = 'create', cartaoData = null) {
        const overlay = document.getElementById('modalCartaoOverlay');
        const modal = document.getElementById('modalCartao');
        const form = document.getElementById('formCartao');
        const titulo = document.getElementById('modalCartaoTitulo');
        const subtitle = document.getElementById('modalCartaoSubtitle');
        const modalHeader = modal?.querySelector('.modal-header');

        if (!overlay || !modal || !form || !titulo || !subtitle) {
            return;
        }

        if (typeof mode !== 'string') {
            const matchedCard = STATE.cartoes.find((cartao) => cartao.id === Number(mode));
            if (matchedCard) {
                cartaoData = matchedCard;
                mode = 'edit';
            } else {
                mode = 'create';
            }
        }

        form.reset();
        document.getElementById('cartaoId').value = '';
        document.getElementById('limiteTotal').value = '0,00';
        document.getElementById('contaVinculada').value = '';
        document.getElementById('cartaoCanalInapp').checked = true;
        document.getElementById('cartaoCanalEmail').checked = false;
        CartoesUI.syncReminderChannels();

        const contasDisponiveis = await Modules.API.loadContasSelect();
        const isEdit = mode === 'edit' && !!cartaoData;

        if (isEdit && cartaoData) {
            titulo.textContent = 'Editar cartao de credito';
            subtitle.textContent = 'Revise os dados e ajuste limite, vencimento ou conta vinculada.';
            document.getElementById('cartaoId').value = cartaoData.id;
            document.getElementById('nomeCartao').value = cartaoData.nome_cartao || '';
            document.getElementById('contaVinculada').value = cartaoData.conta_id || '';
            document.getElementById('bandeira').value = cartaoData.bandeira || '';
            document.getElementById('ultimosDigitos').value = cartaoData.ultimos_digitos || '';
            document.getElementById('limiteTotal').value = CartoesUI.formatMoneyValue(cartaoData.limite_total || 0);
            document.getElementById('diaFechamento').value = cartaoData.dia_fechamento || '';
            document.getElementById('diaVencimento').value = cartaoData.dia_vencimento || '';
            document.getElementById('cartaoLembreteAviso').value = cartaoData.lembrar_fatura_antes_segundos || '';
            document.getElementById('cartaoCanalInapp').checked = cartaoData.fatura_canal_inapp !== false && cartaoData.fatura_canal_inapp !== 0;
            document.getElementById('cartaoCanalEmail').checked = Boolean(cartaoData.fatura_canal_email);

            if (modalHeader) {
                modalHeader.style.background = getCardColor(cartaoData);
            }
        } else {
            titulo.textContent = 'Novo cartao de credito';
            subtitle.textContent = contasDisponiveis
                ? 'Cadastre o cartao e vincule a conta usada para pagar a fatura.'
                : 'Antes de cadastrar um cartao, voce precisa ter ao menos uma conta.';

            if (modalHeader) {
                modalHeader.style.background = '';
            }
        }

        CartoesUI.syncReminderChannels();
        CartoesUI.setModalSubmitState(false, isEdit);

        overlay.classList.add('active');
        CartoesUI.setScrollLock(true);

        setTimeout(() => {
            document.getElementById(contasDisponiveis ? 'nomeCartao' : 'contaVinculada')?.focus();
        }, 80);
    },

    closeModal() {
        const overlay = document.getElementById('modalCartaoOverlay');
        if (!overlay) {
            return;
        }

        overlay.classList.remove('active');
        CartoesUI.setScrollLock(false);

        const modalHeader = document.querySelector('#modalCartao .modal-header');
        if (modalHeader) {
            modalHeader.style.background = '';
        }

        STATE.isSaving = false;
        CartoesUI.setModalSubmitState(false, false);

        setTimeout(() => {
            document.getElementById('formCartao')?.reset();
            document.getElementById('cartaoId').value = '';
            document.getElementById('limiteTotal').value = '0,00';
            CartoesUI.syncReminderChannels();
        }, 180);
    },

    setupCardActions() {
        document.querySelectorAll('.credit-card').forEach((card) => {
            card.addEventListener('click', (event) => {
                if (event.target.closest('.card-action-btn, .lk-info')) {
                    return;
                }

                const id = parseInt(card.dataset.id, 10);
                if (Number.isFinite(id)) {
                    CartoesUI.showCardDetails(id);
                }
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                const id = parseInt(card.dataset.id, 10);
                if (Number.isFinite(id)) {
                    CartoesUI.showCardDetails(id);
                }
            });
        });
    },

    async showCardDetails(id) {
        const cartao = STATE.cartoes.find((item) => item.id === id);
        if (!cartao) {
            return;
        }

        if (window.LK_CardDetail?.open) {
            window.LK_CardDetail.open(
                id,
                cartao.nome_cartao || cartao.nome || 'Cartao',
                getCardColor(cartao),
                getCurrentMonthKey()
            );
            return;
        }

        Modules.Fatura?.verFatura?.(id);
    },

    async exportarRelatorio() {
        if (!STATE.filteredCartoes?.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Nenhum cartao para exportar',
                    text: 'Adicione cartoes ou altere os filtros.',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
            return;
        }

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const dataAtual = new Date();
            const mesAno = dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });

            const limiteTotal = STATE.filteredCartoes.reduce((sum, c) => sum + parseFloat(c.limite_total || 0), 0);
            const limiteDisponivel = STATE.filteredCartoes.reduce((sum, c) => sum + parseFloat((c.limite_disponivel_real ?? c.limite_disponivel) || 0), 0);
            const limiteUtilizado = limiteTotal - limiteDisponivel;
            const percentualGeral = limiteTotal > 0 ? (limiteUtilizado / limiteTotal * 100).toFixed(1) : 0;

            const primaryColor = [230, 126, 34];
            const darkColor = [26, 31, 46];
            const lightGray = [248, 249, 250];

            doc.setFillColor(...primaryColor);
            doc.rect(0, 0, 210, 35, 'F');

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.text('RELATORIO DE CARTOES DE CREDITO', 105, 15, { align: 'center' });

            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text(`Periodo: ${mesAno}`, 105, 22, { align: 'center' });
            doc.text(`Gerado em: ${dataAtual.toLocaleDateString('pt-BR')} as ${dataAtual.toLocaleTimeString('pt-BR')}`, 105, 28, { align: 'center' });

            let yPos = 45;
            doc.setTextColor(...darkColor);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('RESUMO FINANCEIRO', 14, yPos);

            yPos += 8;
            doc.autoTable({
                startY: yPos,
                head: [['Indicador', 'Valor']],
                body: [
                    ['Total de Cartoes', STATE.filteredCartoes.length.toString()],
                    ['Limite Total Combinado', Utils.formatMoney(limiteTotal)],
                    ['Limite Utilizado', Utils.formatMoney(limiteUtilizado)],
                    ['Limite Disponivel', Utils.formatMoney(limiteDisponivel)],
                    ['Percentual de Utilizacao', `${percentualGeral}%`]
                ],
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                columnStyles: {
                    0: { cellWidth: 100, fontStyle: 'bold' },
                    1: { cellWidth: 86, halign: 'right' }
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            yPos = doc.lastAutoTable.finalY + 15;
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('DETALHAMENTO POR CARTAO', 14, yPos);

            yPos += 5;
            const tableData = STATE.filteredCartoes.map((cartao) => {
                const limiteDisp = cartao.limite_disponivel_real ?? cartao.limite_disponivel ?? 0;
                const percentualUso = cartao.limite_total > 0
                    ? ((cartao.limite_total - limiteDisp) / cartao.limite_total * 100).toFixed(1)
                    : 0;

                return [
                    cartao.nome_cartao,
                    Utils.formatBandeira(cartao.bandeira),
                    `**** ${cartao.ultimos_digitos}`,
                    Utils.formatMoney(cartao.limite_total),
                    Utils.formatMoney(limiteDisp),
                    `${percentualUso}%`,
                    cartao.ativo ? 'Ativo' : 'Inativo'
                ];
            });

            doc.autoTable({
                startY: yPos,
                head: [['Cartao', 'Bandeira', 'Final', 'Limite Total', 'Disponivel', 'Uso', 'Status']],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 40 },
                    1: { cellWidth: 25, halign: 'center' },
                    2: { cellWidth: 25, halign: 'center' },
                    3: { cellWidth: 28, halign: 'right' },
                    4: { cellWidth: 28, halign: 'right' },
                    5: { cellWidth: 18, halign: 'center' },
                    6: { cellWidth: 22, halign: 'center' }
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    `Pagina ${i} de ${pageCount} | Lukrato - Sistema de Gestao Financeira`,
                    105,
                    287,
                    { align: 'center' }
                );
            }

            doc.save(`relatorio_cartoes_${dataAtual.toISOString().split('T')[0]}.pdf`);
            Utils.showToast('success', 'Relatorio exportado com sucesso');
        } catch (error) {
            console.error('Erro ao exportar:', error);
            Utils.showToast('error', 'Erro ao exportar relatorio');
        }
    },
};

Modules.UI = CartoesUI;
