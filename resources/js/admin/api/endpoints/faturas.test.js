import {
    resolveCardFaturaEndpoint,
    resolveCardFaturaPayEndpoint,
    resolveCardFaturaStatusEndpoint,
    resolveCardHistoricoFaturasEndpoint,
    resolveCardParcelamentosResumoEndpoint,
    resolveCardParcelasPayEndpoint,
    resolveCardFaturaUndoPaymentEndpoint,
    resolveCardPendingFaturasEndpoint,
    resolveCardParcelaUndoPaymentEndpoint,
    resolveFaturaEndpoint,
    resolveFaturasEndpoint,
    resolveFaturaItemEndpoint,
    resolveFaturaItemParcelamentoEndpoint,
    resolveFaturaItemToggleEndpoint,
} from './faturas.js';

describe('admin/api/endpoints/faturas', () => {
    it('resolve os endpoints v1 de faturas e itens', () => {
        expect(resolveFaturasEndpoint()).toBe('api/v1/faturas');
        expect(resolveFaturaEndpoint(18)).toBe('api/v1/faturas/18');
        expect(resolveFaturaItemEndpoint(18, 7)).toBe('api/v1/faturas/18/itens/7');
        expect(resolveFaturaItemToggleEndpoint(18, 7)).toBe('api/v1/faturas/18/itens/7/toggle');
        expect(resolveFaturaItemParcelamentoEndpoint(18, 7)).toBe('api/v1/faturas/18/itens/7/parcelamento');
    });

    it('resolve os endpoints v1 de pagamento e reversao de faturas de cartao', () => {
        expect(resolveCardFaturaEndpoint(22)).toBe('api/v1/cartoes/22/fatura');
        expect(resolveCardFaturaPayEndpoint(22)).toBe('api/v1/cartoes/22/fatura/pagar');
        expect(resolveCardFaturaStatusEndpoint(22)).toBe('api/v1/cartoes/22/fatura/status');
        expect(resolveCardFaturaUndoPaymentEndpoint(22)).toBe('api/v1/cartoes/22/fatura/desfazer-pagamento');
        expect(resolveCardPendingFaturasEndpoint(22)).toBe('api/v1/cartoes/22/faturas-pendentes');
        expect(resolveCardHistoricoFaturasEndpoint(22)).toBe('api/v1/cartoes/22/faturas-historico');
        expect(resolveCardParcelamentosResumoEndpoint(22)).toBe('api/v1/cartoes/22/parcelamentos-resumo');
        expect(resolveCardParcelasPayEndpoint(22)).toBe('api/v1/cartoes/22/parcelas/pagar');
        expect(resolveCardParcelaUndoPaymentEndpoint(99)).toBe('api/v1/cartoes/parcelas/99/desfazer-pagamento');
    });
});