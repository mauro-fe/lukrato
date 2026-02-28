/**
 * LUKRATO — Faturas / API
 */
import { CONFIG, STATE, Utils, Modules } from './state.js';

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
        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${parcelamentoId}`);
    },

    async criarParcelamento(dados) {
        return await Utils.apiRequest(CONFIG.ENDPOINTS.parcelamentos, {
            method: 'POST',
            body: JSON.stringify(dados)
        });
    },

    async cancelarParcelamento(id) {
        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${id}`, {
            method: 'DELETE'
        });
    },

    async toggleItemFatura(faturaId, itemId, pago) {
        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${faturaId}/itens/${itemId}/toggle`, {
            method: 'POST',
            body: JSON.stringify({ pago })
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

        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.cartoes}/${cartaoId}/fatura/pagar`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });
    },

    /**
     * Listar contas do usuário com saldos
     */
    async listarContas() {
        return await Utils.apiRequest(`${CONFIG.ENDPOINTS.contas}?with_balances=1`);
    }
};

Modules.API = FaturasAPI;
