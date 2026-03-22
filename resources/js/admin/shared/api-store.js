import { apiGet, buildUrl } from './api.js';

const memoryCache = new Map();
const inFlightRequests = new Map();

function isFresh(entry, ttlMs) {
    if (!entry) {
        return false;
    }

    if (ttlMs <= 0) {
        return true;
    }

    return (Date.now() - entry.timestamp) < ttlMs;
}

export function readCachedValue(cacheKey, ttlMs = 0) {
    const entry = memoryCache.get(cacheKey);
    return isFresh(entry, ttlMs) ? entry.value : undefined;
}

export async function getCachedValue(cacheKey, loader, { ttlMs = 30000, force = false } = {}) {
    if (!force) {
        const cached = readCachedValue(cacheKey, ttlMs);
        if (cached !== undefined) {
            return cached;
        }
    } else {
        memoryCache.delete(cacheKey);
    }

    if (inFlightRequests.has(cacheKey)) {
        return inFlightRequests.get(cacheKey);
    }

    const promise = (async () => {
        try {
            const value = await loader();
            memoryCache.set(cacheKey, {
                value,
                timestamp: Date.now(),
            });
            return value;
        } finally {
            inFlightRequests.delete(cacheKey);
        }
    })();

    inFlightRequests.set(cacheKey, promise);
    return promise;
}

export function apiGetCached(url, params = {}, options = {}) {
    const fullUrl = buildUrl(url, params);
    const cacheKey = options.cacheKey || fullUrl;

    return getCachedValue(cacheKey, () => apiGet(url, params), {
        ttlMs: options.ttlMs ?? 30000,
        force: options.force === true,
    });
}

export function invalidateApiCache(keyOrPrefix = '') {
    if (!keyOrPrefix) {
        memoryCache.clear();
        inFlightRequests.clear();
        return;
    }

    Array.from(memoryCache.keys()).forEach((key) => {
        if (key === keyOrPrefix || key.startsWith(keyOrPrefix)) {
            memoryCache.delete(key);
        }
    });

    Array.from(inFlightRequests.keys()).forEach((key) => {
        if (key === keyOrPrefix || key.startsWith(keyOrPrefix)) {
            inFlightRequests.delete(key);
        }
    });
}
