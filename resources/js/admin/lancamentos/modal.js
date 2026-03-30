/**
 * LUKRATO â€” LanÃ§amentos / ModalManager + OptionsManager
 */

import { CONFIG, DOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';
import { apiGet, getErrorMessage } from '../shared/api.js';
import { syncCustomSelects } from './custom-select.js';
import {
    getPlanningAlertsStore,
    resolvePlanningPeriod,
    isSamePlanningPeriod,
} from '../shared/planning-alerts.js';

const planningStore = getPlanningAlertsStore();
let editPlanningRenderSeq = 0;

function syncModalSelects(root) {
    if (!root) return;
    syncCustomSelects(root);
}

// ============================================================================
// GERENCIAMENTO DE OPÃ‡Ã•ES (SELECTS)
// ============================================================================

const OptionsManager = {
    populateCategoriaSelect: (select, tipo, selectedId) => {
        if (!select) return;

        const normalized = (tipo || '').toLowerCase();
        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

        select.innerHTML = '<option value="">Sem categoria</option>';

        const items = STATE.categoriaOptions.filter((item) => {
            if (!normalized) return true;
            return item.tipo === normalized;
        });

        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.nome;
            opt.dataset.tipo = item.tipo || '';
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });

        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Categoria indisponÃ­vel';
            fallback.selected = true;
            select.appendChild(fallback);
        }

        // Listener cascata subcategoria
        if (!select.dataset.subcatListenerAttached) {
            select.dataset.subcatListenerAttached = '1';
            select.addEventListener('change', () => {
                OptionsManager.populateSubcategoriaSelect(select.value);
            });
        }
    },

    /**
     * Preencher select de subcategoria com base na categoria selecionada (edit modal)
     */
    populateSubcategoriaSelect: async (categoriaId, selectedSubcatId) => {
        const select = DOM.selectLancSubcategoria;
        const group = DOM.subcategoriaGroup;
        if (!select) return;

        if (!categoriaId) {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.classList.add('hidden');
            return;
        }
        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/categorias/${categoriaId}/subcategorias`);
            const subs = [...(json?.data?.subcategorias ?? (Array.isArray(json?.data) ? json.data : []))]
                .sort((a, b) => String(a?.nome || '').localeCompare(String(b?.nome || ''), 'pt-BR', { sensitivity: 'base' }));

            const selectedVal = selectedSubcatId ? String(selectedSubcatId) : '';
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = String(sub.id);
                opt.textContent = sub.nome;
                if (selectedVal && String(sub.id) === selectedVal) opt.selected = true;
                select.appendChild(opt);
            });

            if (group) {
                if (subs.length > 0) group.classList.remove('hidden');
                else group.classList.add('hidden');
            }
        } catch {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.classList.add('hidden');
        }
    },
    populateContaSelect: (select, selectedId) => {
        if (!select) return;

        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

        select.innerHTML = '<option value="">Selecione</option>';

        STATE.contaOptions.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.label;
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });

        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Conta indisponÃ­vel';
            fallback.selected = true;
            select.appendChild(fallback);
        }
    },

    formatMetaOptionLabel: (meta) => {
        const titulo = String(meta?.titulo || 'Meta').trim();
        const status = String(meta?.status || '').trim().toLowerCase();

        if (status === 'concluida') return `${titulo} • Concluida`;
        if (status === 'pausada') return `${titulo} • Pausada`;
        if (status === 'cancelada') return `${titulo} • Cancelada`;

        const progresso = Number(meta?.progresso ?? 0);
        if (Number.isFinite(progresso) && progresso > 0) {
            return `${titulo} • ${Utils.formatPercent(progresso)}`;
        }

        return titulo;
    },

    populateMetaSelect: async (select, selectedId, options = {}) => {
        if (!select) return;

        const {
            emptyLabel = 'Nenhuma meta',
            fallbackLabel = 'Meta indisponivel'
        } = options;

        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';
        const metas = await planningStore.ensureMetas();

        select.innerHTML = `<option value="">${emptyLabel}</option>`;

        (Array.isArray(metas) ? metas : []).forEach((meta) => {
            const metaId = Number(meta?.id ?? 0);
            if (!metaId) return;

            const opt = document.createElement('option');
            opt.value = String(metaId);
            opt.textContent = OptionsManager.formatMetaOptionLabel(meta);
            opt.dataset.status = String(meta?.status || '').trim().toLowerCase();

            if (currentValue && String(metaId) === currentValue) {
                opt.selected = true;
            }

            select.appendChild(opt);
        });

        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = fallbackLabel;
            fallback.selected = true;
            select.appendChild(fallback);
        }
    },

    loadFilterOptions: async () => {
        const [categorias, contas] = await Promise.all([
            DOM.selectCategoria ? Modules.API.fetchJsonList(`${CONFIG.BASE_URL}api/categorias`) : Promise.resolve([]),
            DOM.selectConta ? Modules.API.fetchJsonList(`${CONFIG.BASE_URL}api/contas?only_active=1&with_balances=1`) : Promise.resolve([])
        ]);

        // Inicializar seletores de filtro
        if (DOM.selectCategoria) {
            DOM.selectCategoria.innerHTML = '<option value="">Categoria</option><option value="none">Sem categoria</option>';
        }
        if (DOM.selectConta) {
            DOM.selectConta.innerHTML = '<option value="">Conta</option>';
        }

        // Processar categorias
        if (DOM.selectCategoria && categorias.length) {
            STATE.categoriaOptions = categorias
                .map((cat) => ({
                    id: Number(cat?.id ?? 0),
                    nome: String(cat?.nome ?? '').trim(),
                    tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                }))
                .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

            const options = STATE.categoriaOptions
                .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                .join('');
            DOM.selectCategoria.insertAdjacentHTML('beforeend', options);
        }

        // Processar contas
        if (DOM.selectConta && contas.length) {
            STATE.contaOptions = contas
                .map((acc) => {
                    const id = Number(acc?.id ?? 0);
                    const nome = String(acc?.nome ?? '').trim();
                    const instituicao = String(acc?.instituicao ?? '').trim();
                    const label = nome || instituicao || `Conta #${id}`;
                    const saldo = Number(acc?.saldoAtual ?? acc?.saldo ?? acc?.saldo_inicial ?? 0);
                    return { id, label, nome, saldo };
                })
                .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

            const options = STATE.contaOptions
                .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                .join('');
            DOM.selectConta.insertAdjacentHTML('beforeend', options);
        }

        // Populate export selects with same data
        if (DOM.exportConta) {
            DOM.exportConta.innerHTML = '<option value="">Todas</option>';
            if (STATE.contaOptions.length) {
                const opts = STATE.contaOptions
                    .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                    .join('');
                DOM.exportConta.insertAdjacentHTML('beforeend', opts);
            }
        }
        if (DOM.exportCategoria) {
            DOM.exportCategoria.innerHTML = '<option value="">Todas</option>';
            if (STATE.categoriaOptions.length) {
                const opts = STATE.categoriaOptions
                    .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                    .join('');
                DOM.exportCategoria.insertAdjacentHTML('beforeend', opts);
            }
        }

        // Atualizar selects do modal se existirem
        if (DOM.selectLancConta) {
            OptionsManager.populateContaSelect(DOM.selectLancConta, DOM.selectLancConta.value || null);
        }
        if (DOM.selectTransConta) {
            OptionsManager.populateContaSelect(DOM.selectTransConta, DOM.selectTransConta.value || null);
        }
        if (DOM.selectTransContaDestino) {
            OptionsManager.populateContaSelect(DOM.selectTransContaDestino, DOM.selectTransContaDestino.value || null);
        }
        if (DOM.selectLancCategoria) {
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo?.value || '',
                DOM.selectLancCategoria.value || null
            );
        }
    }
};

