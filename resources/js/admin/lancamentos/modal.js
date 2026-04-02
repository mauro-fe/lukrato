/**
 * LUKRATO — Lançamentos / ModalManager + OptionsManager
 */

import { CONFIG, DOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';
import { apiGet, getErrorMessage } from '../shared/api.js';
import { syncCustomSelects } from './custom-select.js';
import {
    buildPlanningAlertCard,
    canLinkMetaInLancamento,
    formatMetaOptionLabel,
    parseSelectedMetaId,
    resolveMetaOperationForLancamento,
    summarizeMetaTitles,
} from '../shared/lancamento-meta.js';
import {
    getPlanningAlertsStore,
    resolvePlanningPeriod,
    isSamePlanningPeriod,
} from '../shared/planning-alerts.js';
import { attachLancamentosModalOptions } from './modal-options.js';
import { attachLancamentosModalDeleteScope } from './modal-delete-scope.js';
import { attachLancamentosModalEdit } from './modal-edit.js';
import { attachLancamentosModalTransfer } from './modal-transfer.js';

const planningStore = getPlanningAlertsStore();

function syncModalSelects(root) {
    if (!root) return;
    syncCustomSelects(root);
}

const OptionsManager = {};
const ModalManager = {};

attachLancamentosModalOptions(OptionsManager, {
    CONFIG,
    DOM,
    STATE,
    Utils,
    Modules,
    planningStore,
    apiGet,
    formatMetaOptionLabel,
});

attachLancamentosModalDeleteScope(ModalManager, {
    DOM,
    STATE,
});

attachLancamentosModalEdit(ModalManager, {
    DOM,
    STATE,
    Utils,
    MoneyMask,
    Notifications,
    Modules,
    _sugerirCategoriaIA,
    getErrorMessage,
    syncModalSelects,
    OptionsManager,
    planningStore,
    resolvePlanningPeriod,
    isSamePlanningPeriod,
    buildPlanningAlertCard,
    summarizeMetaTitles,
    canLinkMetaInLancamento,
    parseSelectedMetaId,
    resolveMetaOperationForLancamento,
});

attachLancamentosModalTransfer(ModalManager, {
    DOM,
    STATE,
    Utils,
    MoneyMask,
    Notifications,
    Modules,
    getErrorMessage,
    syncModalSelects,
    OptionsManager,
});

Modules.OptionsManager = OptionsManager;
Modules.ModalManager = ModalManager;

window._editLancSugerirCategoriaIA = ModalManager.sugerirCategoriaIA;

export { OptionsManager, ModalManager };
