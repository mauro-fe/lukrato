export function resolveFaturasEndpoint() {
    return 'api/v1/faturas';
}

export function resolveFaturaEndpoint(faturaId) {
    return `${resolveFaturasEndpoint()}/${encodeURIComponent(String(faturaId))}`;
}

export function resolveFaturaItemEndpoint(faturaId, itemId) {
    return `${resolveFaturaEndpoint(faturaId)}/itens/${encodeURIComponent(String(itemId))}`;
}

export function resolveFaturaItemToggleEndpoint(faturaId, itemId) {
    return `${resolveFaturaItemEndpoint(faturaId, itemId)}/toggle`;
}

export function resolveFaturaItemParcelamentoEndpoint(faturaId, itemId) {
    return `${resolveFaturaItemEndpoint(faturaId, itemId)}/parcelamento`;
}

export function resolveCardFaturaPayEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/fatura/pagar`;
}

export function resolveCardFaturaEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/fatura`;
}

export function resolveCardFaturaStatusEndpoint(cartaoId) {
    return `${resolveCardFaturaEndpoint(cartaoId)}/status`;
}

export function resolveCardFaturaUndoPaymentEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/fatura/desfazer-pagamento`;
}

export function resolveCardPendingFaturasEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/faturas-pendentes`;
}

export function resolveCardHistoricoFaturasEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/faturas-historico`;
}

export function resolveCardParcelamentosResumoEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/parcelamentos-resumo`;
}

export function resolveCardParcelasPayEndpoint(cartaoId) {
    return `api/v1/cartoes/${encodeURIComponent(String(cartaoId))}/parcelas/pagar`;
}

export function resolveCardParcelaUndoPaymentEndpoint(parcelaId) {
    return `api/v1/cartoes/parcelas/${encodeURIComponent(String(parcelaId))}/desfazer-pagamento`;
}