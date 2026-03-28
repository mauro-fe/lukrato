import '../../../css/admin/modules/help-center.css';

window.__LK_HELP_CENTER_MANAGED = true;

const DEFAULT_VERSION = 'v1';
const OFFER_DELAY = 1800;
const OFFER_SESSION_PREFIX = `lk_help_offer_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

const PAGE_LABELS = {
    dashboard: 'Dashboard',
    lancamentos: 'Lancamentos',
    contas: 'Contas',
    cartoes: 'Cartoes',
    faturas: 'Faturas',
    categorias: 'Categorias',
    relatorios: 'Relatorios',
    orcamento: 'Orcamento',
    metas: 'Metas',
    gamification: 'Conquistas',
    billing: 'Planos',
    perfil: 'Perfil',
};

const TOUR_CONFIGS = {
    dashboard: {
        label: 'Dashboard',
        version: DEFAULT_VERSION,
        primarySelector: ['#dashboardFirstTransactionCta', '#dashboardEmptyStateCta', '#fabContainer', '.fab-container', '#fabMain', '#fabButton'],
        steps: [
            {
                selector: '#saldoCard',
                title: 'Resumo do mês',
                description: 'Aqui você enxerga saldo, entradas e saídas sem navegar por várias telas.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#dashboardFirstTransactionCta', '#dashboardEmptyStateCta', '#fabContainer', '.fab-container', '#fabMain', '#fabButton'],
                title: 'Adicionar lançamento',
                description: 'Esse é o atalho principal. O valor do Lukrato aparece rápido depois da primeira transação.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#chart-section',
                title: 'Gráfico',
                description: 'Quando houver dados reais, este bloco mostra para onde o dinheiro está indo.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: () => document.querySelector('.sidebar .nav-item[href*="categorias"], .sidebar-nav-group .nav-item[href*="categorias"], a[href*="categorias"].nav-item'),
                title: 'Categorias',
                description: 'Categorias deixam relatórios, insights e metas muito mais claros.',
                side: 'right',
                align: 'center',
            },
        ],
    },
    lancamentos: {
        label: 'Lançamentos',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovoLancamento', '#fabButton'],
        steps: [
            {
                selector: '.lan-summary-strip',
                title: 'Resumo do período',
                description: 'Veja receitas, despesas e saldo do mês de forma imediata.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#btnNovoLancamento', '#fabButton'],
                title: 'Novo lançamento',
                description: 'Comece por aqui quando quiser registrar uma entrada, gasto ou transferência.',
                side: 'left',
                align: 'center',
            },
            {
                selector: '.lk-filters-section',
                title: 'Filtros rápidos',
                description: 'Use período, tipo, categoria e conta para encontrar qualquer registro sem rolar a página.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#lancamentosFeed', '.modern-table-wrapper'],
                title: 'Ações da lista',
                description: 'Cada item permite editar, marcar como pago ou excluir com poucos cliques.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    contas: {
        label: 'Contas',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovaConta'],
        steps: [
            {
                selector: '#contasHero',
                title: 'Visão consolidada',
                description: 'Aqui você acompanha o total guardado e onde seu dinheiro está concentrado.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#btnNovaConta',
                title: 'Nova conta',
                description: 'Cadastre banco, carteira, reserva ou dinheiro em caixa para centralizar seus saldos.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#viewToggle',
                title: 'Cards ou lista',
                description: 'Troque a visualização para ler contas do jeito que ficar mais confortável.',
                side: 'left',
                align: 'center',
            },
            {
                selector: '#accountsGrid',
                title: 'Suas contas',
                description: 'A grade mostra saldo, percentual do total e acessos rápidos para gerenciar cada conta.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    cartoes: {
        label: 'Cartoes',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovoCartao', '#btnNovoCartaoEmpty'],
        steps: [
            {
                selector: ['#btnNovoCartao', '#btnNovoCartaoEmpty'],
                title: 'Adicionar cartão',
                description: 'Cadastre cartões para acompanhar limite, compras e faturas sem planilhas.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['.cart-kpis', '.quick-stats-grid', '.cart-summary-grid'],
                title: 'Visao rapida',
                description: 'Os indicadores ajudam a enxergar limite livre, uso atual e alertas importantes.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['.cartoes-toolbar', '.cart-search-wrapper', '#searchCartoes'],
                title: 'Busca e filtros',
                description: 'Use busca, filtros e alternancia de layout para chegar rapido ao cartao certo.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#cartoesGrid', '.cartoes-grid'],
                title: 'Lista de cartoes',
                description: 'Os cards mostram limite, fechamento e atalhos para editar ou consultar faturas.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    faturas: {
        label: 'Faturas',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnFiltrar', '[data-action="pagar"]', '.btn-pagar'],
        steps: [
            {
                selector: ['.filters-modern', '.faturas-toolbar', '#btnFiltrar'],
                title: 'Contexto do período',
                description: 'Comece pelos filtros para ver a fatura certa e reduzir ruído.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['.view-toggle', '.faturas-view-toggle'],
                title: 'Modo de leitura',
                description: 'Alterne a visualização para focar em parcelas, histórico ou lista compacta.',
                side: 'left',
                align: 'center',
            },
            {
                selector: ['#parcelamentosContainer', '.faturas-grid', '.parcelamentos-grid'],
                title: 'Itens da fatura',
                description: 'Aqui você acompanha parcelas, status e os atalhos para pagar ou revisar um item.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    categorias: {
        label: 'Categorias',
        version: DEFAULT_VERSION,
        primarySelector: ['#formNova button[type="submit"]', '.create-card button[type="submit"]', '.create-card'],
        steps: [
            {
                selector: ['.create-card', '#formNova'],
                title: 'Criar categoria',
                description: 'Crie categorias do seu jeito para deixar relatórios e metas mais inteligentes.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.receitas-card',
                title: 'Receitas',
                description: 'Separe entradas por origem para entender melhor o que sustenta seu caixa.',
                side: 'right',
                align: 'center',
            },
            {
                selector: '.despesas-card',
                title: 'Despesas',
                description: 'Organize gastos por contexto. Isso melhora filtros, IA e leitura dos relatórios.',
                side: 'left',
                align: 'center',
            },
        ],
    },
    relatorios: {
        label: 'Relatorios',
        version: DEFAULT_VERSION,
        primarySelector: ['.rel-section-tabs .rel-section-tab[data-section="relatorios"]', '.tabs-card .tab-btn[data-view="category"]'],
        steps: [
            {
                selector: '.quick-stats-grid',
                title: 'Resumo do mês',
                description: 'As quatro caixas principais resumem o período antes de você mergulhar nos detalhes.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.rel-section-tabs',
                title: 'Troca de seção',
                description: 'Use as abas para sair de visão geral e ir direto para relatórios, insights ou comparativos.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.tabs-card',
                title: 'Modelos de análise',
                description: 'Escolha por categoria, saldo diário, contas, cartões ou visão anual em um clique.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#exportControl',
                title: 'Exportação',
                description: 'Quando precisar compartilhar ou guardar o estudo, exporte daqui.',
                side: 'left',
                align: 'center',
            },
        ],
    },
    orcamento: {
        label: 'Orcamento',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovoOrcamento', '#btnAutoSugerir', '#btnAutoSugerirEmpty'],
        steps: [
            {
                selector: '#summaryOrcamentos',
                title: 'Visão do mês',
                description: 'Aqui você descobre rápido onde o limite está sob controle e onde precisa agir.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#btnAutoSugerir', '#btnAutoSugerirEmpty'],
                title: 'Sugestão automática',
                description: 'Se não quiser montar tudo do zero, o sistema sugere limites com base no seu histórico.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btnNovoOrcamento',
                title: 'Novo limite',
                description: 'Crie um limite manual para a categoria que você quiser controlar de perto.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#orcamentosGrid', '.orc-grid', '.fin-grid'],
                title: 'Cards de acompanhamento',
                description: 'Cada card mostra gasto atual, folga e alertas para o restante do mês.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    metas: {
        label: 'Metas',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovaMetaHeader', '#btnNovaMeta', '#btnNovaMetaEmpty'],
        steps: [
            {
                selector: ['#btnNovaMetaHeader', '#btnNovaMeta', '#btnNovaMetaEmpty'],
                title: 'Criar meta',
                description: 'Defina valor e prazo para transformar um objetivo em plano acompanhado pelo sistema.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#summaryMetas', '.met-summary-grid'],
                title: 'Resumo das metas',
                description: 'Veja rapidamente quanto já acumulou, quantas metas estão ativas e o ritmo do progresso.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#metasGrid', '.met-grid'],
                title: 'Cards das metas',
                description: 'Cada card mostra valor atual, percentual atingido e onde vale concentrar novos aportes.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    gamification: {
        label: 'Conquistas',
        version: DEFAULT_VERSION,
        primarySelector: ['#missionsSection'],
        steps: [
            {
                selector: ['.stats-grid', '.gamification-stats'],
                title: 'Seu progresso',
                description: 'Os indicadores do topo mostram pontos, nível, dias ativos e conquistas liberadas.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#missionsSection',
                title: 'Missões do dia',
                description: 'Aqui você encontra pequenas ações que aceleram o hábito de usar o Lukrato.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['.achievements-section', '.achievement-card'],
                title: 'Conquistas',
                description: 'As conquistas registram marcos importantes e ajudam a deixar a rotina mais motivadora.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    billing: {
        label: 'Planos',
        version: DEFAULT_VERSION,
        primarySelector: ['.plans-grid .plan-card--recommended .surface-button', '.plans-grid .plan-card .surface-button', '.plans-grid'],
        steps: [
            {
                selector: '.billing-header',
                title: 'Comparação de planos',
                description: 'O topo resume o que muda entre Free e Pro antes de você decidir.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['.plan-billing-toggle', '.billing-cycle-toggle', '.cycle-toggle'],
                title: 'Ciclo de cobrança',
                description: 'Troque entre mensal, semestral e anual para ver o custo que faz mais sentido para você.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.plans-grid',
                title: 'Escolha do plano',
                description: 'Os cards mostram recursos, limites e o caminho para assinar ou trocar de plano.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    perfil: {
        label: 'Perfil',
        version: DEFAULT_VERSION,
        primarySelector: ['#btn-save-dados', '#avatarEditBtn'],
        steps: [
            {
                selector: '.profile-header',
                title: 'Seu perfil',
                description: 'Este bloco concentra identidade visual, plano e dados principais da conta.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#avatarEditBtn',
                title: 'Foto e identidade',
                description: 'Atualize a foto quando quiser deixar o produto mais pessoal.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.profile-tabs',
                title: 'Seções do perfil',
                description: 'Troque entre dados, segurança e preferências sem perder o contexto da página.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#btn-save-dados',
                title: 'Salvar ajustes',
                description: 'Fez alguma alteração? Salve aqui para manter tudo sincronizado.',
                side: 'top',
                align: 'center',
            },
        ],
    },
};

function isVisibleElement(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const styles = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();

    return styles.display !== 'none'
        && styles.visibility !== 'hidden'
        && rect.width > 0
        && rect.height > 0;
}

function resolveElement(target) {
    if (!target) {
        return null;
    }

    if (typeof target === 'function') {
        return resolveElement(target());
    }

    if (Array.isArray(target)) {
        for (const item of target) {
            const resolved = resolveElement(item);
            if (resolved) {
                return resolved;
            }
        }

        return null;
    }

    if (target instanceof HTMLElement) {
        return isVisibleElement(target) ? target : null;
    }

    if (typeof target === 'string') {
        const element = document.querySelector(target);
        return isVisibleElement(element) ? element : null;
    }

    return null;
}

function isScrollableElement(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const styles = window.getComputedStyle(element);
    const overflowY = styles.overflowY || styles.overflow || '';

    return /(auto|scroll|overlay)/.test(overflowY)
        && element.scrollHeight > element.clientHeight + 8;
}

function isElementComfortablyInViewport(element, options = {}) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const {
        topOffset = 110,
        bottomOffset = 32,
    } = options;

    const rect = element.getBoundingClientRect();
    const safeTop = topOffset;
    const safeBottom = window.innerHeight - bottomOffset;

    return rect.bottom > safeTop
        && rect.top < safeBottom;
}

function getScrollableAncestors(element) {
    const ancestors = [];
    let current = element?.parentElement || null;

    while (current && current !== document.body) {
        if (isScrollableElement(current)) {
            ancestors.push(current);
        }

        current = current.parentElement;
    }

    const layoutScroller = document.querySelector('.main-content, .content-wrapper, .lk-main');
    if (layoutScroller instanceof HTMLElement && !ancestors.includes(layoutScroller)) {
        ancestors.push(layoutScroller);
    }

    return ancestors;
}

function ensureElementInViewport(element, options = {}) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    const {
        topOffset = 110,
        bottomOffset = 32,
    } = options;

    if (isElementComfortablyInViewport(element, { topOffset, bottomOffset })) {
        return;
    }

    const rect = element.getBoundingClientRect();
    const safeTop = topOffset;
    const safeBottom = window.innerHeight - bottomOffset;
    const isAboveViewport = rect.bottom <= safeTop;
    const isBelowViewport = rect.top >= safeBottom;

    if (!isAboveViewport && !isBelowViewport) {
        return;
    }

    element.scrollIntoView({
        behavior: 'auto',
        block: 'start',
        inline: 'nearest',
    });

    window.scrollBy({
        top: -(topOffset - 24),
        left: 0,
        behavior: 'auto',
    });
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function getOppositeSide(side) {
    switch (side) {
        case 'top':
            return 'bottom';
        case 'bottom':
            return 'top';
        case 'left':
            return 'right';
        case 'right':
            return 'left';
        default:
            return 'center';
    }
}

function normalizeHelpPreferences(value) {
    const fallback = {
        settings: { auto_offer: true },
        tour_completed: {},
        offer_dismissed: {},
        tips_seen: {},
    };

    if (!value || typeof value !== 'object') {
        return fallback;
    }

    return {
        settings: {
            auto_offer: value?.settings?.auto_offer !== false,
        },
        tour_completed: typeof value.tour_completed === 'object' && value.tour_completed ? value.tour_completed : {},
        offer_dismissed: typeof value.offer_dismissed === 'object' && value.offer_dismissed ? value.offer_dismissed : {},
        tips_seen: typeof value.tips_seen === 'object' && value.tips_seen ? value.tips_seen : {},
    };
}

class HelpCenter {
    constructor() {
        this.currentPage = this.getCurrentPage();
        this.preferences = normalizeHelpPreferences(window.__LK_CONFIG?.helpCenter);
        this.tour = null;
        this.activeTourTarget = null;
        this.completedCurrentRun = false;
        this.offerVisible = false;
        this.offerElement = null;
        this.tourRefreshCleanup = null;

        this.elements = {
            helpToggle: document.getElementById('topNavHelpToggle'),
            helpMenu: document.getElementById('topNavHelpMenu'),
            helpCurrentPage: document.getElementById('topNavHelpCurrentPage'),
            helpStatus: document.getElementById('topNavHelpStatus'),
            helpTourBtn: document.getElementById('topNavHelpTourBtn'),
            helpTipsBtn: document.getElementById('topNavHelpTipsBtn'),
            helpAutoOfferBtn: document.getElementById('topNavHelpAutoOfferBtn'),
            helpResetBtn: document.getElementById('topNavHelpResetBtn'),
        };
    }

    init() {
        if (!document.body) {
            return;
        }

        this.createOffer();
        this.bindMenu();
        this.renderMenuState();
        this.scheduleOffer();
    }

    getCurrentPage() {
        const configPage = String(window.__LK_CONFIG?.currentMenu || '').trim().toLowerCase();
        if (configPage) {
            return configPage;
        }

        const path = window.location.pathname.toLowerCase();
        const pageMap = {
            '/dashboard': 'dashboard',
            '/lancamentos': 'lancamentos',
            '/contas': 'contas',
            '/cartoes': 'cartoes',
            '/faturas': 'faturas',
            '/categorias': 'categorias',
            '/relatorios': 'relatorios',
            '/orcamento': 'orcamento',
            '/metas': 'metas',
            '/gamification': 'gamification',
            '/billing': 'billing',
            '/perfil': 'perfil',
        };

        for (const [fragment, page] of Object.entries(pageMap)) {
            if (path.includes(fragment)) {
                return page;
            }
        }

        return 'dashboard';
    }

    getCurrentConfig() {
        return TOUR_CONFIGS[this.currentPage] || null;
    }

    getCurrentVersion() {
        return this.getCurrentConfig()?.version || DEFAULT_VERSION;
    }

    getPageLabel(page = this.currentPage) {
        return PAGE_LABELS[page] || 'Esta tela';
    }

    hasTutorial(page = this.currentPage) {
        return Boolean(TOUR_CONFIGS[page]);
    }

    hasTips(page = this.currentPage) {
        return Boolean(window.FirstVisitTooltips?.hasTooltipsForPage?.(page));
    }

    getOfferSessionKey(page = this.currentPage) {
        return `${OFFER_SESSION_PREFIX}${page}_${this.getCurrentVersion()}`;
    }

    wasOfferShownThisSession(page = this.currentPage) {
        try {
            return sessionStorage.getItem(this.getOfferSessionKey(page)) === '1';
        } catch (_error) {
            return false;
        }
    }

    markOfferShownThisSession(page = this.currentPage) {
        try {
            sessionStorage.setItem(this.getOfferSessionKey(page), '1');
        } catch (_error) {
            // ignore sessionStorage failures
        }
    }

    isCompleted(page = this.currentPage) {
        return this.preferences.tour_completed?.[page] === this.getCurrentVersion();
    }

    isDismissed(page = this.currentPage) {
        return this.preferences.offer_dismissed?.[page] === this.getCurrentVersion();
    }

    shouldOffer() {
        if (!this.currentPage) {
            return false;
        }

        if (!this.preferences.settings.auto_offer) {
            return false;
        }

        if (!this.hasTutorial()) {
            return false;
        }

        if (this.isCompleted() || this.isDismissed()) {
            return false;
        }

        if (this.wasOfferShownThisSession()) {
            return false;
        }

        const availableSteps = this.buildSteps();
        return availableSteps.length > 1;
    }

    createOffer() {
        const offer = document.createElement('div');
        offer.className = 'lk-help-offer';
        offer.id = 'lkHelpOffer';
        offer.innerHTML = `
            <div class="lk-help-offer__card surface-card surface-card--clip">
                <div class="lk-help-offer__icon">
                    <i data-lucide="sparkles"></i>
                </div>
                <div class="lk-help-offer__content">
                    <span class="lk-help-offer__eyebrow">Tour opcional</span>
                    <strong>Quer um tour rapido desta tela?</strong>
                    <p>Em menos de 30 segundos eu te mostro onde agir primeiro, sem travar sua navegacao.</p>
                </div>
                <div class="lk-help-offer__actions">
                    <button type="button" class="lk-help-btn lk-help-btn--primary" data-help-offer="start">Ver agora</button>
                    <button type="button" class="lk-help-btn lk-help-btn--ghost" data-help-offer="tips">Ver dicas</button>
                    <button type="button" class="lk-help-btn lk-help-btn--subtle" data-help-offer="dismiss">Agora nao</button>
                </div>
            </div>
        `;

        document.body.appendChild(offer);
        this.offerElement = offer;

        offer.querySelector('[data-help-offer="start"]')?.addEventListener('click', () => {
            this.hideOffer();
            this.startCurrentPageTutorial({ source: 'offer' });
        });

        offer.querySelector('[data-help-offer="tips"]')?.addEventListener('click', async () => {
            await this.markDismissed();
            this.hideOffer();
            this.showCurrentPageTips();
        });

        offer.querySelector('[data-help-offer="dismiss"]')?.addEventListener('click', async () => {
            await this.markDismissed();
            this.hideOffer();
            this.renderMenuState();
            this.highlightPrimaryAction();
        });

        window.LK?.refreshIcons?.(offer);
    }

    bindMenu() {
        this.elements.helpToggle?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = this.elements.helpMenu?.hasAttribute('hidden') === false;
            this.toggleMenu(!isOpen);
        });

        this.elements.helpTourBtn?.addEventListener('click', () => {
            this.toggleMenu(false);
            this.startCurrentPageTutorial({ source: 'menu' });
        });

        this.elements.helpTipsBtn?.addEventListener('click', () => {
            this.toggleMenu(false);
            this.showCurrentPageTips();
        });

        this.elements.helpAutoOfferBtn?.addEventListener('click', async () => {
            const nextValue = !this.preferences.settings.auto_offer;

            this.preferences.settings.auto_offer = nextValue;
            this.renderMenuState();

            const success = await this.persistPreference('set_auto_offer', {
                value: nextValue,
            });

            if (!success) {
                this.preferences.settings.auto_offer = !nextValue;
                this.renderMenuState();
                return;
            }

            if (window.LK?.toast) {
                window.LK.toast.success(nextValue
                    ? 'Convites de tutorial reativados.'
                    : 'Convites automaticos pausados.');
            }
        });

        this.elements.helpResetBtn?.addEventListener('click', async () => {
            const confirmed = await (window.LK?.confirm
                ? window.LK.confirm({
                    title: 'Recomeçar tutoriais?',
                    text: 'Isso libera novamente tours e dicas das telas principais.',
                    confirmText: 'Recomeçar',
                    cancelText: 'Cancelar',
                })
                : Promise.resolve(window.confirm('Recomeçar tutoriais desta conta?')));

            if (!confirmed) {
                return;
            }

            const previousPreferences = JSON.parse(JSON.stringify(this.preferences));

            this.preferences.tour_completed = {};
            this.preferences.offer_dismissed = {};
            this.preferences.tips_seen = {};
            this.renderMenuState();

            const success = await this.persistPreference('reset_all');
            if (!success) {
                this.preferences = previousPreferences;
                this.renderMenuState();
                return;
            }

            window.FirstVisitTooltips?.resetVisitedPages?.();

            try {
                sessionStorage.removeItem(this.getOfferSessionKey());
            } catch (_error) {
                // ignore
            }

            if (window.LK?.toast) {
                window.LK.toast.success('Tutoriais liberados novamente.');
            }

            this.toggleMenu(false);
            this.scheduleOffer(true);
        });

        document.addEventListener('click', (event) => {
            if (!this.elements.helpMenu || !this.elements.helpToggle) {
                return;
            }

            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (this.elements.helpMenu.contains(target) || this.elements.helpToggle.contains(target)) {
                return;
            }

            this.toggleMenu(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.toggleMenu(false);
                this.hideOffer();
            }
        });
    }

    renderMenuState() {
        const label = this.getPageLabel();
        const tutorialAvailable = this.hasTutorial();
        const tipsAvailable = this.hasTips();

        if (this.elements.helpCurrentPage) {
            this.elements.helpCurrentPage.textContent = tutorialAvailable
                ? `Tutorial de ${label}`
                : `Ajuda de ${label}`;
        }

        if (this.elements.helpStatus) {
            let status = 'Disponivel';

            if (!tutorialAvailable && !tipsAvailable) {
                status = 'Sem guia';
            } else if (this.isCompleted()) {
                status = 'Concluido';
            } else if (!this.preferences.settings.auto_offer) {
                status = 'Manual';
            }

            this.elements.helpStatus.textContent = status;
        }

        if (this.elements.helpTourBtn) {
            this.elements.helpTourBtn.disabled = !tutorialAvailable;
            this.elements.helpTourBtn.classList.toggle('is-disabled', !tutorialAvailable);
        }

        if (this.elements.helpTipsBtn) {
            this.elements.helpTipsBtn.disabled = !tipsAvailable;
            this.elements.helpTipsBtn.classList.toggle('is-disabled', !tipsAvailable);
        }

        if (this.elements.helpAutoOfferBtn) {
            const icon = this.preferences.settings.auto_offer ? 'bell' : 'bell-off';
            const text = this.preferences.settings.auto_offer
                ? 'Desativar convite automatico'
                : 'Ativar convite automatico';

            this.elements.helpAutoOfferBtn.innerHTML = `
                <i data-lucide="${icon}"></i>
                <span>${text}</span>
            `;
        }

        window.LK?.refreshIcons?.(this.elements.helpMenu);
    }

    toggleMenu(shouldOpen) {
        if (!this.elements.helpMenu || !this.elements.helpToggle) {
            return;
        }

        if (shouldOpen) {
            this.elements.helpMenu.removeAttribute('hidden');
            this.elements.helpToggle.setAttribute('aria-expanded', 'true');
        } else {
            this.elements.helpMenu.setAttribute('hidden', 'hidden');
            this.elements.helpToggle.setAttribute('aria-expanded', 'false');
        }
    }

    scheduleOffer(force = false) {
        if (!this.hasTutorial()) {
            return;
        }

        if (this.buildSteps().length <= 1) {
            return;
        }

        if (!force && !this.shouldOffer()) {
            return;
        }

        window.setTimeout(() => {
            if (this.buildSteps().length <= 1) {
                return;
            }

            if (!force && !this.shouldOffer()) {
                return;
            }

            this.showOffer();
        }, OFFER_DELAY);
    }

    showOffer() {
        if (!this.offerElement || this.offerVisible) {
            return;
        }

        this.markOfferShownThisSession();
        this.offerVisible = true;
        this.offerElement.classList.add('is-visible');
    }

    hideOffer() {
        if (!this.offerElement) {
            return;
        }

        this.offerVisible = false;
        this.offerElement.classList.remove('is-visible');
    }

    buildSteps(page = this.currentPage) {
        const config = TOUR_CONFIGS[page];
        if (!config) {
            return [];
        }

        return config.steps.reduce((steps, step) => {
            if (step.selector === null) {
                steps.push({
                    popover: {
                        title: step.title,
                        description: step.description,
                        side: step.side || 'over',
                        align: step.align || 'center',
                    },
                });

                return steps;
            }

            const element = resolveElement(step.selector);
            if (!element) {
                return steps;
            }

            steps.push({
                element,
                popover: {
                    title: step.title,
                    description: step.description,
                    side: step.side || 'bottom',
                    align: step.align || 'start',
                },
            });

            return steps;
        }, []);
    }

    teardownTourRefreshGuards() {
        if (typeof this.tourRefreshCleanup === 'function') {
            this.tourRefreshCleanup();
        }

        this.tourRefreshCleanup = null;
    }

    clearTourTarget() {
        if (!(this.activeTourTarget instanceof HTMLElement)) {
            this.activeTourTarget = null;
            return;
        }

        this.activeTourTarget.removeAttribute('data-lk-help-tour-target');
        this.activeTourTarget = null;
    }

    setTourTarget(element) {
        if (!(element instanceof HTMLElement)) {
            this.clearTourTarget();
            return;
        }

        if (this.activeTourTarget === element) {
            return;
        }

        this.clearTourTarget();
        element.setAttribute('data-lk-help-tour-target', 'true');
        this.activeTourTarget = element;
    }

    createTourElements() {
        const overlay = document.createElement('div');
        overlay.className = 'lk-help-tour-overlay';

        const spotlight = document.createElement('div');
        spotlight.className = 'lk-help-tour-spotlight';
        spotlight.setAttribute('aria-hidden', 'true');

        const popover = document.createElement('div');
        popover.className = 'lk-help-tour-popover surface-card surface-card--clip';
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-modal', 'true');

        document.body.appendChild(overlay);
        document.body.appendChild(spotlight);
        document.body.appendChild(popover);

        return { overlay, spotlight, popover };
    }

    syncTourSpotlight(state) {
        if (!state?.spotlight) {
            return;
        }

        const step = state.steps[state.index];
        const target = resolveElement(step?.element);

        if (!(target instanceof HTMLElement)) {
            state.spotlight.removeAttribute('data-visible');
            state.spotlight.style.width = '0px';
            state.spotlight.style.height = '0px';
            return;
        }

        const rect = target.getBoundingClientRect();
        const styles = window.getComputedStyle(target);
        const padding = target.matches('#fabContainer, .fab-container, #fabMain, #fabButton, .fab-main, .nav-item')
            ? 10
            : 6;

        state.spotlight.setAttribute('data-visible', 'true');
        state.spotlight.style.top = `${Math.max(8, rect.top - padding)}px`;
        state.spotlight.style.left = `${Math.max(8, rect.left - padding)}px`;
        state.spotlight.style.width = `${Math.max(0, rect.width + (padding * 2))}px`;
        state.spotlight.style.height = `${Math.max(0, rect.height + (padding * 2))}px`;
        state.spotlight.style.borderRadius = styles.borderRadius || '18px';
    }

    getTourPopoverPosition(targetRect, popoverRect, options = {}) {
        const {
            side = 'bottom',
            align = 'center',
            offset = 18,
            viewportPadding = 16,
        } = options;

        if (!targetRect) {
            return {
                side: 'center',
                top: clamp((window.innerHeight - popoverRect.height) / 2, viewportPadding, Math.max(viewportPadding, window.innerHeight - popoverRect.height - viewportPadding)),
                left: clamp((window.innerWidth - popoverRect.width) / 2, viewportPadding, Math.max(viewportPadding, window.innerWidth - popoverRect.width - viewportPadding)),
            };
        }

        let top = viewportPadding;
        let left = viewportPadding;

        if (side === 'top' || side === 'bottom') {
            top = side === 'bottom'
                ? targetRect.bottom + offset
                : targetRect.top - popoverRect.height - offset;

            if (align === 'start') {
                left = targetRect.left;
            } else if (align === 'end') {
                left = targetRect.right - popoverRect.width;
            } else {
                left = targetRect.left + (targetRect.width / 2) - (popoverRect.width / 2);
            }
        } else if (side === 'left' || side === 'right') {
            left = side === 'right'
                ? targetRect.right + offset
                : targetRect.left - popoverRect.width - offset;

            if (align === 'start') {
                top = targetRect.top;
            } else if (align === 'end') {
                top = targetRect.bottom - popoverRect.height;
            } else {
                top = targetRect.top + (targetRect.height / 2) - (popoverRect.height / 2);
            }
        } else {
            top = (window.innerHeight - popoverRect.height) / 2;
            left = (window.innerWidth - popoverRect.width) / 2;
        }

        return {
            side,
            top,
            left,
        };
    }

    positionTourPopover(state) {
        if (!state?.popover) {
            return;
        }

        const step = state.steps[state.index];
        if (!step) {
            return;
        }

        const popover = state.popover;
        const target = resolveElement(step.element);
        const popoverRect = popover.getBoundingClientRect();
        const viewportPadding = 16;
        const preferredSide = step.popover?.side || 'bottom';
        const align = step.popover?.align || 'center';
        const candidateSides = target
            ? [preferredSide, getOppositeSide(preferredSide), 'bottom', 'top', 'right', 'left', 'center']
            : ['center'];

        const uniqueSides = [...new Set(candidateSides)];
        const targetRect = target?.getBoundingClientRect?.() || null;

        let chosenPosition = null;

        for (const side of uniqueSides) {
            const position = this.getTourPopoverPosition(targetRect, popoverRect, {
                side,
                align,
                viewportPadding,
            });

            const fitsVertically = position.top >= viewportPadding
                && (position.top + popoverRect.height) <= (window.innerHeight - viewportPadding);
            const fitsHorizontally = position.left >= viewportPadding
                && (position.left + popoverRect.width) <= (window.innerWidth - viewportPadding);

            if (fitsVertically && fitsHorizontally) {
                chosenPosition = position;
                break;
            }
        }

        if (!chosenPosition) {
            const fallback = this.getTourPopoverPosition(targetRect, popoverRect, {
                side: preferredSide,
                align,
                viewportPadding,
            });

            chosenPosition = {
                side: fallback.side,
                top: clamp(fallback.top, viewportPadding, Math.max(viewportPadding, window.innerHeight - popoverRect.height - viewportPadding)),
                left: clamp(fallback.left, viewportPadding, Math.max(viewportPadding, window.innerWidth - popoverRect.width - viewportPadding)),
            };
        }

        popover.style.top = `${chosenPosition.top}px`;
        popover.style.left = `${chosenPosition.left}px`;
        popover.dataset.side = chosenPosition.side;
        this.syncTourSpotlight(state);
    }

    renderTourPopover(state) {
        const step = state.steps[state.index];
        if (!step) {
            return;
        }

        const total = state.steps.length;
        const isFirst = state.index === 0;
        const isLast = state.index === total - 1;

        state.popover.innerHTML = `
            <div class="lk-help-tour-popover__progress">${state.index + 1} de ${total}</div>
            <h3 class="lk-help-tour-popover__title">${step.popover?.title || ''}</h3>
            <p class="lk-help-tour-popover__description">${step.popover?.description || ''}</p>
            <div class="lk-help-tour-popover__footer">
                <button type="button" class="lk-help-tour-popover__btn" data-tour-action="prev" ${isFirst ? 'disabled' : ''}>Voltar</button>
                <button type="button" class="lk-help-tour-popover__btn" data-tour-action="cancel">Cancelar</button>
                <button type="button" class="lk-help-tour-popover__btn lk-help-tour-popover__btn--primary" data-tour-action="next">
                    ${isLast ? 'Concluir' : 'Proximo'}
                </button>
            </div>
        `;

        state.popover.querySelector('[data-tour-action="prev"]')?.addEventListener('click', () => {
            this.goToTourStep(state.index - 1);
        });

        state.popover.querySelector('[data-tour-action="cancel"]')?.addEventListener('click', () => {
            void this.closeTour(state, { markDismissed: !this.completedCurrentRun });
        });

        state.popover.querySelector('[data-tour-action="next"]')?.addEventListener('click', () => {
            if (isLast) {
                this.completedCurrentRun = true;
                void this.closeTour(state, { markCompleted: true });
                return;
            }

            this.goToTourStep(state.index + 1);
        });
    }

    goToTourStep(index) {
        const state = this.tour;
        if (!state || !state.isActive()) {
            return;
        }

        const nextIndex = clamp(index, 0, state.steps.length - 1);
        state.index = nextIndex;

        const step = state.steps[nextIndex];
        const target = resolveElement(step?.element);
        if (target) {
            ensureElementInViewport(target, {
                topOffset: 88,
                bottomOffset: 28,
            });
        }

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                if (!state.isActive()) {
                    return;
                }

                const activeTarget = resolveElement(step?.element);
                this.setTourTarget(activeTarget);
                this.renderTourPopover(state);
                this.positionTourPopover(state);
            });
        });
    }

    async closeTour(state = this.tour, options = {}) {
        if (!state) {
            return;
        }

        const {
            silent = false,
            markCompleted = false,
            markDismissed = false,
        } = options;

        if (this.tour === state) {
            this.tour = null;
        }

        state.overlay?.remove();
        state.spotlight?.remove();
        state.popover?.remove();
        document.body.classList.remove('lk-help-tour-active');
        this.clearTourTarget();
        this.teardownTourRefreshGuards();

        window.removeEventListener('resize', state.repositionHandler);
        window.removeEventListener('scroll', state.repositionHandler);
        document.removeEventListener('keydown', state.keydownHandler);

        if (!silent) {
            if (markCompleted) {
                await this.markCompleted();
                this.highlightPrimaryAction(true);
            } else if (markDismissed) {
                await this.markDismissed();
            }

            this.renderMenuState();
        }
    }

    async startCurrentPageTutorial(_options = {}) {
        const steps = this.buildSteps();

        if (steps.length === 0) {
            window.LK?.toast?.info('Ainda nao existe tutorial pronto para esta tela.');
            return false;
        }

        if (this.tour?.isActive?.()) {
            await this.closeTour(this.tour, { silent: true });
        }

        this.hideOffer();
        this.toggleMenu(false);
        this.completedCurrentRun = false;
        document.body.classList.remove('lk-help-tour-active');

        const { overlay, spotlight, popover } = this.createTourElements();

        const state = {
            steps,
            index: 0,
            overlay,
            spotlight,
            popover,
            repositionHandler: () => {
                if (!state.isActive()) {
                    return;
                }

                this.positionTourPopover(state);
            },
            keydownHandler: (event) => {
                if (!state.isActive()) {
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    void this.closeTour(state, { markDismissed: !this.completedCurrentRun });
                    return;
                }

                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    this.goToTourStep(state.index + 1);
                    return;
                }

                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    this.goToTourStep(state.index - 1);
                }
            },
            isActive: () => this.tour === state,
        };

        overlay.addEventListener('click', (event) => {
            event.preventDefault();
        });

        overlay.addEventListener('wheel', (event) => {
            event.preventDefault();
        }, { passive: false });

        popover.addEventListener('wheel', (event) => {
            event.preventDefault();
        }, { passive: false });

        window.addEventListener('resize', state.repositionHandler);
        window.addEventListener('scroll', state.repositionHandler, { passive: true });
        document.addEventListener('keydown', state.keydownHandler);

        this.tour = state;
        document.body.classList.add('lk-help-tour-active');
        this.goToTourStep(0);

        return true;
    }

    async showCurrentPageTips() {
        if (!this.hasTips()) {
            window.LK?.toast?.info('Ainda nao existe dica rapida para esta tela.');
            return false;
        }

        this.hideOffer();
        this.toggleMenu(false);
        window.FirstVisitTooltips?.removeAllTooltips?.();
        window.FirstVisitTooltips?.showTooltipsForPage?.(this.currentPage);
        await this.markTipsSeen();
        this.highlightPrimaryAction();
        this.renderMenuState();

        return true;
    }

    highlightPrimaryAction(scrollIntoView = false) {
        const target = resolveElement(this.getCurrentConfig()?.primarySelector);
        if (!target) {
            return;
        }

        target.classList.add('lk-help-primary-highlight');
        window.setTimeout(() => {
            target.classList.remove('lk-help-primary-highlight');
        }, 7000);

        if (scrollIntoView) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    }

    async markCompleted() {
        const page = this.currentPage;
        const version = this.getCurrentVersion();

        this.preferences.tour_completed[page] = version;
        delete this.preferences.offer_dismissed[page];
        this.renderMenuState();

        await this.persistPreference('complete_tour', { page, version }, { silent: true });
    }

    async markDismissed() {
        const page = this.currentPage;
        const version = this.getCurrentVersion();

        this.preferences.offer_dismissed[page] = version;
        this.renderMenuState();

        await this.persistPreference('dismiss_offer', { page, version }, { silent: true });
    }

    async markTipsSeen() {
        const page = this.currentPage;
        const version = this.getCurrentVersion();

        this.preferences.tips_seen[page] = version;
        await this.persistPreference('view_tips', { page, version }, { silent: true });
    }

    async persistPreference(action, extra = {}, options = {}) {
        if (!window.LK?.api?.post) {
            return false;
        }

        const { silent = false } = options;

        const response = await window.LK.api.post('api/user/help-preferences', {
            action,
            ...extra,
        });

        if (!response?.ok) {
            if (!silent) {
                window.LK?.toast?.error(response?.message || 'Nao foi possivel salvar sua preferencia de ajuda.');
            }

            return false;
        }

        if (response.data?.preferences) {
            this.preferences = normalizeHelpPreferences(response.data.preferences);
            window.__LK_CONFIG.helpCenter = this.preferences;
        }

        return true;
    }

    isManagingAutoOffers() {
        return true;
    }
}

function bootHelpCenter() {
    if (window.LKHelpCenter) {
        return;
    }

    window.LKHelpCenter = new HelpCenter();
    window.LKHelpCenter.init();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootHelpCenter);
} else {
    bootHelpCenter();
}
