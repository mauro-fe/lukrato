/**
 * LUKRATO — Faturas / API
 */
import { CONFIG, Utils, Modules } from './state.js';
import {
    resolveCardFaturaPayEndpoint,
    resolveCardFaturaUndoPaymentEndpoint,
    resolveFaturaEndpoint,
    resolveFaturaItemEndpoint,
    resolveFaturaItemParcelamentoEndpoint,
    resolveFaturaItemToggleEndpoint,
} from '../api/endpoints/faturas.js';
import { resolveCategorySubcategoriesEndpoint } from '../api/endpoints/finance.js';

export const FaturasAPI = {
    async listarParcelamentos(filters = {}) {
        const params = {
            status: filters.status,
            cartao_id: filters.cartao_id,
            ano: filters.ano,
            mes: filters.mes
        };

        const url = Utils.buildUrl(CONFIG.ENDPOINTS.parcelamentos, params);
        return await Utils.apiRequest(url);
    },

    async listarCartoes() {
        return await Utils.apiRequest(CONFIG.ENDPOINTS.cartoes);
    },

    async buscarParcelamento(id) {
        const parcelamentoId = parseInt(id, 10);
        if (isNaN(parcelamentoId)) {
            throw new Error('ID inválido');
        }
        return await Utils.apiRequest(resolveFaturaEndpoint(parcelamentoId));
    },

    async criarParcelamento(dados) {
        return await Utils.apiRequest(CONFIG.ENDPOINTS.parcelamentos, {
            method: 'POST',
            body: JSON.stringify(dados)
        });
    },

    async cancelarParcelamento(id) {
        return await Utils.apiRequest(resolveFaturaEndpoint(id), {
            method: 'DELETE'
        });
    },

    async toggleItemFatura(faturaId, itemId, pago) {
        return await Utils.apiRequest(resolveFaturaItemToggleEndpoint(faturaId, itemId), {
            method: 'POST',
            body: JSON.stringify({ pago })
        });
    },

    async atualizarItemFatura(faturaId, itemId, dados) {
        return await Utils.apiRequest(resolveFaturaItemEndpoint(faturaId, itemId), {
            method: 'PUT',
            body: JSON.stringify(dados)
        });
    },

    async excluirItemFatura(faturaId, itemId) {
        return await Utils.apiRequest(resolveFaturaItemEndpoint(faturaId, itemId), {
            method: 'DELETE'
        });
    },

    async excluirParcelamentoDoItem(faturaId, itemId) {
        return await Utils.apiRequest(resolveFaturaItemParcelamentoEndpoint(faturaId, itemId), {
            method: 'DELETE'
        });
    },

    /**
     * Pagar fatura completa - cria UM ÚNICO lançamento agrupado
     * @param {number} cartaoId - ID do cartão de crédito
     * @param {number} mes - Mês da fatura (1-12)
     * @param {number} ano - Ano da fatura
     * @param {number|null} contaId - ID da conta para débito (null = usa conta vinculada ao cartão)
     */
    async pagarFaturaCompleta(cartaoId, mes, ano, contaId = null) {
        const payload = { mes, ano };
        if (contaId) payload.conta_id = contaId;

        return await Utils.apiRequest(resolveCardFaturaPayEndpoint(cartaoId), {
            method: 'POST',
            body: JSON.stringify(payload)
        });
    },

    async pagarFaturaParcial(cartaoId, mes, ano, contaId, valorParcial) {
        return await Utils.apiRequest(resolveCardFaturaPayEndpoint(cartaoId), {
            method: 'POST',
            body: JSON.stringify({
                mes,
                ano,
                conta_id: contaId,
                valor_parcial: valorParcial,
            })
        });
    },

    async desfazerPagamentoFatura(cartaoId, mes, ano) {
        return await Utils.apiRequest(resolveCardFaturaUndoPaymentEndpoint(cartaoId), {
            method: 'POST',
            body: JSON.stringify({ mes, ano })
        });
    },

    /**
     * Listar contas do usuário com saldos
     */
    async listarContas() {
        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.contas}?with_balances=1`);
    },

    async listarCategorias() {
        return await Utils.apiRequest(CONFIG.ENDPOINTS.categorias);
    },

    async listarSubcategorias(categoriaId) {
        return await Utils.apiRequest(resolveCategorySubcategoriesEndpoint(categoriaId));
    }
};

Modules.API = FaturasAPI;
