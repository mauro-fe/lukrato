function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveLancamentosEndpoint() {
    return 'api/v1/lancamentos';
}

export function resolveLancamentoEndpoint(lancamentoId) {
    return `${resolveLancamentosEndpoint()}/${encodeEndpointSegment(lancamentoId)}`;
}

export function resolveLancamentosBulkDeleteEndpoint() {
    return `${resolveLancamentosEndpoint()}/delete`;
}

export function resolveLancamentosUsageEndpoint() {
    return `${resolveLancamentosEndpoint()}/usage`;
}

export function resolveLancamentosExportEndpoint() {
    return `${resolveLancamentosEndpoint()}/export`;
}

export function resolveLancamentoCancelRecurringEndpoint(lancamentoId) {
    return `${resolveLancamentoEndpoint(lancamentoId)}/cancelar-recorrencia`;
}

export function resolveLancamentoPayEndpoint(lancamentoId) {
    return `${resolveLancamentoEndpoint(lancamentoId)}/pagar`;
}

export function resolveLancamentoUnpayEndpoint(lancamentoId) {
    return `${resolveLancamentoEndpoint(lancamentoId)}/despagar`;
}

export function resolveLancamentoFaturaDetailsEndpoint(lancamentoId) {
    return `${resolveLancamentoEndpoint(lancamentoId)}/fatura-detalhes`;
}

export function resolveContaLancamentosEndpoint(contaId) {
    return `api/v1/contas/${encodeEndpointSegment(contaId)}/lancamentos`;
}

export function resolveParcelamentosEndpoint() {
    return 'api/v1/parcelamentos';
}

export function resolveParcelamentoEndpoint(parcelamentoId) {
    return `${resolveParcelamentosEndpoint()}/${encodeEndpointSegment(parcelamentoId)}`;
}

export function resolveTransactionsEndpoint() {
    return 'api/v1/transactions';
}

export function resolveTransactionEndpoint(transactionId) {
    return `${resolveTransactionsEndpoint()}/${encodeEndpointSegment(transactionId)}`;
}

export function resolveTransactionLegacyUpdateEndpoint(transactionId) {
    return `${resolveTransactionEndpoint(transactionId)}/update`;
}

export function resolveTransfersEndpoint() {
    return 'api/v1/transfers';
}