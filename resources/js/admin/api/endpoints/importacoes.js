function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

function normalizeImportacaoTarget(target) {
    return String(target || '').trim().toLowerCase() === 'cartao' ? 'cartao' : 'conta';
}

function normalizeCsvTemplateMode(mode) {
    return String(mode || '').trim().toLowerCase() === 'manual' ? 'manual' : 'auto';
}

export function resolveImportacoesConfiguracoesEndpoint() {
    return 'api/v1/importacoes/configuracoes';
}

export function resolveImportacoesConfiguracoesPageInitEndpoint() {
    return 'api/v1/importacoes/configuracoes/page-init';
}

export function resolveImportacoesPageInitEndpoint() {
    return 'api/v1/importacoes/page-init';
}

export function resolveImportacoesCsvTemplateEndpoint({ mode = 'auto', target = 'conta' } = {}) {
    const params = new URLSearchParams({
        mode: normalizeCsvTemplateMode(mode),
        target: normalizeImportacaoTarget(target),
    });

    return `api/v1/importacoes/modelos/csv?${params.toString()}`;
}

export function resolveImportacoesHistoricoEndpoint() {
    return 'api/v1/importacoes/historico';
}

export function resolveImportacoesHistoricoPageInitEndpoint() {
    return 'api/v1/importacoes/historico/page-init';
}

export function resolveImportacaoHistoricoEndpoint(batchId) {
    return `${resolveImportacoesHistoricoEndpoint()}/${encodeEndpointSegment(batchId)}`;
}

export function resolveImportacoesJobsEndpoint() {
    return 'api/v1/importacoes/jobs';
}

export function resolveImportacaoJobStatusEndpoint(jobId) {
    return `${resolveImportacoesJobsEndpoint()}/${encodeEndpointSegment(jobId)}`;
}

export function resolveImportacoesPreviewEndpoint() {
    return 'api/v1/importacoes/preview';
}

export function resolveImportacoesConfirmEndpoint() {
    return 'api/v1/importacoes/confirm';
}