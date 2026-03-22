/**
 * ============================================================================
 * LUKRATO - Onboarding V2 Context
 * ============================================================================
 * Lightweight global store for the onboarding flow.
 * ============================================================================
 */

import { ONBOARDING_STEPS, STEP_ORDER, STEP_CONFIG, STORAGE_KEYS } from '../types.js';

const GLOBAL_CONFIG = window.__LK_CONFIG__ || window.__LK_CONFIG || {};
const BASE_URL = GLOBAL_CONFIG.baseUrl || '/';
const ONBOARDING_CONFIG = window.__LK_ONBOARDING_CONFIG__ || {};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || GLOBAL_CONFIG.csrfToken
        || '';
}

function resolveDashboardUrl(apiRedirect = null) {
    if (typeof apiRedirect === 'string' && apiRedirect.trim() !== '') {
        const separator = apiRedirect.includes('?') ? '&' : '?';
        return `${apiRedirect}${separator}first_visit=1`;
    }

    return `${BASE_URL}dashboard?first_visit=1`;
}

function getFirstError(errors) {
    if (Array.isArray(errors)) {
        return errors.find((value) => typeof value === 'string' && value.trim() !== '') || null;
    }

    if (errors && typeof errors === 'object') {
        for (const value of Object.values(errors)) {
            if (typeof value === 'string' && value.trim() !== '') {
                return value;
            }
        }
    }

    return null;
}

function getHttpErrorMessage(response, fallbackMessage) {
    if (response.status === 401) {
        return 'Sua sessao expirou. Faca login novamente.';
    }

    if (response.status === 403) {
        return 'Voce nao tem permissao para continuar.';
    }

    if (response.status === 404) {
        return 'Nao foi possivel acessar o endpoint do onboarding.';
    }

    if (response.status === 419) {
        return 'Seu token expirou. Recarregue a pagina e tente novamente.';
    }

    if (response.status >= 500) {
        return 'O servidor encontrou um erro inesperado. Tente novamente.';
    }

    return fallbackMessage;
}

