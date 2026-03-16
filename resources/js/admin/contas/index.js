/**
 * ============================================================================
 * LUKRATO — Contas / Index (Entry Point)
 * ============================================================================
 * Imports every contas module, bootstraps the page, and exposes a
 * backward-compatible window.contasManager proxy for inline onclick
 * handlers in PHP views.
 * ============================================================================
 */

import { CONFIG } from './state.js';
import { ContasAPI } from './api.js';
import { ContasRender } from './render.js';
import { ContasModal } from './modal.js';
import { ContasEvents, ContasMoneyMask } from './events.js';

const getLancamentoGlobalManager = () => window.lancamentoGlobalManager || null;
const openLancamentoGlobalFromConta = (contaId, options = {}) => {
    const manager = getLancamentoGlobalManager();
    if (!manager?.openModal) {
        window.location.href = `${CONFIG.BASE_URL}lancamentos`;
        return;
    }

    manager.openModal({
        source: 'contas',
        presetAccountId: contaId,
        ...options
    });
};

// All modules auto-register on Modules via their own files.
// The init sequence:
const init = async () => {
    // Setup money masks
    ContasMoneyMask.setupMoneyMask();
    ContasMoneyMask.setupCartaoMoneyMask();

    // Attach event listeners
    ContasEvents.attachEventListeners();
    ContasEvents.initKeyboardShortcuts();

    // Load initial data
    await ContasAPI.loadInstituicoes();
    await ContasAPI.loadContas();
};

window.ContasAPI = ContasAPI;

// Backward compat: expose as window.contasManager with method proxies
// Every method called via onclick="contasManager.xxx()" in PHP views must be listed here.
window.contasManager = {
    // ── Modal (contas) ───────────────────────────────────────────────────
    openModal: (...a) => ContasModal.openModal(...a),
    closeModal: () => ContasModal.closeModal(),
    moreConta: (id, e) => ContasModal.moreConta(id, e),
    updateColorPreview: () => ContasModal.updateColorPreview(),

    // ── Modal (cartão) ──────────────────────────────────────────────────
    openCartaoModal: (id) => ContasModal.openCartaoModal(id),
    closeCartaoModal: () => ContasModal.closeCartaoModal(),

    // ── Modal (nova instituição) ─────────────────────────────────────────
    openNovaInstituicaoModal: () => ContasModal.openNovaInstituicaoModal(),
    closeNovaInstituicaoModal: () => ContasModal.closeNovaInstituicaoModal(),

    // ── API / CRUD ──────────────────────────────────────────────────────
    editConta: (id) => ContasAPI.editConta(id),
    deleteConta: (id) => ContasAPI.deleteConta(id),
    archiveConta: (id) => ContasAPI.archiveConta(id),
    loadContas: () => ContasAPI.loadContas(),

    // ── Lançamento modal ─────────────────────────────────────────────────
    openLancamentoModal: (contaId, options = {}) => openLancamentoGlobalFromConta(contaId, options),
    closeLancamentoModal: () => getLancamentoGlobalManager()?.closeModal?.(),
    mostrarFormularioLancamento: (tipo) => getLancamentoGlobalManager()?.mostrarFormulario?.(tipo),
    voltarEscolhaTipo: () => getLancamentoGlobalManager()?.voltarEscolhaTipo?.(),
    handleLancamentoSubmit: () => getLancamentoGlobalManager()?.salvarLancamento?.(),

    // ── Lançamento — forma de pagamento / recebimento ────────────────────
    selecionarFormaPagamento: (forma) => getLancamentoGlobalManager()?.selecionarFormaPagamento?.(forma),
    selecionarFormaRecebimento: (forma) => getLancamentoGlobalManager()?.selecionarFormaRecebimento?.(forma),

    // ── Lançamento — cartão de crédito ───────────────────────────────────
    onCartaoChange: () => undefined,
    aoMarcarParcelado: () => undefined,
    toggleAssinaturaCartao: () => getLancamentoGlobalManager()?.toggleAssinaturaCartao?.(),
    toggleAssinaturaCartaoFim: () => getLancamentoGlobalManager()?.toggleAssinaturaCartaoFim?.(),

    // ── Lançamento — recorrência / agendamento ──────────────────────────
    toggleRecorrencia: () => getLancamentoGlobalManager()?.toggleRecorrencia?.(),
    toggleRecorrenciaFim: () => getLancamentoGlobalManager()?.toggleRecorrenciaFim?.(),
    selecionarTipoLancamento: (tipo) => getLancamentoGlobalManager()?.mostrarFormulario?.(tipo),
    selecionarTipoAgendamento: (tipo) => getLancamentoGlobalManager()?.selecionarTipoAgendamento?.(tipo),

    // ── Lançamento — wizard steps ───────────────────────────────────────
    nextStep: () => getLancamentoGlobalManager()?.nextStep?.(),
    prevStep: () => getLancamentoGlobalManager()?.prevStep?.(),
    skipAndSave: () => getLancamentoGlobalManager()?.saveQuick?.(),

    // ── IA ───────────────────────────────────────────────────────────────
    sugerirCategoriaIA: () => getLancamentoGlobalManager()?.sugerirCategoriaIA?.(),
};

// Guard + bootstrap
if (!window.__CONTAS_MANAGER_INITIALIZED__) {
    window.__CONTAS_MANAGER_INITIALIZED__ = true;
    document.addEventListener('DOMContentLoaded', () => init());
}