// ============================================================================
// GERENCIAMENTO DE MODAIS
// ============================================================================

const DELETE_SCOPE_CONTENT = {
    single: {
        title: 'Excluir lancamento',
        subtitle: 'Revise a exclusao antes de confirmar.',
        lead: 'Esta acao nao pode ser desfeita.',
        hint: 'O lancamento sera removido permanentemente.',
        showOptions: false,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lancamento',
                text: 'Remove somente o registro atual.'
            }
        }
    },
    recorrencia: {
        title: 'Excluir lancamento',
        subtitle: 'Este lancamento faz parte de uma recorrencia.',
        lead: 'Escolha o alcance da exclusao para a serie.',
        hint: 'Ao excluir futuros, somente itens ainda nao pagos serao removidos.',
        showOptions: true,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lancamento',
                text: 'Remove somente a ocorrencia atual.'
            },
            future: {
                title: 'Este e os futuros nao pagos',
                text: 'Mantem historico e itens da serie que ja foram pagos.'
            },
            all: {
                title: 'Toda a recorrencia',
                text: 'Encerra a serie completa vinculada a este lancamento.'
            }
        }
    },
    parcelamento: {
        title: 'Excluir lancamento',
        subtitle: 'Este lancamento faz parte de um parcelamento.',
        lead: 'Escolha como deseja remover as parcelas dessa compra.',
        hint: 'Parcelas ja pagas nao entram na exclusao em massa.',
        showOptions: true,
        confirmLabel: 'Excluir',
        defaultScope: 'single',
        options: {
            single: {
                title: 'Apenas este lancamento',
                text: 'Remove somente esta parcela da serie.'
            },
            future: {
                title: 'Esta e as proximas nao pagas',
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
        subtitle: 'você esta gerenciando um parcelamento inteiro.',
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

const ModalManager = {
    ensureLancModal: () => {
        if (STATE.modalEditLanc) return STATE.modalEditLanc;
        if (!DOM.modalEditLancEl) return null;

        if (window.bootstrap?.Modal) {
            if (DOM.modalEditLancEl.parentElement && DOM.modalEditLancEl.parentElement !== document.body) {
                document.body.appendChild(DOM.modalEditLancEl);
            }
            STATE.modalEditLanc = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditLancEl);
            return STATE.modalEditLanc;
        }
        return null;
    },

    ensureDeleteScopeModal: () => {
        if (STATE.modalDeleteScope) return STATE.modalDeleteScope;
        if (!DOM.modalDeleteScopeEl || !window.bootstrap?.Modal) return null;

        if (DOM.modalDeleteScopeEl.parentElement && DOM.modalDeleteScopeEl.parentElement !== document.body) {
            document.body.appendChild(DOM.modalDeleteScopeEl);
        }

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

    /**
     * Sugerir categoria usando IA no modal de ediÃ§Ã£o
     */
    sugerirCategoriaIA: async () => {
        await _sugerirCategoriaIA({
            descricaoInputId: 'editLancDescricao',
            categoriaSelectId: 'editLancCategoria',
            subcategoriaSelectId: 'editLancSubcategoria',
            subcategoriaGroupId: 'editSubcategoriaGroup',
            btnId: 'btnEditAiSuggestCategoria',
            notify: (msg, type) => {
                const iconMap = { success: 'success', warning: 'warning', error: 'error' };
                Notifications.toast(msg, iconMap[type] || 'info');
            },
        });
        void ModalManager.renderPlanningAlerts();
    },

    clearLancAlert: () => {
        if (!DOM.editLancAlert) return;
        DOM.editLancAlert.classList.add('d-none');
        DOM.editLancAlert.textContent = '';
    },

    showLancAlert: (msg) => {
        if (!DOM.editLancAlert) return;
        DOM.editLancAlert.textContent = msg;
        DOM.editLancAlert.classList.remove('d-none');
    },

    getEditSelectLabel: (select, fallback = '') => {
        if (!select) return fallback;

        const currentValue = String(select.value || '').trim();
        if (!currentValue) return fallback;

        const option = select.options?.[select.selectedIndex];
        const label = String(option?.textContent || '').trim();
        return label || fallback;
    },

    canLinkMetaInLancamentoEdit: (tipo, formaPagamento = '') => {
        const normalizedTipo = String(tipo || '').toLowerCase().trim();
        const normalizedPayment = String(formaPagamento || '').toLowerCase().trim();
        return normalizedTipo === 'receita' && normalizedPayment !== 'estorno_cartao';
    },

    getSelectedMetaId: (select) => {
        const raw = String(select?.value || '').trim();
        const parsed = Number(raw);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    },

    resolveMetaTitle: (metaId, fallback = '') => {
        const title = String(planningStore.getMetaById(metaId)?.titulo || '').trim();
        return title || String(fallback || '').trim() || 'Meta';
    },

    syncEditMetaField: () => {
        const snapshot = STATE.editingLancamentoData || {};
        const tipo = DOM.selectLancTipo?.value || snapshot?.tipo || '';
        const formaPagamento = DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '';
        const shouldShow = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento);

        if (DOM.editLancMetaGroup) {
            DOM.editLancMetaGroup.hidden = !shouldShow;
        }

        if (!shouldShow && DOM.selectLancMeta) {
            DOM.selectLancMeta.value = '';
        }
    },

    getEditStatusMeta: () => {
        const data = STATE.editingLancamentoData || {};
        if (data?.cancelado_em) {
            return { label: 'Cancelado', className: 'is-neutral' };
        }

        const rawPago = data?.pago;
        const isPago = !(rawPago === false || rawPago === 0 || rawPago === '0' || rawPago === 'false');
        return isPago
            ? { label: 'Pago', className: 'is-success' }
            : { label: 'Pendente', className: 'is-warning' };
    },

    formatEditSummaryDateTime: (dataValue, horaValue) => {
        const dateLabel = dataValue ? Utils.fmtDate(dataValue) : 'Sem data';
        return horaValue ? `${dateLabel} às ${horaValue}` : dateLabel;
    },

    syncEditSummary: () => {
        const snapshot = STATE.editingLancamentoData || {};
        const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || 'despesa').toLowerCase();
        const descricao = String(DOM.inputLancDescricao?.value || snapshot?.descricao || '').trim();
        const contaLabel = ModalManager.getEditSelectLabel(DOM.selectLancConta, 'Conta não definida');
        const categoriaLabel = ModalManager.getEditSelectLabel(DOM.selectLancCategoria, 'Sem categoria');
        const formaPagamento = DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '';
        const canLinkMeta = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento);
        const metaLabel = canLinkMeta
            ? ModalManager.getEditSelectLabel(DOM.selectLancMeta, Utils.getLancamentoMetaTitle(snapshot))
            : '';
        const dataValue = DOM.inputLancData?.value || snapshot?.data || '';
        const horaValue = DOM.inputLancHora?.value || snapshot?.hora_lancamento || '';
        const valorAtual = DOM.inputLancValor?.value
            ? MoneyMask.unformat(DOM.inputLancValor.value)
            : Math.abs(Number(snapshot?.valor ?? 0));
        const statusMeta = ModalManager.getEditStatusMeta();

        if (DOM.editLancSummaryTitle) {
            DOM.editLancSummaryTitle.textContent = descricao || 'Sem descrição informada';
        }

        if (DOM.editLancSummaryMeta) {
            const meta = [
                contaLabel,
                categoriaLabel,
                ModalManager.formatEditSummaryDateTime(dataValue, horaValue),
                metaLabel ? `Meta: ${metaLabel}` : ''
            ].filter(Boolean).join(' • ');

            DOM.editLancSummaryMeta.textContent = meta || 'Conta, categoria e data aparecem aqui.';
        }

        if (DOM.editLancSummaryTipo) {
            DOM.editLancSummaryTipo.textContent = tipo === 'receita' ? 'Receita' : 'Despesa';
            DOM.editLancSummaryTipo.classList.remove('is-receita', 'is-despesa');
            DOM.editLancSummaryTipo.classList.add(tipo === 'receita' ? 'is-receita' : 'is-despesa');
        }

        if (DOM.editLancSummaryStatus) {
            DOM.editLancSummaryStatus.textContent = statusMeta.label;
            DOM.editLancSummaryStatus.classList.remove('is-success', 'is-warning', 'is-neutral');
            DOM.editLancSummaryStatus.classList.add(statusMeta.className);
        }

        if (DOM.editLancSummaryValor) {
            const valor = Number.isFinite(Number(valorAtual)) ? Math.abs(Number(valorAtual)) : 0;
            DOM.editLancSummaryValor.textContent = MoneyMask.format(valor);
        }
    },

    resetEditSummary: () => {
        if (DOM.editLancSummaryTitle) {
            DOM.editLancSummaryTitle.textContent = 'Sem descrição informada';
        }

        if (DOM.editLancSummaryMeta) {
            DOM.editLancSummaryMeta.textContent = 'Conta, categoria e data aparecem aqui.';
        }

        if (DOM.editLancSummaryTipo) {
            DOM.editLancSummaryTipo.textContent = 'Despesa';
            DOM.editLancSummaryTipo.classList.remove('is-receita', 'is-despesa');
            DOM.editLancSummaryTipo.classList.add('is-despesa');
        }

        if (DOM.editLancSummaryStatus) {
            DOM.editLancSummaryStatus.textContent = 'Pendente';
            DOM.editLancSummaryStatus.classList.remove('is-success', 'is-warning', 'is-neutral');
            DOM.editLancSummaryStatus.classList.add('is-warning');
        }

        if (DOM.editLancSummaryValor) {
            DOM.editLancSummaryValor.textContent = 'R$ 0,00';
        }
    },

    clearPlanningAlerts: () => {
        if (!DOM.editLancPlanningAlerts) return;
        DOM.editLancPlanningAlerts.innerHTML = '';
        DOM.editLancPlanningAlerts.hidden = true;
    },

    summarizeMetaTitles: (metas = []) => {
        const titles = metas
            .map((meta) => String(meta?.titulo || '').trim())
            .filter(Boolean);

        if (titles.length === 0) return 'suas metas';
        if (titles.length === 1) return titles[0];
        if (titles.length === 2) return `${titles[0]} e ${titles[1]}`;
        return `${titles[0]}, ${titles[1]} e mais ${titles.length - 2}`;
    },

    buildPlanningAlertCard: ({ tone = 'info', icon = 'target', eyebrow, title, message }) => `
        <div class="lk-planning-alert is-${tone}">
            <div class="lk-planning-alert__icon">
                <i data-lucide="${icon}"></i>
            </div>
            <div class="lk-planning-alert__body">
                <span class="lk-planning-alert__eyebrow">${Utils.escapeHtml(eyebrow || 'Planejamento')}</span>
                <strong class="lk-planning-alert__title">${Utils.escapeHtml(title || '')}</strong>
                <p class="lk-planning-alert__message">${Utils.escapeHtml(message || '')}</p>
            </div>
        </div>
    `,

    buildEditMetaAlert: () => {
        const snapshot = STATE.editingLancamentoData || {};
        const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || 'despesa').toLowerCase();
        const formaPagamento = String(DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '').toLowerCase();
        const valorAtual = DOM.inputLancValor?.value
            ? Math.abs(Number(MoneyMask.unformat(DOM.inputLancValor.value)))
            : Math.abs(Number(snapshot?.valor ?? 0));
        const selectedMetaId = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento)
            ? ModalManager.getSelectedMetaId(DOM.selectLancMeta)
            : null;
        const originalMetaId = Number(snapshot?.meta_id ?? 0) > 0 ? Number(snapshot.meta_id) : null;
        const originalMetaTitle = Utils.getLancamentoMetaTitle(snapshot);
        const originalTipo = String(snapshot?.tipo || '').toLowerCase();
        const originalFormaPagamento = String(snapshot?.forma_pagamento || '').toLowerCase();
        const originalContribution = originalMetaId && ModalManager.canLinkMetaInLancamentoEdit(originalTipo, originalFormaPagamento)
            ? Math.abs(Number(snapshot?.valor ?? 0))
            : 0;

        if (!selectedMetaId && !originalMetaId) {
            return '';
        }

        if (selectedMetaId) {
            const selectedMeta = planningStore.getMetaById(selectedMetaId);
            const selectedTitle = ModalManager.resolveMetaTitle(
                selectedMetaId,
                ModalManager.getEditSelectLabel(DOM.selectLancMeta, originalMetaTitle)
            );
            const targetValue = Number(selectedMeta?.valor_alvo ?? 0);
            const currentAllocated = Number(selectedMeta?.valor_alocado ?? 0);
            const replacingOriginal = originalMetaId === selectedMetaId ? originalContribution : 0;
            const projectedAllocation = Math.max(0, currentAllocated - replacingOriginal + valorAtual);
            const progressPercent = targetValue > 0
                ? Math.max(0, Math.min(100, (projectedAllocation / targetValue) * 100))
                : null;
            const remainingValue = targetValue > 0
                ? Math.max(0, targetValue - projectedAllocation)
                : 0;

            let tone = projectedAllocation >= targetValue && targetValue > 0 ? 'success' : 'info';
            if (progressPercent !== null && progressPercent >= 80 && projectedAllocation < targetValue) {
                tone = 'warning';
            }

            let message = `Ao salvar, este lancamento vai alocar ${Utils.fmtMoney(valorAtual)} em ${selectedTitle}.`;

            if (targetValue > 0) {
                message += ` Total projetado: ${Utils.fmtMoney(projectedAllocation)} de ${Utils.fmtMoney(targetValue)} (${Utils.formatPercent(progressPercent)}).`;
                if (remainingValue > 0) {
                    message += ` Faltariam ${Utils.fmtMoney(remainingValue)} para concluir.`;
                } else {
                    message += ' A meta fica concluida automaticamente.';
                }
            }

            if (originalMetaId && originalMetaId !== selectedMetaId) {
                message += ` O vinculo anterior com ${ModalManager.resolveMetaTitle(originalMetaId, originalMetaTitle)} sera removido.`;
            }

            return ModalManager.buildPlanningAlertCard({
                tone,
                icon: 'target',
                eyebrow: 'Meta vinculada',
                title: `Aporte em ${selectedTitle}`,
                message
            });
        }

        if (!originalMetaId || !originalContribution) {
            return '';
        }

        const originalMeta = planningStore.getMetaById(originalMetaId);
        const resolvedOriginalTitle = ModalManager.resolveMetaTitle(originalMetaId, originalMetaTitle);
        const targetValue = Number(originalMeta?.valor_alvo ?? 0);
        const currentAllocated = Number(originalMeta?.valor_alocado ?? 0);
        const projectedAllocation = Math.max(0, currentAllocated - originalContribution);
        const progressPercent = targetValue > 0
            ? Math.max(0, Math.min(100, (projectedAllocation / targetValue) * 100))
            : null;
        const remainingValue = targetValue > 0
            ? Math.max(0, targetValue - projectedAllocation)
            : 0;

        let message = `Ao salvar, este lancamento deixa de alocar ${Utils.fmtMoney(originalContribution)} em ${resolvedOriginalTitle}.`;

        if (targetValue > 0) {
            message += ` Total projetado da meta: ${Utils.fmtMoney(projectedAllocation)} de ${Utils.fmtMoney(targetValue)} (${Utils.formatPercent(progressPercent)}).`;
            if (remainingValue > 0) {
                message += ` Faltariam ${Utils.fmtMoney(remainingValue)} para concluir.`;
            }
        }

        return ModalManager.buildPlanningAlertCard({
            tone: 'warning',
            icon: 'target',
            eyebrow: 'Meta removida',
            title: `Sem vinculo com ${resolvedOriginalTitle}`,
            message
        });
    },

    buildEditBudgetAlert: async () => {
        const snapshot = STATE.editingLancamentoData || {};
        const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || '').toLowerCase();
        if (tipo !== 'despesa') return '';

        const categoriaId = DOM.selectLancCategoria?.value || snapshot?.categoria_id || '';
        if (!categoriaId) return '';

        const dataValue = DOM.inputLancData?.value || snapshot?.data || '';
        const period = resolvePlanningPeriod(dataValue);
        const orcamento = await planningStore.getBudgetByCategoria(categoriaId, dataValue);
        if (!orcamento) return '';

        const valorAtual = DOM.inputLancValor?.value
            ? Math.abs(Number(MoneyMask.unformat(DOM.inputLancValor.value)))
            : Math.abs(Number(snapshot?.valor ?? 0));
        const gastoAtual = Number(orcamento.gasto_real ?? 0);
        const limiteEfetivo = Number(orcamento.limite_efetivo ?? orcamento.valor_limite ?? 0);
        const mesmaCategoriaOriginal = String(snapshot?.categoria_id ?? '') === String(categoriaId);
        const mesmaCompetenciaOriginal = isSamePlanningPeriod(snapshot?.data, period);
        const originalContribution = mesmaCategoriaOriginal
            && mesmaCompetenciaOriginal
            && String(snapshot?.tipo || '').toLowerCase() === 'despesa'
            ? Math.abs(Number(snapshot?.valor ?? 0))
            : 0;
        const gastoBase = Math.max(0, gastoAtual - originalContribution);
        const gastoProjetado = gastoBase + valorAtual;
        const restante = Math.max(0, limiteEfetivo - gastoProjetado);
        const excesso = Math.max(0, gastoProjetado - limiteEfetivo);
        const percentual = limiteEfetivo > 0 ? (gastoProjetado / limiteEfetivo) * 100 : 0;
        const rollover = Number(orcamento.rollover_valor ?? 0);
        const categoriaNome = String(orcamento.categoria_nome || orcamento.categoria?.nome || 'categoria').trim();

        let tone = 'info';
        let title = `${categoriaNome} tem orcamento ativo`;
        let message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. Com este ajuste, restam ${Utils.fmtMoney(restante)} no periodo (${percentual.toFixed(1)}% usado).`;

        if (excesso > 0) {
            tone = 'danger';
            title = `${categoriaNome} estoura o orcamento`;
            message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. O gasto projetado vai para ${Utils.fmtMoney(gastoProjetado)} e passa ${Utils.fmtMoney(excesso)} do limite.`;
        } else if (percentual >= 80) {
            tone = 'warning';
            title = `${categoriaNome} entra em alerta`;
            message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. Depois deste ajuste, sobram ${Utils.fmtMoney(restante)} no periodo (${percentual.toFixed(1)}% usado).`;
        }

        if (rollover > 0) {
            message += ` O limite inclui ${Utils.fmtMoney(rollover)} de rollover.`;
        }

        return ModalManager.buildPlanningAlertCard({
            tone,
            icon: excesso > 0 ? 'triangle-alert' : 'wallet',
            eyebrow: 'Orcamento do periodo',
            title,
            message
        });
    },

    renderPlanningAlerts: async () => {
        if (!DOM.editLancPlanningAlerts) return;

        const renderId = ++editPlanningRenderSeq;
        await planningStore.ensureMetas();
        if (renderId !== editPlanningRenderSeq) return;

        const notices = [];
        const metaNotice = ModalManager.buildEditMetaAlert();
        if (metaNotice) notices.push(metaNotice);

        const budgetNotice = await ModalManager.buildEditBudgetAlert();
        if (renderId !== editPlanningRenderSeq) return;
        if (budgetNotice) notices.push(budgetNotice);

        if (!notices.length) {
            ModalManager.clearPlanningAlerts();
            return;
        }

        DOM.editLancPlanningAlerts.innerHTML = notices.join('');
        DOM.editLancPlanningAlerts.hidden = false;
        window.LK?.refreshIcons?.();
        window.lucide?.createIcons?.();
    },

    openEditLancamento: async (data) => {
        const modal = ModalManager.ensureLancModal();
        if (!modal || !Utils.canEditLancamento(data)) return;

        STATE.editingLancamentoId = data?.id ?? null;
        STATE.editingLancamentoData = data ? { ...data } : null;
        if (!STATE.editingLancamentoId) return;

        ModalManager.clearLancAlert();

        // Preencher campos
        if (DOM.inputLancData) {
            DOM.inputLancData.value = (data?.data || '').slice(0, 10);
        }

        if (DOM.inputLancHora) {
            DOM.inputLancHora.value = data?.hora_lancamento || '';
        }

        if (DOM.selectLancTipo) {
            const tipo = String(data?.tipo || '').toLowerCase();
            DOM.selectLancTipo.value = ['receita', 'despesa'].includes(tipo) ? tipo : 'despesa';
        }

        OptionsManager.populateContaSelect(DOM.selectLancConta, data?.conta_id ?? null);
        OptionsManager.populateCategoriaSelect(
            DOM.selectLancCategoria,
            DOM.selectLancTipo?.value || '',
            data?.categoria_id ?? null
        );

        // Subcategoria cascata
        if (data?.categoria_id) {
            OptionsManager.populateSubcategoriaSelect(data.categoria_id, data?.subcategoria_id ?? null);
        } else {
            // Reset subcategoria
            if (DOM.selectLancSubcategoria) DOM.selectLancSubcategoria.innerHTML = '<option value="">Sem subcategoria</option>';
            if (DOM.subcategoriaGroup) DOM.subcategoriaGroup.classList.add('hidden');
        }

        if (DOM.inputLancValor) {
            const valor = Math.abs(Number(data?.valor ?? 0));
            DOM.inputLancValor.value = Number.isFinite(valor) ? MoneyMask.format(valor) : '';
        }

        if (DOM.inputLancDescricao) {
            DOM.inputLancDescricao.value = data?.descricao || '';
        }

        if (DOM.selectLancFormaPagamento) {
            DOM.selectLancFormaPagamento.value = data?.forma_pagamento || '';
        }

        await OptionsManager.populateMetaSelect(
            DOM.selectLancMeta,
            data?.meta_id ?? null,
            { fallbackLabel: Utils.getLancamentoMetaTitle(data) || 'Meta vinculada' }
        );

        ModalManager.syncEditMetaField();
        syncModalSelects(DOM.modalEditLancEl);
        ModalManager.syncEditSummary();
        void ModalManager.renderPlanningAlerts();
        window.LK?.refreshIcons?.();
        modal.show();
    },

    // Transfer modal helpers
    ensureTransModal: () => {
        if (STATE.modalEditTrans) return STATE.modalEditTrans;
        if (!DOM.modalEditTransEl) return null;
        if (window.bootstrap?.Modal) {
            if (DOM.modalEditTransEl.parentElement && DOM.modalEditTransEl.parentElement !== document.body) {
                document.body.appendChild(DOM.modalEditTransEl);
            }
            STATE.modalEditTrans = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditTransEl);
            return STATE.modalEditTrans;
        }
        return null;
    },

    openEditTransferencia: async (data) => {
        const modal = ModalManager.ensureTransModal();
        if (!modal || !Utils.canEditLancamento(data)) return;

        STATE.editingLancamentoId = data?.id ?? null;
        STATE.editingLancamentoData = data ? { ...data } : null;
        if (!STATE.editingLancamentoId) return;

        // clear alerts
        if (DOM.editTransAlert) {
            DOM.editTransAlert.classList.add('d-none');
            DOM.editTransAlert.textContent = '';
        }

        // preencher campos
        if (DOM.inputTransData) DOM.inputTransData.value = (data?.data || '').slice(0, 10);
        if (DOM.inputTransValor) DOM.inputTransValor.value = MoneyMask.format(Math.abs(Number(data?.valor || 0)));
        if (DOM.inputTransDescricao) DOM.inputTransDescricao.value = data?.descricao || '';

        // popular selects de conta
        if (DOM.selectTransConta) OptionsManager.populateContaSelect(DOM.selectTransConta, data?.conta_id ?? null);
        if (DOM.selectTransContaDestino) OptionsManager.populateContaSelect(DOM.selectTransContaDestino, data?.conta_id_destino ?? null);
        await OptionsManager.populateMetaSelect(
            DOM.selectTransMeta,
            data?.meta_id ?? null,
            { fallbackLabel: Utils.getLancamentoMetaTitle(data) || 'Meta vinculada' }
        );

        syncModalSelects(DOM.modalEditTransEl);
        modal.show();
    },

    submitTransForm: async (ev) => {
        ev.preventDefault();
        if (!STATE.editingLancamentoId) return;

        if (DOM.editTransAlert) {
            DOM.editTransAlert.classList.add('d-none');
            DOM.editTransAlert.textContent = '';
        }

        const dataValue = DOM.inputTransData?.value || '';
        const valorValue = DOM.inputTransValor?.value || '';
        const contaOrigem = DOM.selectTransConta?.value || '';
        const contaDestino = DOM.selectTransContaDestino?.value || '';
        const descricao = (DOM.inputTransDescricao?.value || '').trim();
        const metaId = ModalManager.getSelectedMetaId(DOM.selectTransMeta);

        if (!dataValue) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Informe a data.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (!contaOrigem) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Selecione a conta de origem.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (!contaDestino) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Selecione a conta de destino.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (contaOrigem === contaDestino) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Conta de destino deve ser diferente da origem.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }

        const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
        if (!Number.isFinite(valorFloat) || valorFloat <= 0) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Informe um valor vÃ¡lido.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }

        const payload = {
            data: dataValue,
            tipo: 'transferencia',
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricao,
            conta_id: Number(contaOrigem),
            conta_id_destino: Number(contaDestino),
            forma_pagamento: 'transferencia',
            meta_id: metaId
        };

        const submitBtn = DOM.formTrans?.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');
        try {
            const json = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);
            if (json && json.success === false) {
                const msg = getErrorMessage({ data: json }, 'Falha ao atualizar transferencia.');
                throw new Error(msg);
            }

            ModalManager.ensureTransModal()?.hide();
            Notifications.toast('Transferência atualizada com sucesso!');
            await Modules.DataManager.load();

            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'update',
                    id: Number(STATE.editingLancamentoId)
                }
            }));
        } catch (err) {
            if (DOM.editTransAlert) {
                DOM.editTransAlert.textContent = getErrorMessage(err, 'Falha ao atualizar.');
                DOM.editTransAlert.classList.remove('d-none');
            }
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    },

    // Inicializa modal de visualizaÃ§Ã£o
    ensureViewModal: () => {
        if (!STATE.modalViewLanc && DOM.modalViewLancEl) {
            STATE.modalViewLanc = new bootstrap.Modal(DOM.modalViewLancEl);
        }
        return STATE.modalViewLanc;
    },

    // Abre modal de visualizaÃ§Ã£o de lanÃ§amento
    openViewLancamento: (data) => {
        const modal = ModalManager.ensureViewModal();
        if (!modal || !data) return;

        STATE.viewingLancamento = data;

        // Preencher ID oculto
        if (DOM.viewLancamentoId) {
            DOM.viewLancamentoId.textContent = data.id || '';
        }

        // Data
        if (DOM.viewLancData) {
            DOM.viewLancData.textContent = Utils.fmtDate(data.data);
        }

        // Tipo
        if (DOM.viewLancTipo) {
            const tipo = String(data.tipo || (data.eh_transferencia ? 'transferencia' : '')).toLowerCase();
            if (tipo === 'receita') {
                DOM.viewLancTipo.textContent = 'Receita';
                DOM.viewLancTipo.className = 'badge bg-success';
            } else if (tipo === 'transferencia' || data.eh_transferencia) {
                DOM.viewLancTipo.textContent = 'Transferencia';
                DOM.viewLancTipo.className = 'badge bg-info text-dark';
            } else {
                DOM.viewLancTipo.textContent = 'Despesa';
                DOM.viewLancTipo.className = 'badge bg-danger';
            }
        }

        // Valor
        if (DOM.viewLancValor) {
            DOM.viewLancValor.textContent = Utils.fmtMoney(Math.abs(data.valor || 0));
        }

        // Status
        if (DOM.viewLancStatus) {
            let statusLabel = 'Efetivado';
            let statusClass = 'bg-success';
            if (data.cancelado_em) {
                statusLabel = 'Cancelado';
                statusClass = 'bg-secondary';
            } else if (!data.pago) {
                statusLabel = 'Pendente';
                statusClass = 'bg-warning text-dark';
            }
            DOM.viewLancStatus.textContent = statusLabel;
            DOM.viewLancStatus.className = 'badge ' + statusClass;
        }

        // Categoria
        if (DOM.viewLancCategoria) {
            DOM.viewLancCategoria.textContent = data.categoria_nome || data.categoria || '-';
        }

        // Conta
        if (DOM.viewLancConta) {
            DOM.viewLancConta.textContent = data.conta_nome || data.conta || '-';
        }

        // CartÃ£o
        if (DOM.viewLancCartaoItem && DOM.viewLancCartao) {
            if (data.cartao_credito_id && data.cartao_nome) {
                DOM.viewLancCartaoItem.classList.remove('d-none');
                DOM.viewLancCartao.textContent = data.cartao_nome;
            } else {
                DOM.viewLancCartaoItem.classList.add('d-none');
            }
        }

        // Forma de Pagamento
        if (DOM.viewLancFormaPgtoItem && DOM.viewLancFormaPgto) {
            if (data.forma_pagamento) {
                DOM.viewLancFormaPgtoItem.classList.remove('d-none');
                DOM.viewLancFormaPgto.textContent = Utils.formatFormaPagamento ? Utils.formatFormaPagamento(data.forma_pagamento) : data.forma_pagamento;
            } else {
                DOM.viewLancFormaPgtoItem.classList.add('d-none');
            }
        }

        if (DOM.viewLancMetaItem && DOM.viewLancMeta) {
            const metaTitle = Utils.getLancamentoMetaTitle(data);
            if (metaTitle) {
                DOM.viewLancMetaItem.classList.remove('d-none');
                DOM.viewLancMetaItem.style.display = '';
                DOM.viewLancMeta.textContent = metaTitle;
            } else {
                DOM.viewLancMetaItem.classList.add('d-none');
                DOM.viewLancMetaItem.style.display = 'none';
            }
        }

        // DescriÃ§Ã£o
        if (DOM.viewLancDescricaoCard && DOM.viewLancDescricao) {
            if (data.descricao && data.descricao.trim()) {
                DOM.viewLancDescricaoCard.classList.remove('d-none');
                DOM.viewLancDescricao.textContent = data.descricao;
            } else {
                DOM.viewLancDescricaoCard.classList.add('d-none');
            }
        }

        // Parcelamento
        if (DOM.viewLancParcelamentoCard && DOM.viewLancParcela) {
            if (data.parcela_atual && data.total_parcelas) {
                DOM.viewLancParcelamentoCard.classList.remove('d-none');
                DOM.viewLancParcelamentoCard.style.display = '';
                DOM.viewLancParcela.textContent = `${data.parcela_atual}/${data.total_parcelas}`;
            } else {
                DOM.viewLancParcelamentoCard.classList.add('d-none');
                DOM.viewLancParcelamentoCard.style.display = 'none';
            }
        }

        // Lembrete
        if (DOM.viewLancLembreteCard) {
            const segundos = data.lembrar_antes_segundos;
            if (segundos && segundos > 0) {
                DOM.viewLancLembreteCard.classList.remove('d-none');
                DOM.viewLancLembreteCard.style.display = '';
                // Formatar tempo
                let tempoLabel = '';
                if (segundos >= 604800) tempoLabel = '1 semana antes';
                else if (segundos >= 259200) tempoLabel = '3 dias antes';
                else if (segundos >= 172800) tempoLabel = '2 dias antes';
                else if (segundos >= 86400) tempoLabel = '1 dia antes';
                else tempoLabel = `${Math.round(segundos / 3600)}h antes`;
                if (DOM.viewLancLembreteTempo) DOM.viewLancLembreteTempo.textContent = tempoLabel;
                // Canais
                let canais = [];
                if (data.canal_inapp) canais.push('App');
                if (data.canal_email) canais.push('E-mail');
                if (DOM.viewLancLembreteCanais) DOM.viewLancLembreteCanais.textContent = canais.join(', ') || 'Nenhum canal';
            } else {
                DOM.viewLancLembreteCard.classList.add('d-none');
                DOM.viewLancLembreteCard.style.display = 'none';
            }
        }

        modal.show();
    },

    submitEditForm: async (ev) => {
        ev.preventDefault();
        if (!STATE.editingLancamentoId) return;

        ModalManager.clearLancAlert();

        // Validar e coletar dados
        const dataValue = DOM.inputLancData?.value || '';
        const horaValue = DOM.inputLancHora?.value || '';
        const tipoValue = DOM.selectLancTipo?.value || '';
        const contaValue = DOM.selectLancConta?.value || '';
        const categoriaValue = DOM.selectLancCategoria?.value || '';
        const valorValue = DOM.inputLancValor?.value || '';
        const descricaoValue = (DOM.inputLancDescricao?.value || '').trim();
        const formaPagamentoValue = DOM.selectLancFormaPagamento?.value || '';
        const metaId = ModalManager.canLinkMetaInLancamentoEdit(tipoValue, formaPagamentoValue)
            ? ModalManager.getSelectedMetaId(DOM.selectLancMeta)
            : null;

        if (!dataValue) return ModalManager.showLancAlert('Informe a data do lanÃ§amento.');
        if (!tipoValue) return ModalManager.showLancAlert('Selecione o tipo do lanÃ§amento.');
        if (!contaValue) return ModalManager.showLancAlert('Selecione a conta.');

        const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
        if (!Number.isFinite(valorFloat) || valorFloat <= 0) {
            return ModalManager.showLancAlert('Informe um valor vÃ¡lido maior que zero.');
        }

        const payload = {
            data: dataValue,
            hora_lancamento: horaValue || null,
            tipo: tipoValue,
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricaoValue,
            conta_id: Number(contaValue),
            categoria_id: categoriaValue ? Number(categoriaValue) : null,
            subcategoria_id: DOM.selectLancSubcategoria?.value ? Number(DOM.selectLancSubcategoria.value) : null,
            forma_pagamento: formaPagamentoValue || null,
            meta_id: metaId
        };

        // Continuar com lÃ³gica normal de atualizaÃ§Ã£o...
        const submitBtn = DOM.formLanc.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');

        try {
            const json = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);

            if (json && json.success === false) {
                const msg = getErrorMessage({ data: json }, 'Falha ao atualizar lancamento.');
                throw new Error(msg);
            }

            ModalManager.ensureLancModal()?.hide();
            Notifications.toast('Lançamento atualizado com sucesso!');
            await Modules.DataManager.load();

            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'update',
                    id: Number(STATE.editingLancamentoId)
                }
            }));
        } catch (err) {
            const errorMsg = getErrorMessage(err, 'Falha ao atualizar lancamento.');
            ModalManager.showLancAlert(errorMsg);
            Notifications.toast(errorMsg, 'error');
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    }
};

// ============================================================================
// REGISTRO NO MÃ“DULO GLOBAL
// ============================================================================

Modules.OptionsManager = OptionsManager;
Modules.ModalManager = ModalManager;

// Expor sugerirCategoriaIA para onclick inline do modal de ediÃ§Ã£o
window._editLancSugerirCategoriaIA = ModalManager.sugerirCategoriaIA;

export { OptionsManager, ModalManager };
