/**
 * Financas Manager – Entry point
 * Orchestrates all modules and exposes backward-compatible global API
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { FinancasApp } from './app.js';

// ── Bootstrap ──────────────────────────────────────────────────

const init = async () => {
    await FinancasApp.init();
};

// ── Backward compat for onclick="financasManager.xxx()" ────────

window.financasManager = {
    // Orçamentos
    openOrcamentoModal: (id) => FinancasApp.openOrcamentoModal(id),
    deleteOrcamento: (id) => FinancasApp.deleteOrcamento(id),

    // Metas
    openMetaModal: (id) => FinancasApp.openMetaModal(id),
    deleteMeta: (id) => FinancasApp.deleteMeta(id),
    openAporteModal: (id) => FinancasApp.openAporteModal(id),

    // Templates
    useTemplate: (tmpl) => FinancasApp.useTemplate(tmpl),

    // Utils exposed for inline oninput handlers
    formatarDinheiro: (input) => Utils.formatarDinheiro(input),

    // Misc
    loadAll: () => FinancasApp.loadAll(),
};

// ── DOMContentLoaded ───────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
} else {
    init();
}
