import {
    resolveImportacoesConfiguracoesEndpoint,
    resolveImportacoesConfiguracoesPageInitEndpoint,
} from '../../api/endpoints/importacoes.js';
import { appendCsrfToken, fetchApiJson } from '../app.js';

export async function loadImportacoesConfiguracoesPageInit(contaId = 0) {
    const normalizedContaId = Number.parseInt(String(contaId || '0'), 10);
    const endpoint = Number.isFinite(normalizedContaId) && normalizedContaId > 0
        ? `${resolveImportacoesConfiguracoesPageInitEndpoint()}?conta_id=${encodeURIComponent(String(normalizedContaId))}`
        : resolveImportacoesConfiguracoesPageInitEndpoint();

    return fetchApiJson(endpoint, {
        method: 'GET',
    });
}

export async function saveImportacoesConfiguracao(formData, scope = document) {
    appendCsrfToken(formData, scope);

    return fetchApiJson(resolveImportacoesConfiguracoesEndpoint(), {
        method: 'POST',
        body: formData,
    });
}