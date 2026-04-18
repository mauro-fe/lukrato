/**
 * ============================================================================
 * LUKRATO - Lancamento Global (Header FAB Modal)
 * ============================================================================
 * Entry point Vite - resources/js/admin/lancamento-global/index.js
 *
 * Refactored from public/assets/js/lancamento-global.js
 * Uses shared modules instead of duplicated utility functions.
 * ============================================================================
 */

import '../../../css/admin/modal-lancamento/index.css';
import { formatMoney, parseMoney, escapeHtml } from '../shared/utils.js';
import { formatMoneyInput } from '../shared/utils.js';
import { apiGet, apiPost, getBaseUrl, getErrorMessage, logClientError, logClientWarning } from '../shared/api.js';
import { applyMoneyMask } from '../shared/money-mask.js';
import { refreshIcons, showToast } from '../shared/ui.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';
import { loadLancamentoRecentHistory, renderLancamentoHistoryPlaceholder } from '../shared/lancamento-history.js';
import { computeAccountEffect, getPlanningAlertsStore } from '../shared/planning-alerts.js';
import { CustomSelectManager, syncCustomSelects } from '../shared/custom-select.js';
import { sortByLabel } from './helpers.js';
import { attachLancamentoGlobalCoreMethods } from './manager-core.js';
import { attachLancamentoGlobalOptionsMethods } from './manager-options.js';
import { attachLancamentoGlobalEventsMethods } from './manager-events.js';
import { attachLancamentoGlobalFormFlowMethods } from './manager-form-flow.js';
import { attachLancamentoGlobalPaymentMethods } from './manager-payment.js';
import { attachLancamentoGlobalPayloadMethods } from './manager-payload.js';
import { attachLancamentoGlobalPlanningMethods } from './manager-planning.js';
import { attachLancamentoGlobalSaveMethods } from './manager-save.js';
import { attachLancamentoGlobalWizardMethods } from './manager-wizard.js';
import { bootLancamentoGlobalManager, registerLancamentoGlobalBridge } from './legacy-bridge.js';

class LancamentoGlobalManager {
    constructor() {
        this.contaSelecionada = null;
        this.contas = [];
        this.categorias = [];
        this.cartoes = [];
        this.metas = [];
        this.tipoAtual = null;
        this.eventosConfigurados = false;
        this.salvando = false;
        this.isEstornoCartao = false;
        this._dataLoaded = false;
        this.pendingTipo = null;
        this.planningStore = getPlanningAlertsStore();
        this.planningRenderSeq = 0;
        this.contextoAbertura = {
            source: 'global',
            presetAccountId: null,
            lockAccount: false,
            tipo: null
        };

        this.currentStep = 1;
        this.totalSteps = 5;
    }
}

attachLancamentoGlobalCoreMethods(LancamentoGlobalManager, {
    CustomSelectManager,
    syncCustomSelects,
    loadLancamentoRecentHistory,
    renderLancamentoHistoryPlaceholder,
    getBaseUrl,
    apiGet,
    sortByLabel,
    formatMoney,
});

attachLancamentoGlobalOptionsMethods(LancamentoGlobalManager, {
    escapeHtml,
    getBaseUrl,
    apiGet,
    sortByLabel,
    _sugerirCategoriaIA,
});

attachLancamentoGlobalEventsMethods(LancamentoGlobalManager, {
    applyMoneyMask,
    parseMoney,
    formatMoney,
    refreshIcons,
});

attachLancamentoGlobalFormFlowMethods(LancamentoGlobalManager, {
    parseMoney,
    formatMoney,
    getBaseUrl,
    refreshIcons,
});

attachLancamentoGlobalPaymentMethods(LancamentoGlobalManager, {
    formatMoney,
    parseMoney,
});

attachLancamentoGlobalPayloadMethods(LancamentoGlobalManager, {
    parseMoney,
});

attachLancamentoGlobalPlanningMethods(LancamentoGlobalManager, {
    refreshIcons,
    parseMoney,
    formatMoney,
    formatMoneyInput,
    computeAccountEffect,
    getBaseUrl,
});

attachLancamentoGlobalWizardMethods(LancamentoGlobalManager, {
    refreshIcons,
    renderLancamentoHistoryPlaceholder,
});

attachLancamentoGlobalSaveMethods(LancamentoGlobalManager, {
    formatMoney,
    parseMoney,
    refreshIcons,
    showToast,
    getBaseUrl,
    logClientWarning,
    apiPost,
    getErrorMessage,
    logClientError,
});

const manager = new LancamentoGlobalManager();
registerLancamentoGlobalBridge(manager);
bootLancamentoGlobalManager(manager);

export default manager;
