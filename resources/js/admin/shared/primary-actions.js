import { getBaseUrl } from './api.js';

const ACTION_CREATE_ACCOUNT = 'create_account';
const ACTION_CREATE_TRANSACTION = 'create_transaction';

function normalizeActionType(value, accountCount = 0) {
    const normalized = String(value || '').trim().toLowerCase();

    if (normalized === ACTION_CREATE_ACCOUNT || normalized === ACTION_CREATE_TRANSACTION) {
        return normalized;
    }

    return Number(accountCount) > 0 ? ACTION_CREATE_TRANSACTION : ACTION_CREATE_ACCOUNT;
}

function normalizeActionUrl(path) {
    const baseUrl = getBaseUrl();
    const rawPath = String(path || '').trim();

    if (rawPath === '') {
        return baseUrl;
    }

    if (/^https?:\/\//i.test(rawPath)) {
        return rawPath;
    }

    return `${baseUrl}${rawPath.replace(/^\/+/, '')}`;
}

export function resolvePrimaryActionMeta(meta = {}, fallback = {}) {
    const accountCountRaw = meta.real_account_count
        ?? meta.accountCount
        ?? fallback.accountCount
        ?? 0;
    const accountCount = Math.max(0, Number(accountCountRaw) || 0);
    const actionType = normalizeActionType(
        meta.primary_action ?? meta.cta_action ?? fallback.actionType,
        accountCount
    );
    const ctaUrl = normalizeActionUrl(
        meta.cta_url
        ?? fallback.ctaUrl
        ?? (actionType === ACTION_CREATE_ACCOUNT ? 'contas' : 'lancamentos/novo')
    );
    const ctaLabel = String(
        meta.cta_label
        ?? fallback.ctaLabel
        ?? (actionType === ACTION_CREATE_ACCOUNT ? 'Criar primeira conta' : 'Adicionar agora')
    );

    return {
        actionType,
        accountCount,
        ctaUrl,
        ctaLabel,
    };
}

export function openPrimaryAction(meta = {}, fallback = {}) {
    const action = resolvePrimaryActionMeta(meta, fallback);

    if (action.actionType === ACTION_CREATE_ACCOUNT) {
        const contaModalExists = Boolean(document.getElementById('modalContaOverlay'));

        if (contaModalExists && window.contasManager?.openModal) {
            window.contasManager.openModal('create');
            return action;
        }

        window.location.href = action.ctaUrl;
        return action;
    }

    if (window.lancamentoGlobalManager?.openModal) {
        window.lancamentoGlobalManager.openModal();
        return action;
    }

    if (window.LK?.modals?.openLancamentoModal) {
        window.LK.modals.openLancamentoModal();
        return action;
    }

    window.location.href = action.ctaUrl;
    return action;
}

export function getDashboardPrimaryActionCopy(meta = {}, fallback = {}) {
    const action = resolvePrimaryActionMeta(meta, fallback);

    if (action.actionType === ACTION_CREATE_ACCOUNT) {
        return {
            action,
            quickStartTitle: 'Comece criando sua primeira conta',
            quickStartDescription: 'Antes de registrar transações, você precisa cadastrar pelo menos uma conta para o Lukrato saber de onde o dinheiro entra e sai.',
            quickStartButton: 'Criar conta',
            quickStartNotes: [
                'Você libera os lançamentos assim que salvar a conta',
                'O saldo inicial já prepara o dashboard para seus dados reais',
                'Depois disso, receitas e despesas entram no fluxo normal',
            ],
            emptyStateTitle: 'Seu histórico real começa na primeira conta',
            emptyStateDescription: 'Crie uma conta para liberar saldo, transações e categorias com seus próprios dados.',
            emptyStateButton: 'Criar conta',
            chartEmptyTitle: 'Seu gráfico entra em ação depois da primeira conta',
            chartEmptyDescription: 'Crie uma conta para começar a registrar transações e ver para onde o dinheiro está indo.',
            chartEmptyButton: 'Criar conta',
            shouldOfferTour: false,
        };
    }

    return {
        action,
        quickStartTitle: 'Comece adicionando sua primeira transação',
        quickStartDescription: 'Enquanto você ainda não cadastrou nada, o Lukrato mostra um exemplo para você entender o fluxo. Assim que chegar seu primeiro dado real, a demonstração some automaticamente.',
        quickStartButton: 'Adicionar agora',
        quickStartNotes: [
            'O saldo começa a reagir imediatamente',
            'Compare o exemplo com seus dados reais depois',
            'As categorias passam a refletir seu uso assim que você começar',
        ],
        emptyStateTitle: 'Seu histórico começa na primeira transação.',
        emptyStateDescription: 'Adicione um lançamento para ver saldo, gráfico e categorias ganhando contexto real.',
        emptyStateButton: 'Adicionar agora',
        chartEmptyTitle: 'Seu gráfico aparece com dados reais',
        chartEmptyDescription: 'Registre sua primeira transação para começar a enxergar para onde o dinheiro está indo.',
        chartEmptyButton: 'Registrar transação',
        shouldOfferTour: true,
    };
}

export { ACTION_CREATE_ACCOUNT, ACTION_CREATE_TRANSACTION };
