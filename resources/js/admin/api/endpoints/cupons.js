export function resolveCuponsEndpoint() {
    return 'api/v1/cupons';
}

export function resolveCuponsStatisticsEndpoint() {
    return `${resolveCuponsEndpoint()}/estatisticas`;
}