/**
 * ============================================================================
 * LUKRATO - Onboarding V2 Types & Constants
 * ============================================================================
 * Shared constants for the onboarding flow.
 * ============================================================================
 */

/**
 * Steps do onboarding
 * @readonly
 * @enum {string}
 */
export const ONBOARDING_STEPS = {
    WELCOME: 'welcome',
    GOAL: 'goal',
    ACCOUNT: 'account',
    TRANSACTION: 'transaction',
    SUCCESS: 'success'
};

/**
 * Ordem dos steps
 * @type {string[]}
 */
export const STEP_ORDER = [
    ONBOARDING_STEPS.WELCOME,
    ONBOARDING_STEPS.GOAL,
    ONBOARDING_STEPS.ACCOUNT,
    ONBOARDING_STEPS.TRANSACTION,
    ONBOARDING_STEPS.SUCCESS
];

/**
 * Configuracao de cada step
 */
export const STEP_CONFIG = {
    [ONBOARDING_STEPS.WELCOME]: {
        title: 'Inicio',
        progress: 25,
        skippable: false,
        icon: 'sparkles'
    },
    [ONBOARDING_STEPS.GOAL]: {
        title: 'Objetivo',
        progress: 50,
        skippable: false,
        icon: 'target'
    },
    [ONBOARDING_STEPS.ACCOUNT]: {
        title: 'Conta',
        progress: 75,
        skippable: false,
        icon: 'wallet'
    },
    [ONBOARDING_STEPS.TRANSACTION]: {
        title: 'Registro',
        progress: 100,
        skippable: true,
        icon: 'receipt'
    },
    [ONBOARDING_STEPS.SUCCESS]: {
        title: 'Pronto',
        progress: 100,
        skippable: false,
        icon: 'party-popper'
    }
};

/**
 * Objetivos disponiveis para o usuario
 */
export const USER_GOALS = [
    {
        id: 'control',
        icon: 'pie-chart',
        emoji: '\u{1F4CA}',
        title: 'Controlar gastos',
        description: 'Saber para onde meu dinheiro vai todo mes',
        color: '#3b82f6'
    },
    {
        id: 'save',
        icon: 'piggy-bank',
        emoji: '\u{1F437}',
        title: 'Economizar dinheiro',
        description: 'Guardar mais e gastar melhor',
        color: '#10b981'
    },
    {
        id: 'debt',
        icon: 'trending-down',
        emoji: '\u{1F4C9}',
        title: 'Sair das dividas',
        description: 'Organizar e eliminar minhas pendencias',
        color: '#ef4444'
    },
    {
        id: 'organize',
        icon: 'calendar',
        emoji: '\u{1F4C5}',
        title: 'Me organizar',
        description: 'Nao perder contas e prazos',
        color: '#8b5cf6'
    }
];

/**
 * Mensagens motivacionais por objetivo
 */
export const GOAL_MESSAGES = {
    control: {
        welcome: 'Vamos descobrir para onde seu dinheiro esta indo.',
        success: 'Agora voce ja sabe para onde seu dinheiro esta indo.'
    },
    save: {
        welcome: 'Vamos criar o habito de guardar mais e gastar melhor.',
        success: 'Agora voce deu o primeiro passo para economizar com clareza.'
    },
    debt: {
        welcome: 'Vamos organizar suas pendencias e criar um plano simples.',
        success: 'Agora voce comecou a organizar o que precisa sair do caminho.'
    },
    organize: {
        welcome: 'Vamos deixar sua rotina financeira mais previsivel.',
        success: 'Agora sua vida financeira comecou a ficar no lugar.'
    }
};

/**
 * Tempo estimado para completar o onboarding (em segundos)
 */
export const ESTIMATED_TIME_SECONDS = 60;

/**
 * Local storage keys
 */
export const STORAGE_KEYS = {
    ONBOARDING_STATE: 'lk_onboarding_v2_state',
    ONBOARDING_COMPLETED: 'lk_onboarding_v2_completed',
    USER_GOAL: 'lk_user_goal'
};
