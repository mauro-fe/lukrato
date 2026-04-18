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

const initDetailPage = async () => {
    const detailPage = document.getElementById('cardDetailPage');

    if (!detailPage) {
        return false;
    }

    const cardId = Number.parseInt(String(detailPage.dataset.cardId || ''), 10);
    if (!Number.isInteger(cardId) || cardId <= 0) {
        const loading = document.getElementById('cardDetailPageLoading');
        const error = document.getElementById('cardDetailPageError');
        const subtitle = document.getElementById('cardDetailPageSubtitle');

        if (loading) {
            loading.hidden = true;
            loading.style.display = 'none';
        }

        if (error) {
            error.hidden = false;
            error.innerHTML = '<p>Cartão inválido para consulta de detalhes.</p>';
        }

        if (subtitle) {
            subtitle.textContent = 'Verifique o link e tente novamente.';
        }

        return true;
    }

    const currentMonth = detailPage.dataset.currentMonth || `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`;

    if (!window.LK_CardDetail?.renderPage) {
        const loading = document.getElementById('cardDetailPageLoading');
        const error = document.getElementById('cardDetailPageError');
        const subtitle = document.getElementById('cardDetailPageSubtitle');

        if (loading) {
            loading.hidden = true;
            loading.style.display = 'none';
        }

        if (error) {
            error.hidden = false;
            error.innerHTML = '<p>Não foi possível inicializar os detalhes deste cartão.</p>';
        }

        if (subtitle) {
            subtitle.textContent = 'Atualize a página para tentar novamente.';
        }

        return true;
    }

    await window.LK_CardDetail.renderPage({
        cardId,
        currentMonth,
        mountId: 'cardDetailPageContent',
        loadingId: 'cardDetailPageLoading',
        errorId: 'cardDetailPageError',
        titleId: 'cardDetailPageTitle',
        subtitleId: 'cardDetailPageSubtitle',
    });

    return true;
};

const init = async () => {
    if (await initDetailPage()) {
        return;
    }

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
