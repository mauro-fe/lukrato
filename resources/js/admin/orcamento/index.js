/**
 * Orçamento – Entry point
 */

import '../../../css/admin/orcamento/index.css';
import { Utils } from './state.js';
import { OrcamentoApp } from './app.js';

// ── Bootstrap ──────────────────────────────────────────────────

const init = async () => {
    await OrcamentoApp.init();
};

// ── Backward compat for onclick="orcamentoManager.xxx()" ───────

window.orcamentoManager = {
    openOrcamentoModal: (id) => OrcamentoApp.openOrcamentoModal(id),
    openOrcamentoModalByCategoria: (categoriaId) => OrcamentoApp.openOrcamentoModalByCategoria(categoriaId),
    openSugestoes: () => OrcamentoApp.openSugestoes(),
    deleteOrcamento: (id) => OrcamentoApp.deleteOrcamento(id),
    formatarDinheiro: (input) => Utils.formatarDinheiro(input),
    loadAll: () => OrcamentoApp.loadAll(),
};

// ── DOMContentLoaded ───────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
} else {
    init();
}
