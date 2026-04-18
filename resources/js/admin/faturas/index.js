/**
 * LUKRATO — Faturas / Entry Point
 *
 * Imports all modules, wires up global window functions for PHP views,
 * and bootstraps the application on DOMContentLoaded.
 */
import '../../../css/admin/faturas/index.css';
import { initDOM } from './state.js';
import './api.js';
import './ui.js';
import { FaturasApp } from './app.js';
import { ModalPagarFatura, reverterPagamentoFaturaGlobal } from './payment.js';
import { initCustomize } from './customize.js';

// ============================================================================
// Expose global functions for PHP view onclick handlers
// ============================================================================

window.abrirModalPagarFatura = (faturaId, valorTotal) => ModalPagarFatura.abrir(faturaId, valorTotal);
window.reverterPagamentoFaturaGlobal = reverterPagamentoFaturaGlobal;

// ============================================================================
// Guard + bootstrap
// ============================================================================

if (!window.__LK_PARCELAMENTOS_LOADER__) {
    window.__LK_PARCELAMENTOS_LOADER__ = true;
    document.addEventListener('DOMContentLoaded', () => {
        initDOM();
        if (!document.getElementById('faturaDetalhePage')) {
            initCustomize();
        }
        FaturasApp.init();
        ModalPagarFatura.init();
    });
}
