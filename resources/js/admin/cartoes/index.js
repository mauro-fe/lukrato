/**
 * Cartoes Manager - Entry point
 * Orchestrates all modules and exposes backward-compatible global API
 */

import { Modules } from './state.js';
import { CartoesAPI } from './api.js';
import { CartoesUI } from './ui.js';
import { FaturaModal } from './fatura.js';

const init = async () => {
    CartoesUI.setupEventListeners();
    CartoesUI.restoreViewPreference();
    await Modules.API.loadCartoes();
};

// Backward compat for onclick="cartoesManager.xxx()"
window.cartoesManager = {
    openModal: (mode = 'create', cartaoData = null) => CartoesUI.openModal(mode, cartaoData),
    closeModal: () => CartoesUI.closeModal(),
    moreCartao: (id, event) => CartoesUI.showCardMenu(id, event),
    editCartao: (id) => Modules.API.editCartao(id),
    arquivarCartao: (id) => Modules.API.arquivarCartao(id),
    deleteCartao: (id) => Modules.API.deleteCartao(id),
    exportarRelatorio: () => CartoesUI.exportarRelatorio(),
    mostrarModalFatura: (cid, m, a) => FaturaModal.mostrarModalFatura(cid, m, a),
    verFatura: (cid) => FaturaModal.verFatura(cid),
    fecharModalFatura: () => FaturaModal.fecharModalFatura(),
    navegarMes: (cid, m, a, d) => FaturaModal.navegarMes(cid, m, a, d),
    pagarFatura: (cid, m, a) => FaturaModal.pagarFatura(cid, m, a),
    pagarParcelasSelecionadas: (cid, m) => FaturaModal.pagarParcelasSelecionadas(cid, m),
    toggleHistoricoFatura: (cid) => FaturaModal.toggleHistoricoFatura(cid),
    dismissAlerta: (el) => Modules.API.dismissAlerta(el),
    loadCartoes: () => Modules.API.loadCartoes(),
    desfazerPagamento: (cid, m, a) => Modules.API.desfazerPagamento(cid, m, a),
    desfazerPagamentoParcela: (pid) => Modules.API.desfazerPagamentoParcela(pid),
};

if (!window.__CARTOES_MANAGER_INITIALIZED__) {
    window.__CARTOES_MANAGER_INITIALIZED__ = true;
    document.addEventListener('DOMContentLoaded', () => init());
}
