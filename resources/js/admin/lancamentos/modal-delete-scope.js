const DELETE_SCOPE_CONTENT = {
    single: {
        title: 'Excluir lançamento',
        subtitle: 'Revise a exclusão antes de confirmar.',
        lead: 'Esta ação não pode ser desfeita.',
        hint: 'O lançamento será removido permanentemente.',
        showOptions: false,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lançamento',
                text: 'Remove somente o registro atual.'
            }
        }
    },
    recorrencia: {
        title: 'Excluir lançamento',
        subtitle: 'Este lançamento faz parte de uma recorrencia.',
        lead: 'Escolha o alcance da exclusão para a serie.',
        hint: 'Ao excluir futuros, somente itens ainda nao pagos serao removidos.',
        showOptions: true,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lançamento',
                text: 'Remove somente a ocorrencia atual.'
            },
            future: {
                title: 'Este e os futuros nao pagos',
                text: 'Mantem historico e itens da serie que ja foram pagos.'
            },
            all: {
                title: 'Toda a recorrencia',
                text: 'Encerra a serie completa vinculada a este lançamento.'
            }
        }
    },
    parcelamento: {
        title: 'Excluir lançamento',
        subtitle: 'Este lançamento faz parte de um parcelamento.',
        lead: 'Escolha como deseja remover as parcelas dessa compra.',
        hint: 'Parcelas ja pagas nao entram na exclusao em massa.',
        showOptions: true,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lançamento',
                text: 'Remove somente esta parcela da serie.'
            },
            future: {
                title: 'Esta e as proximas não pagas',
                text: 'Remove a parcela atual e as seguintes ainda pendentes.'
            },
            all: {
                title: 'Todo o parcelamento',
                text: 'Exclui a compra parcelada completa vinculada a esta serie.'
            }
        }
    },
    parcelamentoCascade: {
        title: 'Excluir parcelamento',
        subtitle: 'voce esta gerenciando um parcelamento inteiro.',
        lead: 'Escolha se deseja cancelar apenas parcelas pendentes ou a serie completa.',
        hint: 'Excluir tudo remove inclusive parcelas ja pagas.',
        showOptions: true,
        confirmLabel: 'Excluir parcelamento',
        defaultScope: 'future',
        options: {
            future: {
                value: 'unpaid',
                title: 'Apenas parcelas pendentes',
                text: 'Mantem no historico as parcelas desse parcelamento que ja foram pagas.'
            },
            all: {
                value: 'all',
                title: 'Todo o parcelamento',
                text: 'Remove inclusive parcelas quitadas dessa compra parcelada.'
            }
        }
    }
};

export function attachLancamentosModalDeleteScope(ModalManager, dependencies) {
    const {
        DOM,
        STATE,
    } = dependencies;

    Object.assign(ModalManager, {
        ensureDeleteScopeModal: () => {
            if (STATE.modalDeleteScope) return STATE.modalDeleteScope;
            if (!DOM.modalDeleteScopeEl || !window.bootstrap?.Modal) return null;

            window.LK?.modalSystem?.prepareBootstrapModal(DOM.modalDeleteScopeEl, { scope: 'page' });

            if (!DOM.modalDeleteScopeEl.dataset.bound) {
                DOM.modalDeleteScopeEl.dataset.bound = '1';

                DOM.deleteScopeForm?.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const scopeInput = DOM.deleteScopeOptions?.querySelector('input[name="deleteScopeOption"]:checked');
                    STATE.deleteScopeResult = {
                        scope: scopeInput?.value || 'single'
                    };

                    STATE.modalDeleteScope?.hide();
                });

                DOM.modalDeleteScopeEl.addEventListener('hidden.bs.modal', () => {
                    const resolve = STATE.deleteScopeResolver;
                    const result = STATE.deleteScopeResult;

                    STATE.deleteScopeResolver = null;
                    STATE.deleteScopeResult = null;
                    ModalManager.resetDeleteScopeModal();

                    if (typeof resolve === 'function') {
                        resolve(result || null);
                    }
                });
            }

            STATE.modalDeleteScope = window.bootstrap.Modal.getOrCreateInstance(DOM.modalDeleteScopeEl);
            return STATE.modalDeleteScope;
        },

        resetDeleteScopeModal: () => {
            DOM.deleteScopeForm?.reset();

            const defaultInput = DOM.deleteScopeOptions?.querySelector('input[value="single"]');
            if (defaultInput) defaultInput.checked = true;

            if (DOM.deleteScopeOptions) {
                DOM.deleteScopeOptions.hidden = false;
            }
        },

        populateDeleteScopeModal: (mode = 'single') => {
            const content = DELETE_SCOPE_CONTENT[mode] || DELETE_SCOPE_CONTENT.single;

            if (DOM.deleteScopeTitle) DOM.deleteScopeTitle.textContent = content.title || 'Excluir lancamento';
            if (DOM.deleteScopeSubtitle) DOM.deleteScopeSubtitle.textContent = content.subtitle;
            if (DOM.deleteScopeLead) DOM.deleteScopeLead.textContent = content.lead;
            if (DOM.deleteScopeHint) DOM.deleteScopeHint.textContent = content.hint;
            if (DOM.btnConfirmDeleteScope) DOM.btnConfirmDeleteScope.textContent = content.confirmLabel;
            if (DOM.deleteScopeOptions) DOM.deleteScopeOptions.hidden = !content.showOptions;

            ['single', 'future', 'all'].forEach((scopeKey) => {
                const optionEl = DOM.deleteScopeOptions?.querySelector(`[data-delete-scope-option="${scopeKey}"]`);
                const inputEl = optionEl?.querySelector('input[name="deleteScopeOption"]');
                const titleEl = DOM.deleteScopeOptions?.querySelector(`[data-delete-scope-title="${scopeKey}"]`);
                const textEl = DOM.deleteScopeOptions?.querySelector(`[data-delete-scope-text="${scopeKey}"]`);
                const copy = content.options?.[scopeKey];

                if (optionEl) optionEl.hidden = !copy;
                if (!copy) return;

                if (inputEl) inputEl.value = copy.value || scopeKey;
                if (titleEl) titleEl.textContent = copy.title;
                if (textEl) textEl.textContent = copy.text;
            });

            const defaultInput = DOM.deleteScopeOptions?.querySelector(
                `[data-delete-scope-option="${content.defaultScope || 'single'}"] input[name="deleteScopeOption"]`
            );
            if (defaultInput) defaultInput.checked = true;
        },

        openDeleteScopeModal: ({ mode = 'single' } = {}) => {
            const modal = ModalManager.ensureDeleteScopeModal();
            if (!modal) return Promise.resolve(null);

            if (typeof STATE.deleteScopeResolver === 'function') {
                STATE.deleteScopeResolver(null);
            }

            STATE.deleteScopeResolver = null;
            STATE.deleteScopeResult = null;
            ModalManager.populateDeleteScopeModal(mode);

            return new Promise((resolve) => {
                STATE.deleteScopeResolver = resolve;
                modal.show();

                requestAnimationFrame(() => {
                    const focusTarget = DOM.deleteScopeOptions?.hidden
                        ? DOM.btnConfirmDeleteScope
                        : DOM.deleteScopeOptions?.querySelector('input[name="deleteScopeOption"]:checked');
                    focusTarget?.focus?.();
                });
            });
        },
    });
}
