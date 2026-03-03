/**
 * Cartões Manager – Entry point
 * Orchestrates all modules and exposes backward-compatible global API
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { CartoesAPI } from './api.js';
import { CartoesUI } from './ui.js';
import { FaturaModal } from './fatura.js';

const init = async () => {
    CartoesUI.setupEventListeners();
    await Modules.API.loadCartoes();
    await Modules.API.carregarAlertas();
};

// Backward compat for onclick="cartoesManager.xxx()"
window.cartoesManager = {
    openModal: (id) => CartoesUI.openModal(id),
    closeModal: () => CartoesUI.closeModal(),
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

document.addEventListener('DOMContentLoaded', () => init());
