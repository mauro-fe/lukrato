/**
 * Cartoes Manager - Entry point
 * Orchestrates all modules and exposes backward-compatible global API
 */

import '../../../css/admin/cartoes/index.css';
import '../../../css/admin/modules/modal-cartoes.css';
import { STATE, Utils } from './state.js';
import { CartoesAPI } from './api.js';
import { CartoesUI } from './ui.js';
import { FaturaModal } from './fatura.js';
import { initCustomize } from './customize.js';

const init = async () => {
    initCustomize();
    CartoesUI.setupEventListeners();
    CartoesUI.restoreViewPreference();
    await CartoesAPI.loadCartoes();
};

const guardDemoCard = (id) => {
    const cartao = STATE.cartoes.find((item) => item.id === id);
    if (cartao?.is_demo) {
        Utils.showToast('info', 'Esse cartao e apenas um exemplo. Crie um cartao real para abrir a fatura.');
        return true;
    }

    return false;
};

// Backward compat for onclick="cartoesManager.xxx()"
window.cartoesManager = {
    openModal: (mode = 'create', cartaoData = null) => CartoesUI.openModal(mode, cartaoData),
    closeModal: () => CartoesUI.closeModal(),
    moreCartao: (id, event) => CartoesUI.showCardMenu(id, event),
    editCartao: (id) => CartoesAPI.editCartao(id),
    arquivarCartao: (id) => CartoesAPI.arquivarCartao(id),
    deleteCartao: (id) => CartoesAPI.deleteCartao(id),
    exportarRelatorio: () => CartoesUI.exportarRelatorio(),
    mostrarModalFatura: (cid, m, a) => FaturaModal.mostrarModalFatura(cid, m, a),
    verFatura: (cid) => {
        if (guardDemoCard(cid)) return;
        FaturaModal.verFatura(cid);
    },
    fecharModalFatura: () => FaturaModal.fecharModalFatura(),
    navegarMes: (cid, m, a, d) => FaturaModal.navegarMes(cid, m, a, d),
    pagarFatura: (cid, m, a) => FaturaModal.pagarFatura(cid, m, a),
    pagarParcelasSelecionadas: (cid, m) => FaturaModal.pagarParcelasSelecionadas(cid, m),
    toggleHistoricoFatura: (cid) => FaturaModal.toggleHistoricoFatura(cid),
    dismissAlerta: (el) => CartoesAPI.dismissAlerta(el),
    loadCartoes: () => CartoesAPI.loadCartoes(),
    desfazerPagamento: (cid, m, a) => CartoesAPI.desfazerPagamento(cid, m, a),
    desfazerPagamentoParcela: (pid) => CartoesAPI.desfazerPagamentoParcela(pid),
};

if (!window.__CARTOES_MANAGER_INITIALIZED__) {
    window.__CARTOES_MANAGER_INITIALIZED__ = true;
    document.addEventListener('DOMContentLoaded', () => init());
}
