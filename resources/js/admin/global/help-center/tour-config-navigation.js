import { NAVIGATION_VERSION } from './tour-shared.js';

export const NAVIGATION_TOUR_CONFIG = {
    label: 'Navegacao',
    version: NAVIGATION_VERSION,
    primarySelector: {
        desktop: ['#edgeMenuBtn', '.sidebar .nav-item[href*="dashboard"]'],
        mobile: ['#mobileMenuBtn'],
    },
    steps: [
        {
            selector: '#edgeMenuBtn',
            title: 'Expandir ou recolher menu',
            description: 'No desktop, este botão alterna o tamanho da barra lateral.',
            side: 'right',
            align: 'center',
        },
        {
            selector: '.sidebar .nav-item[href*="dashboard"]',
            title: 'Inicio rapido',
            description: 'Dashboard mostra panorama financeiro geral.',
            side: 'right',
            align: 'center',
        },
        {
            selector: '.sidebar .nav-item[href*="lancamentos"]',
            title: 'Fluxo diario',
            description: 'Lancamentos e a tela principal para registros.',
            side: 'right',
            align: 'center',
        },
        {
            selector: ['.sidebar .nav-item[href*="orcamento"]', '.sidebar .nav-item[href*="metas"]'],
            title: 'Planejamento',
            description: 'Orçamento e metas ajudam a manter foco.',
            side: 'right',
            align: 'center',
        },
        {
            selector: '.sidebar .nav-item[href*="relatorios"]',
            title: 'Analise',
            description: 'Relatorios mostram padroes para decisoes.',
            side: 'right',
            align: 'center',
        },
        {
            selector: ['.sidebar-footer .nav-item[href*="perfil"]', '#sidebarSuggestionBtn'],
            title: 'Acoes finais',
            description: 'No rodape voce encontra perfil e feedback.',
            side: 'right',
            align: 'center',
        },
    ],
    mobileSteps: [
        {
            selector: '#mobileMenuBtn',
            title: 'Abrir menu',
            description: 'No mobile, toque aqui para abrir a navegação.',
            side: 'bottom',
            align: 'center',
            ensureSidebarClosed: true,
        },
        {
            selector: '.sidebar',
            title: 'Menu lateral',
            description: 'Com o menu aberto, voce acessa todas as telas.',
            side: 'right',
            align: 'center',
            ensureSidebarOpen: true,
        },
        {
            selector: '.sidebar .nav-item[href*="lancamentos"]',
            title: 'Lancamentos',
            description: 'Atalho para registrar movimentações do dia.',
            side: 'right',
            align: 'center',
            ensureSidebarOpen: true,
        },
        {
            selector: '.sidebar .nav-item[href*="relatorios"]',
            title: 'Relatorios',
            description: 'Acesse analises e visoes comparativas.',
            side: 'right',
            align: 'center',
            ensureSidebarOpen: true,
        },
        {
            selector: ['.sidebar .sidebar-close-btn', '#sidebarBackdrop'],
            title: 'Fechar menu',
            description: 'Toque no X ou fora do menu para voltar ao conteudo.',
            side: 'right',
            align: 'center',
            ensureSidebarOpen: true,
        },
    ],
};

