import { apiFetch, getCSRFToken, getErrorMessage } from '../shared/api.js';

const VALID_PREVIEW_STATES = new Set([
    'idle',
    'file_selected',
    'loading_preview',
    'preview_ready',
    'preview_error',
    'confirming',
    'confirmed',
]);

function normalizePreviewStatus(status) {
    const normalized = String(status || '').trim().toLowerCase();
    return VALID_PREVIEW_STATES.has(normalized) ? normalized : 'idle';
}

function parseInteger(value) {
    const parsed = Number.parseInt(String(value || ''), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function normalizeImportTarget(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized === 'cartao' ? 'cartao' : 'conta';
}

export function normalizeSourceType(value, fallback = 'ofx') {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized === 'csv' || normalized === 'ofx' ? normalized : fallback;
}

function normalizeMessageList(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((entry) => String(entry || '').trim())
        .filter((entry) => entry.length > 0);
}

function flattenErrorMap(errorMap) {
    if (!errorMap || typeof errorMap !== 'object') {
        return [];
    }

    const messages = [];
    Object.values(errorMap).forEach((value) => {
        if (Array.isArray(value)) {
            value.forEach((message) => {
                const normalized = String(message || '').trim();
                if (normalized) {
                    messages.push(normalized);
                }
            });
            return;
        }

        const normalized = String(value || '').trim();
        if (normalized) {
            messages.push(normalized);
        }
    });

    return messages;
}

export function findCsrfToken(scope = document) {
    const fromForm = scope?.querySelector?.('input[name="csrf_token"]')?.value;
    if (fromForm) {
        return String(fromForm);
    }

    const sharedToken = getCSRFToken();
    if (sharedToken) {
        return String(sharedToken);
    }

    return '';
}

export function appendCsrfToken(formData, scope = document) {
    if (!(formData instanceof FormData)) {
        return;
    }

    if (formData.has('csrf_token')) {
        return;
    }

    const token = findCsrfToken(scope);
    if (token) {
        formData.append('csrf_token', token);
    }
}

function buildFetchApiJsonError(error, payload = null) {
    const resolvedPayload = payload ?? error?.payload ?? error?.data ?? null;
    const messages = normalizeMessageList([
        ...flattenErrorMap(resolvedPayload?.errors),
        resolvedPayload?.message,
    ]);
    const message = String(
        resolvedPayload?.message
        || messages[0]
        || getErrorMessage(error, 'Falha na requisicao.')
    ).trim();

    const requestError = error instanceof Error
        ? error
        : new Error(message || 'Falha na requisicao.');

    requestError.message = message || requestError.message || 'Falha na requisicao.';
    requestError.response = error?.response ?? null;
    requestError.payload = resolvedPayload;
    requestError.messages = messages;

    return requestError;
}

export async function fetchApiJson(url, options = {}) {
    try {
        const payload = await apiFetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });

        if (payload?.success === true) {
            return payload;
        }

        throw buildFetchApiJsonError(null, payload);
    } catch (error) {
        if (error?.payload && Array.isArray(error?.messages)) {
            throw error;
        }

        throw buildFetchApiJsonError(error);
    }
}

function createInitialState(root) {
    return {
        selectedImportTarget: normalizeImportTarget(root?.dataset.impImportTarget || 'conta'),
        selectedSourceType: normalizeSourceType(root?.dataset.impSourceType || 'ofx', 'ofx'),
        selectedAccountId: parseInteger(root?.dataset.impActiveAccountId || null),
        selectedCardId: parseInteger(root?.dataset.impActiveCardId || null),
        selectedFile: null,
        selectedFileDetectedSourceType: '',
        selectedFileDetectedImportTarget: '',
        sourceAutoAdjustedToDetectedFile: false,
        targetAutoAdjustedToDetectedFile: false,
        fileDropActive: false,
        showOnlyPendingCategories: false,
        previewStatus: 'idle',
        previewRows: [],
        previewWarnings: [],
        previewErrors: [],
        previewCanConfirm: false,
        jobProgressMessage: '',
        previewSummary: {
            fileName: '',
            totalRows: 0,
            importedRows: 0,
            duplicateRows: 0,
            errorRows: 0,
            categorizedRows: 0,
            uncategorizedRows: 0,
            userRuleSuggestedRows: 0,
            globalRuleSuggestedRows: 0,
            categorizationApplied: false,
        },
    };
}

export function bootImportacoesPage(pageId) {
    const root = document.querySelector('[data-importacoes-page]');
    if (!root) {
        return null;
    }

    const state = createInitialState(root);
    const listeners = new Set();

    const notify = () => {
        listeners.forEach((listener) => listener(state));
        root.dispatchEvent(
            new CustomEvent('importacoes:state-changed', {
                detail: { ...state },
            })
        );
    };

    const setState = (nextPatch = {}) => {
        Object.assign(state, nextPatch);
        state.selectedImportTarget = normalizeImportTarget(state.selectedImportTarget);
        state.previewStatus = normalizePreviewStatus(state.previewStatus);

        root.dataset.previewStatus = state.previewStatus;
        root.dataset.importTarget = state.selectedImportTarget;
        root.dataset.sourceType = String(state.selectedSourceType || '');
        root.dataset.selectedAccountId = String(state.selectedAccountId || '');
        root.dataset.selectedCardId = String(state.selectedCardId || '');

        notify();
    };

    root.setAttribute('data-importacoes-ready', 'true');
    root.setAttribute('data-importacoes-kind', pageId);
    root.dataset.previewStatus = state.previewStatus;
    root.dataset.importTarget = state.selectedImportTarget;
    root.dataset.sourceType = state.selectedSourceType;
    root.dataset.selectedAccountId = String(state.selectedAccountId || '');
    root.dataset.selectedCardId = String(state.selectedCardId || '');

    return {
        pageId,
        root,
        state,
        setState,
        onStateChange(listener) {
            if (typeof listener === 'function') {
                listeners.add(listener);
                return () => listeners.delete(listener);
            }

            return () => { };
        },
        resetPreview() {
            setState({
                previewStatus: 'idle',
                previewRows: [],
                previewWarnings: [],
                previewErrors: [],
                previewCanConfirm: false,
                selectedFileDetectedSourceType: '',
                selectedFileDetectedImportTarget: '',
                sourceAutoAdjustedToDetectedFile: false,
                targetAutoAdjustedToDetectedFile: false,
                fileDropActive: false,
                previewSummary: {
                    fileName: '',
                    totalRows: 0,
                    importedRows: 0,
                    duplicateRows: 0,
                    errorRows: 0,
                    categorizedRows: 0,
                    uncategorizedRows: 0,
                },
            });
        },
    };
}
