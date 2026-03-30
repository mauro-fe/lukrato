import { apiGet, logClientWarning } from './api.js';

function getCollectionPayload(data, key) {
    if (Array.isArray(data)) {
        return data;
    }

    if (Array.isArray(data?.data)) {
        return data.data;
    }

    if (Array.isArray(data?.data?.[key])) {
        return data.data[key];
    }

    if (Array.isArray(data?.[key])) {
        return data[key];
    }

    return [];
}

function toAmount(value) {
    const num = Number(value);
    return Number.isFinite(num) ? Math.abs(num) : 0;
}

export function resolvePlanningPeriod(dateValue) {
    const raw = String(dateValue || '').trim();
    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (match) {
        const year = Number(match[1]);
        const month = Number(match[2]);
        return {
            month,
            year,
            key: `${year}-${String(month).padStart(2, '0')}`
        };
    }

    const today = new Date();
    const month = today.getMonth() + 1;
    const year = today.getFullYear();

    return {
        month,
        year,
        key: `${year}-${String(month).padStart(2, '0')}`
    };
}

export function isSamePlanningPeriod(dateValue, period) {
    if (!period) return false;

    const current = resolvePlanningPeriod(dateValue);
    return current.month === period.month && current.year === period.year;
}

export function isPaidFlag(value, fallback = true) {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    return !(value === false || value === 0 || value === '0' || value === 'false');
}

export function computeAccountEffect({
    type,
    value,
    paymentMethod = '',
    isPaid = true,
    role = 'source'
}) {
    const amount = toAmount(value);
    if (!amount) return 0;

    const normalizedType = String(type || '').toLowerCase();
    const normalizedPayment = String(paymentMethod || '').toLowerCase();

    if (normalizedType === 'transferencia') {
        return role === 'destination' ? amount : -amount;
    }

    if (!isPaidFlag(isPaid, true)) {
        return 0;
    }

    if (normalizedType === 'receita') {
        return normalizedPayment === 'estorno_cartao' ? 0 : amount;
    }

    if (normalizedType === 'despesa') {
        return normalizedPayment === 'cartao_credito' ? 0 : -amount;
    }

    return 0;
}

export function computeSnapshotAccountEffect(snapshot, accountId) {
    const normalizedId = String(accountId ?? '').trim();
    if (!snapshot || !normalizedId) return 0;

    const snapshotType = String(snapshot.tipo || (snapshot.eh_transferencia ? 'transferencia' : '')).toLowerCase();
    const snapshotValue = toAmount(snapshot.valor);
    if (!snapshotValue) return 0;

    if (snapshotType === 'transferencia' || snapshot.eh_transferencia === true) {
        if (String(snapshot.conta_id ?? '') === normalizedId) {
            return -snapshotValue;
        }

        if (String(snapshot.conta_id_destino ?? snapshot.conta_destino_id ?? '') === normalizedId) {
            return snapshotValue;
        }

        return 0;
    }

    if (String(snapshot.conta_id ?? '') !== normalizedId) {
        return 0;
    }

    return computeAccountEffect({
        type: snapshotType,
        value: snapshotValue,
        paymentMethod: snapshot.forma_pagamento,
        isPaid: snapshot.pago
    });
}

class PlanningAlertsStore {
    constructor() {
        this.metas = [];
        this.metasLoaded = false;
        this.metasById = new Map();
        this.metasByConta = new Map();
        this.metaRequest = null;
        this.budgetCache = new Map();
        this.budgetRequests = new Map();
    }

    invalidateMetas() {
        this.metas = [];
        this.metasLoaded = false;
        this.metasById = new Map();
        this.metasByConta = new Map();
        this.metaRequest = null;
    }

    invalidateBudgets() {
        this.budgetCache.clear();
        this.budgetRequests.clear();
    }

