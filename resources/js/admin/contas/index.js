/**
 * ============================================================================
 * LUKRATO — Contas / Index (Entry Point)
 * ============================================================================
 * Imports every contas module, bootstraps the page, and exposes a
 * backward-compatible window.contasManager proxy for inline onclick
 * handlers in PHP views.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { ContasAPI } from './api.js';
import { ContasRender } from './render.js';
import { ContasModal } from './modal.js';
import { ContasLancamento } from './lancamento.js';
import { ContasEvents, ContasMoneyMask } from './events.js';

// All modules auto-register on Modules via their own files.
// The init sequence:
const init = async () => {
    // Setup money masks
    ContasMoneyMask.setupMoneyMask();
    ContasMoneyMask.setupCartaoMoneyMask();
    ContasMoneyMask.setupLancamentoMoneyMask();

    // Attach event listeners
    ContasEvents.attachEventListeners();
    ContasEvents.initKeyboardShortcuts();
    ContasEvents.initViewToggle();

    // Load initial data
    await ContasAPI.loadInstituicoes();
    await ContasAPI.loadContas();
};

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
    openLancamentoModal: (c) => ContasLancamento.openLancamentoModal(c),
    closeLancamentoModal: () => ContasLancamento.closeLancamentoModal(),
    mostrarFormularioLancamento: (t) => ContasLancamento.mostrarFormularioLancamento(t),
    voltarEscolhaTipo: () => ContasLancamento.voltarEscolhaTipo(),
    handleLancamentoSubmit: (e) => ContasLancamento.handleLancamentoSubmit(e),

    // ── Lançamento — forma de pagamento / recebimento ────────────────────
    selecionarFormaPagamento: (f) => ContasLancamento.selecionarFormaPagamento(f),
    selecionarFormaRecebimento: (f) => ContasLancamento.selecionarFormaRecebimento(f),

    // ── Lançamento — cartão de crédito ───────────────────────────────────
    onCartaoChange: () => ContasLancamento.onCartaoChange(),
    aoMarcarParcelado: (p) => ContasLancamento.aoMarcarParcelado(p),
    toggleAssinaturaCartao: (s) => ContasLancamento.toggleAssinaturaCartao(s),
    toggleAssinaturaCartaoFim: (s) => ContasLancamento.toggleAssinaturaCartaoFim(s),

    // ── Lançamento — recorrência / agendamento ──────────────────────────
    toggleRecorrencia: (s) => ContasLancamento.toggleRecorrencia(s),
    toggleRecorrenciaFim: (s) => ContasLancamento.toggleRecorrenciaFim(s),
    selecionarTipoLancamento: (t) => ContasLancamento.selecionarTipoLancamento(t),
    selecionarTipoAgendamento: (t) => ContasLancamento.selecionarTipoAgendamento(t),

    // ── Lançamento — wizard steps ───────────────────────────────────────
    nextStep: () => ContasLancamento.nextStep(),
    prevStep: () => ContasLancamento.prevStep(),
    skipAndSave: () => ContasLancamento.skipAndSave(),
};

// Guard + bootstrap
if (!window.__CONTAS_MANAGER_INITIALIZED__) {
    window.__CONTAS_MANAGER_INITIALIZED__ = true;
    document.addEventListener('DOMContentLoaded', () => init());
}
