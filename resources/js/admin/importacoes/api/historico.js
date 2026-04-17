import {
    resolveImportacaoHistoricoEndpoint,
    resolveImportacoesHistoricoPageInitEndpoint,
} from '../../api/endpoints/importacoes.js';
import { fetchApiJson, findCsrfToken } from '../app.js';

export async function loadImportacoesHistoricoPageInit(params = {}) {
    const query = new URLSearchParams();
    const contaId = Number.parseInt(String(params.conta_id || '0'), 10);
    const sourceType = String(params.source_type || '').trim().toLowerCase();
    const importTarget = String(params.import_target || '').trim().toLowerCase();
    const status = String(params.status || '').trim().toLowerCase();

    if (Number.isFinite(contaId) && contaId > 0) {
        query.set('conta_id', String(contaId));
    }

    if (sourceType === 'ofx' || sourceType === 'csv') {
        query.set('source_type', sourceType);
    }

    if (importTarget === 'conta' || importTarget === 'cartao') {
        query.set('import_target', importTarget);
    }

    if (status !== '') {
        query.set('status', status);
    }

    const endpoint = query.toString() === ''
        ? resolveImportacoesHistoricoPageInitEndpoint()
        : `${resolveImportacoesHistoricoPageInitEndpoint()}?${query.toString()}`;

    return fetchApiJson(endpoint, {
        method: 'GET',
    });
}

export async function deleteImportacaoHistorico(batchId, scope = document) {
    return fetchApiJson(resolveImportacaoHistoricoEndpoint(batchId), {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: findCsrfToken(scope),
        }),
    });
}