    setMetas(metas) {
        const statusOrder = {
            ativa: 0,
            concluida: 1,
            pausada: 2,
            cancelada: 3
        };

        const normalizedMetas = Array.isArray(metas)
            ? metas.filter((meta) => Number(meta?.id ?? 0) > 0)
            : [];

        const sortedMetas = [...normalizedMetas].sort((a, b) => {
            const statusA = String(a?.status || '').trim().toLowerCase();
            const statusB = String(b?.status || '').trim().toLowerCase();
            const rankA = statusOrder[statusA] ?? 99;
            const rankB = statusOrder[statusB] ?? 99;

            if (rankA !== rankB) {
                return rankA - rankB;
            }

            return String(a?.titulo || '')
                .localeCompare(String(b?.titulo || ''), 'pt-BR', { sensitivity: 'base' });
        });

        this.metas = sortedMetas;
        this.metasLoaded = true;
        this.metasById = sortedMetas.reduce((map, meta) => {
            map.set(String(meta.id), meta);
            return map;
        }, new Map());
        this.metasByConta = sortedMetas.reduce((map, meta) => {
            const key = String(meta?.conta_id ?? '').trim();
            if (!key) {
                return map;
            }

            if (!map.has(key)) {
                map.set(key, []);
            }
            map.get(key).push(meta);
            return map;
        }, new Map());
    }

    async ensureMetas(force = false) {
        if (this.metasLoaded && !force) {
            return this.metas;
        }

        if (this.metaRequest && !force) {
            return this.metaRequest;
        }

        this.metaRequest = apiGet('api/financas/metas')
            .then((response) => {
                this.setMetas(getCollectionPayload(response, 'metas'));
                return this.metas;
            })
            .catch((error) => {
                logClientWarning(
                    'Avisos de planejamento: nao foi possivel carregar metas',
                    error,
                    'Falha ao carregar metas'
                );
                return this.metas;
            })
            .finally(() => {
                this.metaRequest = null;
            });

        return this.metaRequest;
    }

    getMetas() {
        return this.metas;
    }

    getMetaById(metaId) {
        return this.metasById.get(String(metaId ?? '')) || null;
    }

    getMetasByConta(contaId) {
        return this.metasByConta.get(String(contaId ?? '')) || [];
    }

    async getBudgets(dateValue, force = false) {
        const period = resolvePlanningPeriod(dateValue);

        if (this.budgetCache.has(period.key) && !force) {
            return this.budgetCache.get(period.key) || [];
        }

        if (this.budgetRequests.has(period.key) && !force) {
            return this.budgetRequests.get(period.key);
        }

        const request = apiGet('api/financas/orcamentos', {
            mes: period.month,
            ano: period.year
        })
            .then((response) => {
                const budgets = getCollectionPayload(response, 'orcamentos');
                this.budgetCache.set(period.key, budgets);
                return budgets;
            })
            .catch((error) => {
                logClientWarning(
                    'Avisos de planejamento: nao foi possivel carregar orcamentos do periodo',
                    error,
                    'Falha ao carregar orcamentos do periodo'
                );
                return this.budgetCache.get(period.key) || [];
            })
            .finally(() => {
                this.budgetRequests.delete(period.key);
            });

        this.budgetRequests.set(period.key, request);
        return request;
    }

    async getBudgetByCategoria(categoriaId, dateValue, force = false) {
        if (!categoriaId) return null;

        const budgets = await this.getBudgets(dateValue, force);
        return budgets.find((item) => String(item?.categoria_id ?? '') === String(categoriaId)) || null;
    }
}

export function getPlanningAlertsStore() {
    if (window.__LK_PLANNING_ALERTS_STORE__) {
        return window.__LK_PLANNING_ALERTS_STORE__;
    }

    const store = new PlanningAlertsStore();
    window.__LK_PLANNING_ALERTS_STORE__ = store;

    if (!window.__LK_PLANNING_ALERTS_STORE_BOUND__) {
        document.addEventListener('lukrato:data-changed', (event) => {
            const resource = String(event.detail?.resource || '').toLowerCase();

            if (resource === 'transactions' || resource === 'orcamentos') {
                store.invalidateBudgets();
            }

            if (resource === 'metas' || resource === 'meta') {
                store.invalidateMetas();
            }
        });

        window.__LK_PLANNING_ALERTS_STORE_BOUND__ = true;
    }

    return store;
}
