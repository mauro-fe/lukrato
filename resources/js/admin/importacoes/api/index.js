import {
    resolveImportacaoJobStatusEndpoint,
    resolveImportacoesConfiguracoesEndpoint,
    resolveImportacoesConfirmEndpoint,
    resolveImportacoesPageInitEndpoint,
    resolveImportacoesPreviewEndpoint,
} from '../../api/endpoints/importacoes.js';
import { resolvePlanLimitsEndpoint } from '../../api/endpoints/billing.js';
import {
    resolveCategoriesEndpoint,
    resolveCategorySubcategoriesEndpoint,
} from '../../api/endpoints/finance.js';
import { appendCsrfToken, fetchApiJson } from '../app.js';

function buildFormDataForAction(form, scope, payload, file) {
    const formData = form ? new FormData(form) : new FormData();
    formData.set('source_type', String(payload.source_type || 'ofx'));
    formData.set('import_target', String(payload.import_target || 'conta'));
    formData.set('async', String(payload.async || '0'));
    formData.set('categorize_preview', String(payload.categorize_preview || '0'));

    if (payload.row_overrides && typeof payload.row_overrides === 'object') {
        formData.set('row_overrides', JSON.stringify(payload.row_overrides));
    } else {
        formData.delete('row_overrides');
    }

    if (payload.import_target === 'cartao') {
        formData.set('cartao_id', String(payload.cartao_id || ''));
        formData.delete('conta_id');
    } else {
        formData.set('conta_id', String(payload.conta_id || ''));
        formData.delete('cartao_id');
    }

    if (file instanceof File) {
        formData.set('file', file, file.name);
    }

    appendCsrfToken(formData, scope);

    return formData;
}

export function createImportacoesIndexApi({ form = null, scope = document } = {}) {
    return {
        async loadPageInit(params = {}) {
            const query = new URLSearchParams();
            const importTarget = String(params.import_target || '').trim().toLowerCase();
            const sourceType = String(params.source_type || '').trim().toLowerCase();
            const contaId = Number.parseInt(String(params.conta_id || '0'), 10);
            const cartaoId = Number.parseInt(String(params.cartao_id || '0'), 10);

            if (importTarget === 'conta' || importTarget === 'cartao') {
                query.set('import_target', importTarget);
            }

            if (sourceType === 'ofx' || sourceType === 'csv') {
                query.set('source_type', sourceType);
            }

            if (Number.isFinite(contaId) && contaId > 0) {
                query.set('conta_id', String(contaId));
            }

            if (Number.isFinite(cartaoId) && cartaoId > 0) {
                query.set('cartao_id', String(cartaoId));
            }

            const endpoint = query.toString() === ''
                ? resolveImportacoesPageInitEndpoint()
                : `${resolveImportacoesPageInitEndpoint()}?${query.toString()}`;

            return fetchApiJson(endpoint, {
                method: 'GET',
            });
        },

        async loadPlanLimits() {
            return fetchApiJson(resolvePlanLimitsEndpoint(), {
                method: 'GET',
            });
        },

        async loadProfileConfig(contaId) {
            return fetchApiJson(`${resolveImportacoesConfiguracoesEndpoint()}?conta_id=${encodeURIComponent(String(contaId))}`, {
                method: 'GET',
            });
        },

        async loadCategories() {
            return fetchApiJson(resolveCategoriesEndpoint(), {
                method: 'GET',
            });
        },

        async loadSubcategories(categoriaId) {
            return fetchApiJson(resolveCategorySubcategoriesEndpoint(categoriaId), {
                method: 'GET',
            });
        },

        async requestPreview(payload, file) {
            const formData = buildFormDataForAction(form, scope, payload, file);
            return fetchApiJson(resolveImportacoesPreviewEndpoint(), {
                method: 'POST',
                body: formData,
            });
        },

        async requestConfirm(payload, file) {
            const formData = buildFormDataForAction(form, scope, payload, file);
            return fetchApiJson(resolveImportacoesConfirmEndpoint(), {
                method: 'POST',
                body: formData,
            });
        },

        async requestJobStatus(jobId) {
            return fetchApiJson(resolveImportacaoJobStatusEndpoint(jobId), {
                method: 'GET',
            });
        },
    };
}