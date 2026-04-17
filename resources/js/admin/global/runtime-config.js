import { resolveUserBootstrapEndpoint } from '../api/endpoints/preferences.js';
import { apiGet, getApiPayload, getErrorMessage } from '../shared/api.js';
import { readRuntimeApiBaseUrl, readRuntimeBaseUrl } from '../shared/runtime.js';

export { normalizeBaseUrl, readRuntimeApiBaseUrl, readRuntimeBaseUrl, readRuntimeCsrfToken } from '../shared/runtime.js';

const RUNTIME_CONFIG_EVENT = 'lukrato:runtime-config-updated';

const runtimeConfig = (() => {
    if (window.__LK_CONFIG && typeof window.__LK_CONFIG === 'object') {
        return window.__LK_CONFIG;
    }

    window.__LK_CONFIG = {};
    return window.__LK_CONFIG;
})();

let bootstrapPromise = null;
let hasHydratedBootstrap = false;

function isPlainObject(value) {
    return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

function clonePlainObject(value) {
    return isPlainObject(value) ? { ...value } : {};
}

function readBootstrapContextFromDom() {
    if (typeof document === 'undefined') {
        return {
            menu: '',
            view_id: '',
            view_path: '',
        };
    }

    const source = document.body?.dataset || document.documentElement?.dataset || {};

    return {
        menu: String(source.lkMenu || '').trim().toLowerCase(),
        view_id: String(source.lkViewId || '').trim().toLowerCase(),
        view_path: String(source.lkViewPath || '').trim().toLowerCase(),
    };
}

function normalizeHelpCenter(value) {
    const source = isPlainObject(value) ? value : {};
    const settings = isPlainObject(source.settings) ? source.settings : {};

    return {
        settings: {
            auto_offer: settings.auto_offer !== false,
        },
        tour_completed: isPlainObject(source.tour_completed) ? source.tour_completed : {},
        offer_dismissed: isPlainObject(source.offer_dismissed) ? source.offer_dismissed : {},
        tips_seen: isPlainObject(source.tips_seen) ? source.tips_seen : {},
    };
}

function normalizeAvatarSettings(value) {
    const source = isPlainObject(value) ? value : {};
    const positionX = Number(source.position_x);
    const positionY = Number(source.position_y);
    const zoom = Number(source.zoom);

    return {
        position_x: Number.isFinite(positionX) ? Math.max(0, Math.min(100, positionX)) : 50,
        position_y: Number.isFinite(positionY) ? Math.max(0, Math.min(100, positionY)) : 50,
        zoom: Number.isFinite(zoom) ? Math.max(1, Math.min(2, zoom)) : 1,
    };
}

function mergeInto(target, patch) {
    Object.entries(patch).forEach(([key, value]) => {
        if (isPlainObject(value)) {
            const nextTarget = isPlainObject(target[key]) ? target[key] : {};
            target[key] = nextTarget;
            mergeInto(nextTarget, value);
            return;
        }

        if (Array.isArray(value)) {
            target[key] = [...value];
            return;
        }

        target[key] = value;
    });
}

function syncGlobalRuntimeConfig() {
    runtimeConfig.baseUrl = typeof runtimeConfig.baseUrl === 'string' && runtimeConfig.baseUrl.trim() !== ''
        ? runtimeConfig.baseUrl.replace(/\/?$/, '/')
        : readRuntimeBaseUrl();
    runtimeConfig.apiBaseUrl = typeof runtimeConfig.apiBaseUrl === 'string' && runtimeConfig.apiBaseUrl.trim() !== ''
        ? runtimeConfig.apiBaseUrl.replace(/\/?$/, '/')
        : readRuntimeApiBaseUrl();
    runtimeConfig.csrfTtl = Number.isFinite(Number(runtimeConfig.csrfTtl))
        ? Number(runtimeConfig.csrfTtl)
        : 3600;
    runtimeConfig.helpCenter = normalizeHelpCenter(runtimeConfig.helpCenter);
    runtimeConfig.userAvatarSettings = normalizeAvatarSettings(runtimeConfig.userAvatarSettings);
    runtimeConfig.bundle = clonePlainObject(runtimeConfig.bundle);

    window.__LK_CONFIG = runtimeConfig;

    if (window.LK && typeof window.LK === 'object') {
        window.LK.csrfTtl = runtimeConfig.csrfTtl;
    }

    return runtimeConfig;
}

function dispatchRuntimeConfigUpdate(patch, source = 'local') {
    if (typeof document === 'undefined') {
        return;
    }

    document.dispatchEvent(new CustomEvent(RUNTIME_CONFIG_EVENT, {
        detail: {
            config: runtimeConfig,
            patch,
            source,
        },
    }));
}

function inferContextFromLocation() {
    const domContext = readBootstrapContextFromDom();
    if (domContext.menu || domContext.view_id || domContext.view_path) {
        return {
            menu: domContext.menu || 'dashboard',
            view_id: domContext.view_id || domContext.menu || 'dashboard',
            view_path: domContext.view_path || domContext.menu || 'dashboard',
        };
    }

    const pathname = String(window.location?.pathname || '').trim();
    if (pathname === '') {
        return {
            menu: 'dashboard',
            view_id: 'dashboard',
            view_path: 'dashboard',
        };
    }

    let relativePath = pathname;

    try {
        const basePath = new URL(readRuntimeBaseUrl(), window.location?.origin || 'http://localhost').pathname;
        if (basePath !== '/' && relativePath.toLowerCase().startsWith(basePath.toLowerCase())) {
            relativePath = relativePath.slice(basePath.length);
        }
    } catch {
        // Ignore base path parsing failures and keep pathname fallback.
    }

    relativePath = relativePath.replace(/^\/+|\/+$/g, '');
    if (relativePath === '') {
        return {
            menu: 'dashboard',
            view_id: 'dashboard',
            view_path: 'dashboard',
        };
    }

    const firstSegment = relativePath.split('/')[0] || 'dashboard';

    return {
        menu: firstSegment,
        view_id: relativePath.replace(/\//g, '-'),
        view_path: relativePath,
    };
}

function buildBootstrapQuery(context = {}) {
    const fallbackContext = inferContextFromLocation();
    const resolvedContext = {
        menu: context.menu ?? runtimeConfig.currentMenu ?? fallbackContext.menu,
        view_id: context.view_id ?? context.viewId ?? runtimeConfig.currentViewId ?? fallbackContext.view_id,
        view_path: context.view_path ?? context.viewPath ?? runtimeConfig.currentViewPath ?? fallbackContext.view_path,
    };

    return Object.fromEntries(
        Object.entries(resolvedContext)
            .filter(([, value]) => typeof value === 'string' && value.trim() !== '')
            .map(([key, value]) => [key, value.trim()])
    );
}

async function requestBootstrap(query) {
    const response = await apiGet(resolveUserBootstrapEndpoint(), query);
    if (response?.success === false) {
        throw new Error(getErrorMessage({ data: response }, 'Nao foi possivel carregar o bootstrap autenticado.'));
    }

    const data = getApiPayload(response, {});

    return isPlainObject(data) ? data : {};
}

export function getRuntimeConfig() {
    return syncGlobalRuntimeConfig();
}

export function applyRuntimeConfig(patch, options = {}) {
    if (isPlainObject(patch)) {
        mergeInto(runtimeConfig, patch);
    }

    syncGlobalRuntimeConfig();

    if (options.dispatch !== false) {
        dispatchRuntimeConfigUpdate(patch, options.source || 'local');
    }

    return runtimeConfig;
}

export function onRuntimeConfigUpdate(listener, options = {}) {
    if (typeof document === 'undefined' || typeof listener !== 'function') {
        return () => { };
    }

    const wrapped = (event) => {
        listener(event.detail?.config || runtimeConfig, event.detail || {});
    };

    document.addEventListener(RUNTIME_CONFIG_EVENT, wrapped, options);

    return () => {
        document.removeEventListener(RUNTIME_CONFIG_EVENT, wrapped, options);
    };
}

export async function ensureRuntimeConfig(context = {}, options = {}) {
    const { force = false, silent = true } = options;

    if (!force && hasHydratedBootstrap) {
        return getRuntimeConfig();
    }

    if (!force && bootstrapPromise) {
        return bootstrapPromise;
    }

    bootstrapPromise = (async () => {
        try {
            const query = buildBootstrapQuery(context);
            const payload = await requestBootstrap(query);
            applyRuntimeConfig(payload, {
                source: 'bootstrap',
            });
            hasHydratedBootstrap = true;
        } catch (error) {
            if (!silent) {
                console.error('Erro ao hidratar runtime config', error);
            }
        } finally {
            const resolvedConfig = getRuntimeConfig();
            bootstrapPromise = null;
            return resolvedConfig;
        }
    })();

    return bootstrapPromise;
}

syncGlobalRuntimeConfig();

window.LKRuntimeConfig = {
    get: getRuntimeConfig,
    apply: applyRuntimeConfig,
    ensure: ensureRuntimeConfig,
    onUpdate: onRuntimeConfigUpdate,
};

if (typeof document !== 'undefined') {
    const hydrateRuntimeConfig = () => {
        void ensureRuntimeConfig({}, { silent: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hydrateRuntimeConfig, { once: true });
    } else {
        hydrateRuntimeConfig();
    }
}
