export function readBootstrapContext(root) {
    const dataset = root?.dataset || {};

    return {
        menu: String(dataset.bootstrapMenu || '').trim().toLowerCase(),
        view_id: String(dataset.bootstrapViewId || '').trim().toLowerCase(),
        view_path: String(dataset.bootstrapViewPath || '').trim().toLowerCase(),
    };
}

export function resolveBootstrapSource(bootstrapPayload) {
    if (bootstrapPayload?.success && bootstrapPayload?.data && typeof bootstrapPayload.data === 'object' && !Array.isArray(bootstrapPayload.data)) {
        return bootstrapPayload.data;
    }

    const source = bootstrapPayload?.data ?? bootstrapPayload;
    if (!source || typeof source !== 'object' || Array.isArray(source)) {
        return {};
    }

    return source;
}