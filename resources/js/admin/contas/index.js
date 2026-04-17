/**
 * ============================================================================
 * LUKRATO - Contas / Index (Entry Point)
 * ============================================================================
 * Imports every contas module, bootstraps the page and exposes a small
 * backward-compatible window.contasManager proxy for PHP inline handlers.
 * ============================================================================
 */

import '../../../css/admin/contas/index.css';
import '../../../css/admin/modules/modal-contas.css';
import { buildAppUrl } from '../shared/api.js';
import { ContasAPI } from './api.js';
import { ContasModal } from './modal.js';
import { ContasEvents, ContasMoneyMask } from './events.js';
import { initCustomize } from './customize.js';
import './render.js';

const getLancamentoGlobalManager = () => window.lancamentoGlobalManager || null;
const openLancamentoGlobalFromConta = (contaId, options = {}) => {
    const manager = getLancamentoGlobalManager();
    if (!manager?.openModal) {
        window.location.href = buildAppUrl('lancamentos');
        return;
    }

    manager.openModal({
        source: 'contas',
        presetAccountId: contaId,
        ...options
    });
};

const init = async () => {
    initCustomize();
    ContasMoneyMask.setupMoneyMask();
    ContasEvents.attachEventListeners();
    ContasEvents.initKeyboardShortcuts();

    await ContasAPI.loadInstituicoes();
    await ContasAPI.loadContas();
};

window.ContasAPI = ContasAPI;

window.contasManager = {
    openModal: (...args) => ContasModal.openModal(...args),
    closeModal: () => ContasModal.closeModal(),
    moreConta: (id, event) => ContasModal.moreConta(id, event),
    updateColorPreview: (color) => ContasModal.updateColorPreview(color),
    openNovaInstituicaoModal: () => ContasModal.openNovaInstituicaoModal(),
    closeNovaInstituicaoModal: () => ContasModal.closeNovaInstituicaoModal(),
    editConta: (id) => ContasAPI.editConta(id),
    deleteConta: (id) => ContasAPI.deleteConta(id),
    archiveConta: (id) => ContasAPI.archiveConta(id),
    loadContas: () => ContasAPI.loadContas(),
    openLancamentoModal: (contaId, options = {}) => openLancamentoGlobalFromConta(contaId, options),
    closeLancamentoModal: () => getLancamentoGlobalManager()?.closeModal?.(),
    mostrarFormularioLancamento: (tipo) => getLancamentoGlobalManager()?.mostrarFormulario?.(tipo),
    voltarEscolhaTipo: () => getLancamentoGlobalManager()?.voltarEscolhaTipo?.(),
    handleLancamentoSubmit: () => getLancamentoGlobalManager()?.salvarLancamento?.(),
    selecionarFormaPagamento: (forma) => getLancamentoGlobalManager()?.selecionarFormaPagamento?.(forma),
    selecionarFormaRecebimento: (forma) => getLancamentoGlobalManager()?.selecionarFormaRecebimento?.(forma),
    onCartaoChange: () => undefined,
    aoMarcarParcelado: () => undefined,
    toggleAssinaturaCartao: () => getLancamentoGlobalManager()?.toggleAssinaturaCartao?.(),
    toggleAssinaturaCartaoFim: () => getLancamentoGlobalManager()?.toggleAssinaturaCartaoFim?.(),
    toggleRecorrencia: () => getLancamentoGlobalManager()?.toggleRecorrencia?.(),
    toggleRecorrenciaFim: () => getLancamentoGlobalManager()?.toggleRecorrenciaFim?.(),
    selecionarTipoLancamento: (tipo) => getLancamentoGlobalManager()?.mostrarFormulario?.(tipo),
    selecionarTipoAgendamento: (tipo) => getLancamentoGlobalManager()?.selecionarTipoAgendamento?.(tipo),
    nextStep: () => getLancamentoGlobalManager()?.nextStep?.(),
    prevStep: () => getLancamentoGlobalManager()?.prevStep?.(),
    skipAndSave: () => getLancamentoGlobalManager()?.saveQuick?.(),
    sugerirCategoriaIA: () => getLancamentoGlobalManager()?.sugerirCategoriaIA?.(),
};

if (!window.__CONTAS_MANAGER_INITIALIZED__) {
    window.__CONTAS_MANAGER_INITIALIZED__ = true;
    document.addEventListener('DOMContentLoaded', () => init());
}