function getPlainTextMessage(text) {
    if (!text) {
        return null;
    }

    const normalized = text
        .replace(/<style[\s\S]*?<\/style>/gi, ' ')
        .replace(/<script[\s\S]*?<\/script>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return normalized !== '' ? normalized : null;
}

function getApiMessage(result, fallbackMessage) {
    if (result && typeof result.message === 'string' && result.message.trim() !== '') {
        return result.message.trim();
    }

    const firstError = getFirstError(result?.errors);
    if (firstError) {
        return firstError;
    }

    return fallbackMessage;
}

async function parseApiResponse(response, fallbackMessage) {
    const raw = await response.text();
    const trimmed = raw.trim();
    const contentType = response.headers.get('content-type') || '';
    const looksLikeJson = contentType.includes('application/json')
        || trimmed.startsWith('{')
        || trimmed.startsWith('[');

    if (trimmed === '') {
        if (!response.ok) {
            throw new Error(getHttpErrorMessage(response, fallbackMessage));
        }

        return { success: true, data: null, message: '' };
    }

    if (looksLikeJson) {
        try {
            return JSON.parse(trimmed);
        } catch (error) {
            console.warn('[Onboarding] Invalid JSON response:', trimmed.slice(0, 240));
            throw new Error('O servidor retornou uma resposta invalida. Recarregue a pagina e tente novamente.');
        }
    }

    const plainTextMessage = getPlainTextMessage(trimmed);
    throw new Error(plainTextMessage || getHttpErrorMessage(response, fallbackMessage));
}

function getInitialGoal() {
    if (typeof ONBOARDING_CONFIG.goal === 'string' && ONBOARDING_CONFIG.goal.trim() !== '') {
        return ONBOARDING_CONFIG.goal.trim();
    }

    try {
        const savedGoal = localStorage.getItem(STORAGE_KEYS.USER_GOAL);
        return savedGoal && savedGoal.trim() !== '' ? savedGoal.trim() : null;
    } catch (e) {
        return null;
    }
}

function getInitialStep() {
    const step = ONBOARDING_CONFIG.initialStep;
    return STEP_ORDER.includes(step) ? step : ONBOARDING_STEPS.WELCOME;
}

function createFreshState() {
    const currentStep = getInitialStep();
    const stepIndex = STEP_ORDER.indexOf(currentStep);

    return {
        currentStep,
        stepIndex: stepIndex >= 0 ? stepIndex : 0,
        completed: false,
        data: {
            goal: getInitialGoal(),
            account: ONBOARDING_CONFIG.conta || null,
            transaction: null
        },
        loading: false,
        error: null,
        userName: GLOBAL_CONFIG.userName || '',
        userId: GLOBAL_CONFIG.userId || null
    };
}

function getInitialState() {
    try {
        const saved = localStorage.getItem(STORAGE_KEYS.ONBOARDING_STATE);
        if (!saved) {
            return createFreshState();
        }

        const parsed = JSON.parse(saved);
        if (parsed.currentStep === ONBOARDING_STEPS.SUCCESS && parsed.completed) {
            return createFreshState();
        }

        const currentStep = STEP_ORDER.includes(parsed.currentStep)
            ? parsed.currentStep
            : getInitialStep();
        const freshState = createFreshState();

        return {
            ...freshState,
            ...parsed,
            currentStep,
            stepIndex: STEP_ORDER.indexOf(currentStep),
            data: {
                ...freshState.data,
                ...(parsed.data || {})
            }
        };
    } catch (e) {
        console.warn('[Onboarding] Failed to restore state:', e);
        return createFreshState();
    }
}

class OnboardingStore {
    constructor() {
        this.state = getInitialState();
        this.listeners = new Set();
    }

    getState() {
        return this.state;
    }

    setState(updater) {
        const newState = typeof updater === 'function'
            ? updater(this.state)
            : { ...this.state, ...updater };

        this.state = newState;
        this.persist();
        this.notify();
    }

    subscribe(listener) {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    notify() {
        this.listeners.forEach((listener) => listener(this.state));
    }

    persist() {
        try {
            localStorage.setItem(STORAGE_KEYS.ONBOARDING_STATE, JSON.stringify(this.state));
        } catch (e) {
            console.warn('[Onboarding] Failed to persist state:', e);
        }
    }

    nextStep() {
        const currentIndex = STEP_ORDER.indexOf(this.state.currentStep);
        if (currentIndex < STEP_ORDER.length - 1) {
            const nextStep = STEP_ORDER[currentIndex + 1];
            this.setState({
                currentStep: nextStep,
                stepIndex: currentIndex + 1,
                error: null
            });
        }
    }

    prevStep() {
        const currentIndex = STEP_ORDER.indexOf(this.state.currentStep);
        if (currentIndex > 0) {
            const prevStep = STEP_ORDER[currentIndex - 1];
            this.setState({
                currentStep: prevStep,
                stepIndex: currentIndex - 1,
                error: null
            });
        }
    }

    goToStep(step) {
        const stepIndex = STEP_ORDER.indexOf(step);
        if (stepIndex !== -1) {
            this.setState({
                currentStep: step,
                stepIndex,
                error: null
            });
        }
    }

    setGoal(goalId) {
        this.setState((state) => ({
            ...state,
            data: { ...state.data, goal: goalId }
        }));

        try {
            localStorage.setItem(STORAGE_KEYS.USER_GOAL, goalId);
        } catch (e) {
            console.warn('[Onboarding] Failed to persist goal:', e);
        }
    }

    async saveAccount(accountData) {
        this.setState({ loading: true, error: null });

        try {
            const response = await fetch(`${BASE_URL}api/onboarding/conta/json`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(accountData)
            });

            const result = await parseApiResponse(response, 'Erro ao criar conta');

            if (!response.ok || !result.success) {
                throw new Error(getApiMessage(result, 'Erro ao criar conta'));
            }

            const persistedAccount = {
                ...(result.data || {}),
                nome: result?.data?.nome ?? accountData.nome,
                saldo: result?.data?.saldo ?? accountData.saldo_inicial ?? 0,
                instituicao_financeira_id: result?.data?.instituicao_financeira_id
                    ?? accountData.instituicao_financeira_id
                    ?? null,
                instituicao: result?.data?.instituicao ?? accountData.instituicao ?? null
            };

            this.setState((state) => ({
                ...state,
                loading: false,
                data: { ...state.data, account: persistedAccount }
            }));

            return persistedAccount;
        } catch (error) {
            this.setState({ loading: false, error: error.message });
            throw error;
        }
    }

    async saveTransaction(transactionData) {
        this.setState({ loading: true, error: null });

        try {
            const response = await fetch(`${BASE_URL}api/onboarding/lancamento/json`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(transactionData)
            });

            const result = await parseApiResponse(response, 'Erro ao criar lancamento');

            if (!response.ok || !result.success) {
                throw new Error(getApiMessage(result, 'Erro ao criar lancamento'));
            }

            const persistedTransaction = {
                ...(result.data || {}),
                tipo: result?.data?.tipo ?? transactionData.tipo,
                valor: result?.data?.valor ?? transactionData.valor,
                descricao: result?.data?.descricao ?? transactionData.descricao,
                categoria_id: result?.data?.categoria_id ?? transactionData.categoria_id ?? null,
                conta_id: result?.data?.conta_id ?? transactionData.conta_id ?? null
            };

            const transactionAmount = Math.abs(Number(persistedTransaction.valor) || 0);
            const balanceDelta = persistedTransaction.tipo === 'receita'
                ? transactionAmount
                : -transactionAmount;

            this.setState((state) => ({
                ...state,
                loading: false,
                data: {
                    ...state.data,
                    transaction: persistedTransaction,
                    account: state.data.account
                        ? {
                            ...state.data.account,
                            saldo: (Number(state.data.account.saldo) || 0) + balanceDelta
                        }
                        : state.data.account
                }
            }));

            return persistedTransaction;
        } catch (error) {
            this.setState({ loading: false, error: error.message });
            throw error;
        }
    }

    async skipTransaction() {
        this.setState({ loading: false, error: null });
        this.nextStep();
    }

    async completeOnboarding() {
        this.setState({ loading: true });

        try {
            const response = await fetch(`${BASE_URL}api/onboarding/complete`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    goal: this.state.data.goal
                })
            });

            const result = await parseApiResponse(response, 'Erro ao concluir onboarding');

            try {
                localStorage.setItem(STORAGE_KEYS.ONBOARDING_COMPLETED, 'true');
                localStorage.removeItem(STORAGE_KEYS.ONBOARDING_STATE);
            } catch (e) {
                console.warn('[Onboarding] Failed to update completion state:', e);
            }

            this.setState({
                completed: true,
                loading: false
            });

            setTimeout(() => {
                window.location.href = resolveDashboardUrl(result?.data?.redirect ?? null);
            }, 2500);
        } catch (error) {
            setTimeout(() => {
                window.location.href = resolveDashboardUrl();
            }, 2000);
        }
    }

    reset() {
        try {
            localStorage.removeItem(STORAGE_KEYS.ONBOARDING_STATE);
            localStorage.removeItem(STORAGE_KEYS.ONBOARDING_COMPLETED);
            localStorage.removeItem(STORAGE_KEYS.USER_GOAL);
        } catch (e) {
            console.warn('[Onboarding] Failed to reset persisted state:', e);
        }

        this.state = createFreshState();
        this.notify();
    }

    getProgress() {
        return STEP_CONFIG[this.state.currentStep]?.progress || 0;
    }

    canGoBack() {
        return this.state.stepIndex > 0 && this.state.currentStep !== ONBOARDING_STEPS.SUCCESS;
    }

    canSkip() {
        return STEP_CONFIG[this.state.currentStep]?.skippable || false;
    }
}

export const onboardingStore = new OnboardingStore();

export function useOnboarding() {
    return {
        state: onboardingStore.getState(),
        nextStep: () => onboardingStore.nextStep(),
        prevStep: () => onboardingStore.prevStep(),
        goToStep: (step) => onboardingStore.goToStep(step),
        setGoal: (goal) => onboardingStore.setGoal(goal),
        saveAccount: (data) => onboardingStore.saveAccount(data),
        saveTransaction: (data) => onboardingStore.saveTransaction(data),
        skipTransaction: () => onboardingStore.skipTransaction(),
        completeOnboarding: () => onboardingStore.completeOnboarding(),
        getProgress: () => onboardingStore.getProgress(),
        canGoBack: () => onboardingStore.canGoBack(),
        canSkip: () => onboardingStore.canSkip(),
        subscribe: (fn) => onboardingStore.subscribe(fn)
    };
}

if (typeof window !== 'undefined') {
    window.__LK_ONBOARDING_STORE__ = onboardingStore;
}
