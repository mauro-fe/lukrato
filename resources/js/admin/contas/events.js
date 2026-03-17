/**
 * ============================================================================
 * LUKRATO - Contas / Events & Money Mask
 * ============================================================================
 * Master event wiring, keyboard shortcuts, filters, view toggle and
 * money mask helpers for conta inputs.
 * ============================================================================
 */

import { Utils, Modules, STATE } from './state.js';

export const ContasEvents = {
    refreshContas() {
        Modules.API.loadContas({ silent: STATE.contas.length > 0 });
    },

    applyFilters() {
        const searchInput = document.getElementById('contasSearchInput');
        const searchClear = document.getElementById('contasSearchClear');
        const typeFilter = document.getElementById('contasTypeFilter');

        STATE.searchQuery = searchInput?.value || '';
        STATE.typeFilter = typeFilter?.value || 'all';

        if (searchClear) {
            searchClear.classList.toggle('d-none', !STATE.searchQuery.trim());
        }

        Modules.Render.renderContas();
    },

    clearFilters() {
        const searchInput = document.getElementById('contasSearchInput');
        const searchClear = document.getElementById('contasSearchClear');
        const typeFilter = document.getElementById('contasTypeFilter');

        STATE.searchQuery = '';
        STATE.typeFilter = 'all';

        if (searchInput) searchInput.value = '';
        if (typeFilter) typeFilter.value = 'all';
        if (searchClear) searchClear.classList.add('d-none');

        Modules.Render.renderContas();
    },

    attachEventListeners() {
        Modules.Modal.attachCloseModalListeners();

        const btnNovaConta = document.getElementById('btnNovaConta');
        if (btnNovaConta && !btnNovaConta.dataset.listenerAdded) {
            btnNovaConta.addEventListener('click', () => {
                Modules.Modal.openModal('create');
            });
            btnNovaConta.dataset.listenerAdded = 'true';
        }

        const btnReload = document.getElementById('btnReload');
        if (btnReload && !btnReload.dataset.listenerAdded) {
            btnReload.addEventListener('click', () => {
                ContasEvents.refreshContas();
            });
            btnReload.dataset.listenerAdded = 'true';
        }

        const searchInput = document.getElementById('contasSearchInput');
        const searchClear = document.getElementById('contasSearchClear');
        const typeFilter = document.getElementById('contasTypeFilter');
        let searchTimer = null;

        if (searchInput && !searchInput.dataset.listenerAdded) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => ContasEvents.applyFilters(), 180);
            });
            searchInput.dataset.listenerAdded = 'true';
        }

        if (searchClear && !searchClear.dataset.listenerAdded) {
            searchClear.addEventListener('click', () => {
                ContasEvents.clearFilters();
            });
            searchClear.dataset.listenerAdded = 'true';
        }

        if (typeFilter && !typeFilter.dataset.listenerAdded) {
            typeFilter.addEventListener('change', () => {
                ContasEvents.applyFilters();
            });
            typeFilter.dataset.listenerAdded = 'true';
        }

        const formNovaInstituicao = document.getElementById('formNovaInstituicao');
        if (formNovaInstituicao && !formNovaInstituicao.dataset.listenerAdded) {
            formNovaInstituicao.addEventListener('submit', (event) => {
                event.preventDefault();
                Modules.Modal.handleNovaInstituicaoSubmit(event.target);
            });
            formNovaInstituicao.dataset.listenerAdded = 'true';
        }

        const corInstituicao = document.getElementById('corInstituicao');
        if (corInstituicao && !corInstituicao.dataset.listenerAdded) {
            corInstituicao.addEventListener('input', (event) => {
                Modules.Modal.updateColorPreview(event.target.value);
            });
            corInstituicao.dataset.listenerAdded = 'true';
        }

        const formConta = document.getElementById('formConta');
        if (formConta && !formConta.dataset.listenerAdded) {
            formConta.addEventListener('submit', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();
                Modules.Modal.handleFormSubmit(event.target);
            });
            formConta.dataset.listenerAdded = 'true';
        }

        ContasMoneyMask.setupMoneyMask();

        const moedaSelect = document.getElementById('moedaSelect');
        if (moedaSelect && !moedaSelect.dataset.listenerAdded) {
            moedaSelect.addEventListener('change', (event) => {
                Utils.updateCurrencySymbol(event.target.value);
            });
            moedaSelect.dataset.listenerAdded = 'true';
        }

        const btnAddInstituicao = document.getElementById('btnAddInstituicao');
        if (btnAddInstituicao && !btnAddInstituicao.dataset.listenerAdded) {
            btnAddInstituicao.addEventListener('click', () => {
                Modules.Modal.openNovaInstituicaoModal();
            });
            btnAddInstituicao.dataset.listenerAdded = 'true';
        }

        ContasEvents.initViewToggle();
        ContasEvents.attachContaCardListeners();

        const page = document.querySelector('.cont-page');
        if (page && !page.dataset.actionListenerAdded) {
            page.addEventListener('click', (event) => {
                const actionTarget = event.target.closest('[data-action]');
                if (!actionTarget) return;

                if (actionTarget.dataset.action === 'clear-contas-filters') {
                    event.preventDefault();
                    ContasEvents.clearFilters();
                }

                if (actionTarget.dataset.action === 'retry-load-contas') {
                    event.preventDefault();
                    ContasEvents.refreshContas();
                }

                if (actionTarget.dataset.action === 'create-first-account') {
                    event.preventDefault();
                    Modules.Modal.openModal('create');
                }
            });
            page.dataset.actionListenerAdded = 'true';
        }

        if (!document.body.dataset.contasEscapeBound) {
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    Modules.Modal.closeActiveOverlay();
                }
            });
            document.body.dataset.contasEscapeBound = 'true';
        }
    },

    initKeyboardShortcuts() {
        if (document.body.dataset.contasShortcutsBound) return;

        document.addEventListener('keydown', (event) => {
            const activeEl = document.activeElement;
            const isInputFocused = activeEl && (
                activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                activeEl.tagName === 'SELECT' ||
                activeEl.isContentEditable
            );
            const isModalOpen = document.querySelector('.modal.show, .lk-modal-overlay.active');

            if (isInputFocused || isModalOpen) return;

            if (event.key.toLowerCase() === 'n' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                Modules.Modal.openModal('create');
            }
        });

        document.body.dataset.contasShortcutsBound = 'true';
    },

    initViewToggle() {
        const viewToggle = document.querySelector('.view-toggle');
        const accountsGrid = document.getElementById('accountsGrid');
        const listHeader = document.getElementById('contasListHeader');

        if (!viewToggle || !accountsGrid) return;

        const viewButtons = viewToggle.querySelectorAll('.view-btn');
        const savedView = localStorage.getItem('contas_view_mode') || 'grid';

        if (savedView === 'list') {
            accountsGrid.classList.add('list-view');
            if (listHeader) listHeader.classList.add('visible');
        }

        ContasEvents.updateViewToggleState(viewButtons, savedView);

        viewButtons.forEach((btn) => {
            if (btn.dataset.listenerAdded) return;

            btn.addEventListener('click', () => {
                const view = btn.dataset.view;

                if (view === 'list') {
                    accountsGrid.classList.add('list-view');
                    if (listHeader) listHeader.classList.add('visible');
                } else {
                    accountsGrid.classList.remove('list-view');
                    if (listHeader) listHeader.classList.remove('visible');
                }

                localStorage.setItem('contas_view_mode', view);
                ContasEvents.updateViewToggleState(viewButtons, view);
            });

            btn.dataset.listenerAdded = 'true';
        });
    },

    updateViewToggleState(buttons, activeView) {
        buttons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.view === activeView);
        });
    },

    attachContaCardListeners() {
        const grid = document.getElementById('accountsGrid');
        if (!grid || grid.dataset.listenerAdded) return;

        grid.addEventListener('click', (event) => {
            const btn = event.target.closest('.btn-new-transaction');
            if (!btn) return;

            event.stopPropagation();
            event.preventDefault();

            const contaId = btn.dataset.contaId;
            if (window.lancamentoGlobalManager?.openModal) {
                window.lancamentoGlobalManager.openModal({
                    source: 'contas',
                    presetAccountId: contaId
                });
            }
        });

        grid.dataset.listenerAdded = 'true';
    },
};

export const ContasMoneyMask = {
    setupMoneyMask() {
        const saldoInput = document.getElementById('saldoInicial');
        if (!saldoInput || saldoInput.dataset.moneyMaskBound) return;

        saldoInput.addEventListener('input', (event) => {
            let value = event.target.value;
            value = value.replace(/[^\d-]/g, '');

            const isNegative = value.startsWith('-');
            value = value.replace('-', '');

            const number = parseInt(value, 10) || 0;
            event.target.value = Utils.formatMoneyInput(number, isNegative);
        });

        saldoInput.value = '0,00';
        saldoInput.dataset.moneyMaskBound = 'true';
    },
};

Modules.Events = ContasEvents;
Modules.MoneyMask = ContasMoneyMask;
