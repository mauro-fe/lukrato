import {
    resolveContaLancamentosEndpoint,
    resolveLancamentoCancelRecurringEndpoint,
    resolveLancamentoEndpoint,
    resolveLancamentoFaturaDetailsEndpoint,
    resolveLancamentoPayEndpoint,
    resolveLancamentosBulkDeleteEndpoint,
    resolveLancamentosEndpoint,
    resolveLancamentosExportEndpoint,
    resolveLancamentosUsageEndpoint,
    resolveLancamentoUnpayEndpoint,
    resolveParcelamentoEndpoint,
    resolveParcelamentosEndpoint,
    resolveTransactionEndpoint,
    resolveTransactionLegacyUpdateEndpoint,
    resolveTransactionsEndpoint,
    resolveTransfersEndpoint,
} from './lancamentos.js';

describe('admin/api/endpoints/lancamentos', () => {
    it('resolve os endpoints v1 de lancamentos e acoes relacionadas', () => {
        expect(resolveLancamentosEndpoint()).toBe('api/v1/lancamentos');
        expect(resolveLancamentoEndpoint(12)).toBe('api/v1/lancamentos/12');
        expect(resolveLancamentosBulkDeleteEndpoint()).toBe('api/v1/lancamentos/delete');
        expect(resolveLancamentosUsageEndpoint()).toBe('api/v1/lancamentos/usage');
        expect(resolveLancamentosExportEndpoint()).toBe('api/v1/lancamentos/export');
        expect(resolveLancamentoCancelRecurringEndpoint(12)).toBe('api/v1/lancamentos/12/cancelar-recorrencia');
        expect(resolveLancamentoPayEndpoint(12)).toBe('api/v1/lancamentos/12/pagar');
        expect(resolveLancamentoUnpayEndpoint(12)).toBe('api/v1/lancamentos/12/despagar');
        expect(resolveLancamentoFaturaDetailsEndpoint(12)).toBe('api/v1/lancamentos/12/fatura-detalhes');
        expect(resolveContaLancamentosEndpoint(9)).toBe('api/v1/contas/9/lancamentos');
    });

    it('resolve os endpoints v1 de parcelamentos, transacoes e transferencias', () => {
        expect(resolveParcelamentosEndpoint()).toBe('api/v1/parcelamentos');
        expect(resolveParcelamentoEndpoint(7)).toBe('api/v1/parcelamentos/7');
        expect(resolveTransactionsEndpoint()).toBe('api/v1/transactions');
        expect(resolveTransactionEndpoint(44)).toBe('api/v1/transactions/44');
        expect(resolveTransactionLegacyUpdateEndpoint(44)).toBe('api/v1/transactions/44/update');
        expect(resolveTransfersEndpoint()).toBe('api/v1/transfers');
    });
});