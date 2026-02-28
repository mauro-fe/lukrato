/**
 * LUKRATO — Faturas / Entry Point
 *
 * Imports all modules, wires up global window functions for PHP views,
 * and bootstraps the application on DOMContentLoaded.
 */
import { CONFIG, DOM, STATE, Utils, Modules, initDOM } from './state.js';
import { FaturasAPI } from './api.js';
import { FaturasUI } from './ui.js';
import { FaturasApp } from './app.js';
import { ModalPagarFatura, reverterPagamentoFaturaGlobal, excluirFaturaGlobal, excluirItemFaturaGlobal } from './payment.js';

// ============================================================================
// Expose global functions for PHP view onclick handlers
// ============================================================================

window.abrirModalPagarFatura = (faturaId, valorTotal) => ModalPagarFatura.abrir(faturaId, valorTotal);
window.reverterPagamentoFaturaGlobal = reverterPagamentoFaturaGlobal;
window.excluirFaturaGlobal = excluirFaturaGlobal;
window.excluirItemFaturaGlobal = excluirItemFaturaGlobal;
window.pagarFaturaCompletaGlobal = (faturaId, valorTotal) => FaturasUI.pagarFaturaCompleta(faturaId, valorTotal);

// ============================================================================
// FaturasModule for HTML callbacks
// ============================================================================

window.FaturasModule = {
    toggleCardDetalhes: (faturaId) => FaturasUI.showDetalhes(faturaId),
    excluirItemFatura: (...args) => FaturasUI.excluirItemFatura(...args),
    editarItemFatura: (...args) => FaturasUI.editarItemFatura(...args),
    toggleParcelaPaga: (...args) => FaturasUI.toggleParcelaPaga(...args),
};

// ============================================================================
// Guard + bootstrap
// ============================================================================

if (!window.__LK_PARCELAMENTOS_LOADER__) {
    window.__LK_PARCELAMENTOS_LOADER__ = true;
    document.addEventListener('DOMContentLoaded', () => {
        initDOM();
        FaturasApp.init();
        ModalPagarFatura.init();
    });
}
