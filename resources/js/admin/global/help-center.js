import '../../../css/admin/modules/help-center.css';

window.__LK_HELP_CENTER_MANAGED = true;

const DEFAULT_VERSION = 'v2';
const NAVIGATION_VERSION = 'v1';
const MOBILE_VIEWPORT_MAX = 992;
const OFFER_DELAY = 1800;
const OFFER_SESSION_PREFIX = `lk_help_offer_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

const TUTORIAL_TYPES = {
    PAGE: 'page',
    NAVIGATION: 'navigation',
};

const TUTORIAL_VARIANTS = {
    DESKTOP: 'desktop',
    MOBILE: 'mobile',
};

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
                title: 'Resumo do mes',
                description: 'Aqui voce enxerga saldo, entradas e saidas sem trocar de tela.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.dash-kpis',
                title: 'Indicadores principais',
                description: 'Este bloco resume entradas, saidas e resultado do periodo.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#dashboardFirstTransactionCta', '#dashboardEmptyStateCta', '#fabContainer', '.fab-container', '#fabMain', '#fabButton'],
                title: 'Adicionar lancamento',
                description: 'Atalho principal para registrar sua proxima movimentacao.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#table-section',
                title: 'Ultimas transacoes',
                description: 'Acompanhe historico recente e abra itens para revisar.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#chart-section',
                title: 'Grafico por categoria',
                description: 'Use este bloco para identificar concentracao de gastos.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '#saldoCard',
                title: 'Resumo rapido',
                description: 'Saldo e variacao do mes em um unico card.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#dashboardFirstTransactionCta', '#dashboardEmptyStateCta', '#fabButton'],
                title: 'Novo lancamento',
                description: 'Registre uma entrada ou despesa por este atalho.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#table-section',
                title: 'Historico recente',
                description: 'Lista de transacoes para revisao rapida.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#btnCustomizeDashboard',
                title: 'Personalizar tela',
                description: 'Mostre apenas os blocos que importam para voce.',
                side: 'top',
                align: 'center',
            },
        ],
    },
    lancamentos: {
        label: 'Lancamentos',
        version: DEFAULT_VERSION,
        primarySelector: ['#btnNovoLancamento', '#fabButton'],
        steps: [
            {
                selector: '.lan-summary-strip',
                title: 'Resumo do periodo',
                description: 'Receitas, despesas e saldo do mes em leitura imediata.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#btnNovoLancamento', '#fabButton'],
                title: 'Novo lancamento',
                description: 'Use este atalho para entrada, despesa ou transferencia.',
                side: 'left',
                align: 'center',
            },
            {
                selector: '.lk-filters-section',
                title: 'Filtros principais',
                description: 'Refine por texto, tipo, categoria, conta e status.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#filtroDataInicio', '#filtroDataFim'],
                title: 'Periodo personalizado',
                description: 'Ajuste intervalo manual para consultas mais especificas.',
                side: 'top',
                align: 'start',
            },
            {
                selector: ['#lancamentosFeed', '.modern-table-wrapper'],
                title: 'Lista de lancamentos',
                description: 'Historico com acoes rapidas em cada item.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#exportCard',
                title: 'Exportacao',
                description: 'Exporte quando precisar compartilhar ou arquivar.',
                side: 'top',
                align: 'start',
            },
        ],
        mobileSteps: [
            {
                selector: '.lan-summary-strip',
                title: 'Resumo do periodo',
                description: 'Resumo do mes antes de entrar nos detalhes.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#btnNovoLancamento', '#fabButton'],
                title: 'Novo lancamento',
                description: 'Registre rapidamente sua movimentacao.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#btnToggleLanFilters',
                title: 'Abrir filtros',
                description: 'No mobile, os filtros ficam recolhidos por padrao.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#lancamentosFeed',
                title: 'Feed de transacoes',
                description: 'Role para revisar e editar cada registro.',
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
                title: 'Visao consolidada',
                description: 'Veja seu total e a concentracao do dinheiro.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.cont-kpis',
                title: 'Indicadores de contas',
                description: 'Conta principal e reserva acumulada em um bloco.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#contasDistributionCard',
                title: 'Distribuicao',
                description: 'Entenda em quais contas o saldo esta distribuido.',
                side: 'top',
                align: 'start',
            },
            {
                selector: '#btnNovaConta',
                title: 'Nova conta',
                description: 'Cadastre banco, carteira ou reserva para centralizar saldos.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.contas-toolbar',
                title: 'Busca e filtros',
                description: 'Filtre por nome e tipo para achar contas rapidamente.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#accountsGrid',
                title: 'Cards das contas',
                description: 'A grade mostra saldo e atalhos para editar cada conta.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '#contasHero',
                title: 'Resumo de contas',
                description: 'Total consolidado da sua base de contas.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btnNovaConta',
                title: 'Criar conta',
                description: 'Cadastre uma nova conta por aqui.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#viewToggle',
                title: 'Trocar visualizacao',
                description: 'Alterne entre cards e lista.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#accountsGrid',
                title: 'Suas contas',
                description: 'Acompanhe saldo e acessos rapidos.',
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
                selector: ['.cart-kpis', '.quick-stats-grid', '.cart-summary-grid'],
                title: 'Resumo dos cartoes',
                description: 'Acompanhe limite usado, disponivel e alertas.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#btnNovoCartao', '#btnNovoCartaoEmpty'],
                title: 'Adicionar cartao',
                description: 'Cadastre cartoes para controlar compras e faturas.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.cartoes-toolbar',
                title: 'Barra de controle',
                description: 'Busca, filtros e ajustes de visualizacao.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#searchCartoes', '.cart-search-wrapper'],
                title: 'Busca rapida',
                description: 'Encontre um cartao por nome ou bandeira.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#cartoesGrid', '.cartoes-grid'],
                title: 'Cards de cartao',
                description: 'Cada card mostra limite, fechamento e atalhos.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '.view-toggle',
                title: 'Modo de visualizacao',
                description: 'Troque layout conforme sua preferencia.',
                side: 'left',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: ['.cart-kpis', '.quick-stats-grid', '.cart-summary-grid'],
                title: 'Resumo principal',
                description: 'Panorama dos limites antes dos cards.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#btnNovoCartao', '#btnNovoCartaoEmpty'],
                title: 'Novo cartao',
                description: 'Cadastre seu cartao por este botao.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#searchCartoes', '.cart-search-wrapper'],
                title: 'Buscar cartao',
                description: 'Filtro rapido para localizar o cartao certo.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#cartoesGrid', '.cartoes-grid'],
                title: 'Lista de cartoes',
                description: 'Role a lista para abrir detalhes e atalhos.',
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
                selector: '.filters-modern',
                title: 'Filtros da fatura',
                description: 'Defina status, cartao e periodo antes de analisar itens.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#btnFiltrar',
                title: 'Aplicar filtros',
                description: 'Atualize a lista com os parametros escolhidos.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.view-toggle',
                title: 'Trocar visualizacao',
                description: 'Alterne entre cards e lista.',
                side: 'left',
                align: 'center',
            },
            {
                selector: '#faturasListHeader',
                title: 'Cabecalho da listagem',
                description: 'Resumo rapido da visualizacao ativa.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#parcelamentosContainer',
                title: 'Itens da fatura',
                description: 'Parcelas, status e acoes disponiveis.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '.filters-modern',
                title: 'Contexto do periodo',
                description: 'Escolha filtros para reduzir ruido.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btnFiltrar',
                title: 'Atualizar listagem',
                description: 'Recarregue resultados apos ajustar filtros.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.view-toggle',
                title: 'Cards ou lista',
                description: 'Troque o modo de leitura com um toque.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#parcelamentosContainer',
                title: 'Suas faturas',
                description: 'Role a listagem para revisar e pagar itens.',
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
                selector: '.cat-kpis',
                title: 'Painel de categorias',
                description: 'Resumo de categorias, subcategorias e cobertura de orcamento.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['.create-card', '#formNova'],
                title: 'Criar categoria',
                description: 'Cadastre categorias para melhorar relatorios e filtros.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#catContextCard',
                title: 'Busca e contexto',
                description: 'Use busca para localizar categorias e manter estrutura limpa.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.receitas-card',
                title: 'Grupo de receitas',
                description: 'Organize entradas por origem para analise mais clara.',
                side: 'right',
                align: 'center',
            },
            {
                selector: '.despesas-card',
                title: 'Grupo de despesas',
                description: 'Organize gastos por contexto para comparar comportamento.',
                side: 'left',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: ['.create-card', '#formNova'],
                title: 'Criar categoria',
                description: 'Formulario principal para novas categorias.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#catContextCard',
                title: 'Busca de categorias',
                description: 'Filtre por nome para achar itens rapidamente.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.receitas-card',
                title: 'Receitas',
                description: 'Categorias de entradas financeiras.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '.despesas-card',
                title: 'Despesas',
                description: 'Categorias de gastos para controle diario.',
                side: 'top',
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
                title: 'Resumo do periodo',
                description: 'As metricas principais mostram contexto antes do detalhe.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.rel-section-tabs',
                title: 'Secoes do modulo',
                description: 'Navegue entre visao geral, relatorios e comparativos.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.tabs-card',
                title: 'Modelos de analise',
                description: 'Escolha categoria, saldo diario, contas, cartoes ou anual.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#exportControl',
                title: 'Exportacao',
                description: 'Exporte quando precisar compartilhar ou arquivar.',
                side: 'left',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '.quick-stats-grid',
                title: 'Resumo do mes',
                description: 'Comece pelas metricas principais.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.rel-section-tabs',
                title: 'Trocar secao',
                description: 'Use as abas para abrir a visao desejada.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.tabs-card',
                title: 'Tipo de relatorio',
                description: 'Selecione o modelo de analise.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#exportControl',
                title: 'Exportar',
                description: 'Baixe o relatorio no formato desejado.',
                side: 'top',
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
                title: 'Resumo do mes',
                description: 'Veja rapidamente onde o limite esta sob controle.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#btnAutoSugerir', '#btnAutoSugerirEmpty'],
                title: 'Sugestao automatica',
                description: 'Gere limites sugeridos com base no historico.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btnNovoOrcamento',
                title: 'Novo limite',
                description: 'Crie um limite manual para categoria especifica.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#orcamentosGrid', '.orc-grid', '.fin-grid'],
                title: 'Cards de acompanhamento',
                description: 'Cada card mostra gasto atual, folga e alertas.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '#summaryOrcamentos',
                title: 'Resumo do mes',
                description: 'Panorama rapido dos limites no periodo.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#btnAutoSugerir', '#btnAutoSugerirEmpty'],
                title: 'Sugerir limites',
                description: 'Crie estrutura inicial sem montar tudo manualmente.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btnNovoOrcamento',
                title: 'Novo orcamento',
                description: 'Cadastre limite manual por categoria.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#orcamentosGrid', '.orc-grid', '.fin-grid'],
                title: 'Seus limites',
                description: 'Role os cards para acompanhar consumo.',
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
                description: 'Defina valor e prazo para transformar objetivo em plano.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#summaryMetas', '.met-summary-grid'],
                title: 'Resumo das metas',
                description: 'Acompanhe total acumulado, metas ativas e progresso geral.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#metFocusPanel',
                title: 'Foco do momento',
                description: 'O sistema sugere proximo passo e prioridade da vez.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.met-actions-bar',
                title: 'Acoes rapidas',
                description: 'Crie metas ou use templates para acelerar configuracao.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '.met-toolbar',
                title: 'Busca e filtros',
                description: 'Use busca, chips e ordenacao para achar metas relevantes.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['#metasGrid', '.met-grid'],
                title: 'Cards das metas',
                description: 'Cada card mostra valor atual, percentual e valor restante.',
                side: 'top',
                align: 'center',
            },
            {
                selector: '#metInsightsSection',
                title: 'Insights',
                description: 'Quando houver sinal de risco ou oportunidade, aparece aqui.',
                side: 'top',
                align: 'start',
            },
        ],
        mobileSteps: [
            {
                selector: ['#btnNovaMetaHeader', '#btnNovaMeta', '#btnNovaMetaEmpty'],
                title: 'Criar meta',
                description: 'Botao principal para comecar uma nova meta.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#summaryMetas', '.met-summary-grid'],
                title: 'Resumo geral',
                description: 'Veja progresso e total acumulado do periodo.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.met-toolbar',
                title: 'Filtrar metas',
                description: 'Refine por busca, status e ordenacao.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['#metasGrid', '.met-grid'],
                title: 'Lista de metas',
                description: 'Role os cards para revisar e agir.',
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
                title: 'Resumo de progresso',
                description: 'Veja nivel, pontos e sequencia ativa.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#missionsSection',
                title: 'Missoes do dia',
                description: 'Acoes curtas para manter consistencia.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['.achievements-section', '.achievement-card'],
                title: 'Conquistas',
                description: 'Historico de marcos importantes da sua jornada.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: ['.stats-grid', '.gamification-stats'],
                title: 'Seu nivel atual',
                description: 'Resumo de pontuacao e sequencia.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#missionsSection',
                title: 'Missoes',
                description: 'Complete tarefas para subir de nivel.',
                side: 'top',
                align: 'center',
            },
            {
                selector: ['.achievements-section', '.achievement-card'],
                title: 'Conquistas',
                description: 'Consulte badges e progresso acumulado.',
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
                title: 'Comparacao de planos',
                description: 'Contexto inicial do que muda entre Free e Pro.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: ['.plan-billing-toggle', '.billing-cycle-toggle', '.cycle-toggle'],
                title: 'Ciclo de cobranca',
                description: 'Troque periodicidade para comparar custo total.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.plans-grid',
                title: 'Cards dos planos',
                description: 'Compare recursos, limites e acao principal.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '.billing-header',
                title: 'Escolha seu plano',
                description: 'Visao geral da comparacao de planos.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: ['.plan-billing-toggle', '.billing-cycle-toggle', '.cycle-toggle'],
                title: 'Trocar ciclo',
                description: 'Alterne mensal, semestral e anual.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.plans-grid',
                title: 'Comparar planos',
                description: 'Role os cards e escolha a melhor opcao.',
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
                description: 'Visao geral da sua conta e dados principais.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#avatarEditBtn',
                title: 'Foto e identidade',
                description: 'Atualize foto para deixar o painel mais pessoal.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.profile-tabs',
                title: 'Secoes do perfil',
                description: 'Troque entre dados, seguranca e preferencias.',
                side: 'bottom',
                align: 'start',
            },
            {
                selector: '#btn-save-dados',
                title: 'Salvar ajustes',
                description: 'Depois de editar, salve para aplicar as mudancas.',
                side: 'top',
                align: 'center',
            },
        ],
        mobileSteps: [
            {
                selector: '.profile-header',
                title: 'Identidade da conta',
                description: 'Resumo da sua conta no topo da pagina.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#avatarEditBtn',
                title: 'Editar foto',
                description: 'Atualize avatar com um toque.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '.profile-tabs',
                title: 'Navegar no perfil',
                description: 'Use abas para trocar de secao.',
                side: 'bottom',
                align: 'center',
            },
            {
                selector: '#btn-save-dados',
                title: 'Salvar',
                description: 'Confirme mudancas no botao de salvar.',
                side: 'top',
                align: 'center',
            },
        ],
    },
};

const NAVIGATION_TOUR_CONFIG = {
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
            description: 'No desktop, este botao alterna o tamanho da barra lateral.',
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
            description: 'Orcamento e metas ajudam a manter foco.',
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
            description: 'No mobile, toque aqui para abrir a navegacao.',
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
            description: 'Atalho para registrar movimentacoes do dia.',
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

function resolveElementCandidate(target) {
    if (!target) {
        return null;
    }

    if (typeof target === 'function') {
        return resolveElementCandidate(target());
    }

    if (Array.isArray(target)) {
        for (const item of target) {
            const resolved = resolveElementCandidate(item);
            if (resolved) {
                return resolved;
            }
        }

        return null;
    }

    if (target instanceof HTMLElement) {
        return target;
    }

    if (typeof target === 'string') {
        return document.querySelector(target);
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
            helpNavigationTourBtn: document.getElementById('topNavHelpNavigationTourBtn'),
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

    getPageLabel(page = this.currentPage) {
        return PAGE_LABELS[page] || 'Esta tela';
    }

    isMobileViewport() {
        return window.matchMedia(`(max-width: ${MOBILE_VIEWPORT_MAX}px)`).matches;
    }

    getViewportVariant() {
        return this.isMobileViewport() ? TUTORIAL_VARIANTS.MOBILE : TUTORIAL_VARIANTS.DESKTOP;
    }

    getPageTourConfig(page = this.currentPage) {
        return TOUR_CONFIGS[page] || null;
    }

    getPageTutorialTarget(page = this.currentPage) {
        const config = this.getPageTourConfig(page);
        if (!config) {
            return null;
        }

        const variant = this.getViewportVariant();
        return {
            type: TUTORIAL_TYPES.PAGE,
            page,
            variant,
            key: `${page}.${variant}`,
            baseKey: page,
            label: config.label || this.getPageLabel(page),
            version: config.version || DEFAULT_VERSION,
            config,
        };
    }

    getNavigationTutorialTarget() {
        if (!NAVIGATION_TOUR_CONFIG) {
            return null;
        }

        const variant = this.getViewportVariant();
        return {
            type: TUTORIAL_TYPES.NAVIGATION,
            page: 'navigation',
            variant,
            key: `navigation.${variant}`,
            baseKey: 'navigation',
            label: NAVIGATION_TOUR_CONFIG.label || 'Navegacao',
            version: NAVIGATION_TOUR_CONFIG.version || NAVIGATION_VERSION,
            config: NAVIGATION_TOUR_CONFIG,
        };
    }

    getCurrentConfig() {
        return this.getPageTourConfig(this.currentPage);
    }

    getCurrentVersion() {
        return this.getPageTutorialTarget()?.version || DEFAULT_VERSION;
    }

    hasTutorial(page = this.currentPage) {
        return Boolean(this.getPageTourConfig(page));
    }

    hasNavigationTutorial() {
        return Boolean(NAVIGATION_TOUR_CONFIG);
    }

    hasTips(page = this.currentPage) {
        return Boolean(window.FirstVisitTooltips?.hasTooltipsForPage?.(page));
    }

    getOfferSessionKey(target = this.getPageTutorialTarget()) {
        if (!target) {
            return `${OFFER_SESSION_PREFIX}${this.currentPage}_${DEFAULT_VERSION}`;
        }

        return `${OFFER_SESSION_PREFIX}${target.key}_${target.version}`;
    }

    wasOfferShownThisSession(target = this.getPageTutorialTarget()) {
        try {
            return sessionStorage.getItem(this.getOfferSessionKey(target)) === '1';
        } catch (_error) {
            return false;
        }
    }

    markOfferShownThisSession(target = this.getPageTutorialTarget()) {
        try {
            sessionStorage.setItem(this.getOfferSessionKey(target), '1');
        } catch (_error) {
            // ignore sessionStorage failures
        }
    }

    clearOfferSessionCache() {
        try {
            for (let i = sessionStorage.length - 1; i >= 0; i -= 1) {
                const key = sessionStorage.key(i);
                if (key?.startsWith(OFFER_SESSION_PREFIX)) {
                    sessionStorage.removeItem(key);
                }
            }
        } catch (_error) {
            // ignore sessionStorage failures
        }
    }

    isCompleted(target = this.getPageTutorialTarget()) {
        if (!target) {
            return false;
        }

        const completed = this.preferences.tour_completed || {};
        return completed[target.key] === target.version
            || completed[target.baseKey] === target.version;
    }

    isDismissed(target = this.getPageTutorialTarget()) {
        if (!target) {
            return false;
        }

        const dismissed = this.preferences.offer_dismissed || {};
        return dismissed[target.key] === target.version
            || dismissed[target.baseKey] === target.version;
    }

    shouldOffer() {
        const target = this.getPageTutorialTarget();
        if (!target) {
            return false;
        }

        if (!this.preferences.settings.auto_offer) {
            return false;
        }

        if (this.isCompleted(target) || this.isDismissed(target)) {
            return false;
        }

        if (this.wasOfferShownThisSession(target)) {
            return false;
        }

        const availableSteps = this.buildSteps(target);
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
            await this.markDismissed(this.getPageTutorialTarget());
            this.hideOffer();
            this.showCurrentPageTips();
        });

        offer.querySelector('[data-help-offer="dismiss"]')?.addEventListener('click', async () => {
            await this.markDismissed(this.getPageTutorialTarget());
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

        this.elements.helpNavigationTourBtn?.addEventListener('click', () => {
            this.toggleMenu(false);
            this.startNavigationTutorial({ source: 'menu' });
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

            this.clearOfferSessionCache();

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
        const pageTarget = this.getPageTutorialTarget();
        const navigationTarget = this.getNavigationTutorialTarget();
        const label = this.getPageLabel();
        const tutorialAvailable = Boolean(pageTarget);
        const navigationTutorialAvailable = Boolean(navigationTarget);
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
            } else if (this.isCompleted(pageTarget)) {
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

        if (this.elements.helpNavigationTourBtn) {
            this.elements.helpNavigationTourBtn.disabled = !navigationTutorialAvailable;
            this.elements.helpNavigationTourBtn.classList.toggle('is-disabled', !navigationTutorialAvailable);
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
        const target = this.getPageTutorialTarget();
        if (!target) {
            return;
        }

        if (this.buildSteps(target).length <= 1) {
            return;
        }

        if (!force && !this.shouldOffer()) {
            return;
        }

        window.setTimeout(() => {
            if (this.buildSteps(target).length <= 1) {
                return;
            }

            if (!force && !this.shouldOffer()) {
                return;
            }

            this.showOffer(target);
        }, OFFER_DELAY);
    }

    showOffer(target = this.getPageTutorialTarget()) {
        if (!this.offerElement || this.offerVisible) {
            return;
        }

        this.markOfferShownThisSession(target);
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

    buildSteps(target = this.getPageTutorialTarget()) {
        if (!target?.config) {
            return [];
        }

        const config = target.config;
        const sourceSteps = target.variant === TUTORIAL_VARIANTS.MOBILE && Array.isArray(config.mobileSteps)
            ? config.mobileSteps
            : config.steps;

        if (!Array.isArray(sourceSteps)) {
            return [];
        }

        return sourceSteps.reduce((steps, step) => {
            if (step.selector === null) {
                steps.push({
                    popover: {
                        title: step.title,
                        description: step.description,
                        side: step.side || 'over',
                        align: step.align || 'center',
                    },
                    ensureSidebarOpen: step.ensureSidebarOpen === true,
                    ensureSidebarClosed: step.ensureSidebarClosed === true,
                });

                return steps;
            }

            const shouldDeferElementResolution = target.type === TUTORIAL_TYPES.NAVIGATION
                && target.variant === TUTORIAL_VARIANTS.MOBILE
                && (step.ensureSidebarOpen === true || step.ensureSidebarClosed === true);

            let element = null;

            if (shouldDeferElementResolution) {
                const candidate = resolveElementCandidate(step.selector);
                if (!candidate) {
                    return steps;
                }

                element = step.selector;
            } else {
                element = resolveElement(step.selector);
                if (!element) {
                    return steps;
                }
            }

            steps.push({
                element,
                popover: {
                    title: step.title,
                    description: step.description,
                    side: step.side || 'bottom',
                    align: step.align || 'start',
                },
                ensureSidebarOpen: step.ensureSidebarOpen === true,
                ensureSidebarClosed: step.ensureSidebarClosed === true,
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

    openMobileSidebarIfNeeded() {
        if (!this.isMobileViewport()) {
            return;
        }

        if (document.body.classList.contains('sidebar-open-mobile')) {
            return;
        }

        const button = document.getElementById('mobileMenuBtn');
        if (button) {
            button.click();
            return;
        }

        document.body.classList.add('sidebar-open-mobile');
    }

    closeMobileSidebarIfNeeded() {
        if (!this.isMobileViewport()) {
            return;
        }

        if (!document.body.classList.contains('sidebar-open-mobile')) {
            return;
        }

        const button = document.getElementById('mobileMenuBtn');
        if (button) {
            button.click();
            return;
        }

        document.body.classList.remove('sidebar-open-mobile');
    }

    syncNavigationUIForStep(state, step) {
        if (state?.target?.type !== TUTORIAL_TYPES.NAVIGATION) {
            return;
        }

        if (state.target.variant !== TUTORIAL_VARIANTS.MOBILE) {
            return;
        }

        if (step?.ensureSidebarClosed) {
            this.closeMobileSidebarIfNeeded();
        }

        if (step?.ensureSidebarOpen) {
            this.openMobileSidebarIfNeeded();
        }
    }

    goToTourStep(index) {
        const state = this.tour;
        if (!state || !state.isActive()) {
            return;
        }

        const nextIndex = clamp(index, 0, state.steps.length - 1);
        state.index = nextIndex;

        const step = state.steps[nextIndex];
        this.syncNavigationUIForStep(state, step);
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

        if (state.target?.type === TUTORIAL_TYPES.NAVIGATION
            && state.target.variant === TUTORIAL_VARIANTS.MOBILE) {
            this.closeMobileSidebarIfNeeded();
        }

        if (!silent) {
            if (markCompleted) {
                await this.markCompleted(state.target);
                if (state.target?.type === TUTORIAL_TYPES.PAGE) {
                    this.highlightPrimaryAction(true);
                }
            } else if (markDismissed) {
                await this.markDismissed(state.target);
            }

            this.renderMenuState();
        }
    }

    async startTutorial(target) {
        const steps = this.buildSteps(target);

        if (steps.length === 0) {
            window.LK?.toast?.info('Ainda nao existe tutorial pronto para este fluxo.');
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
            target,
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

    async startCurrentPageTutorial(_options = {}) {
        const target = this.getPageTutorialTarget();
        if (!target) {
            window.LK?.toast?.info('Ainda nao existe tutorial pronto para esta tela.');
            return false;
        }

        return this.startTutorial(target);
    }

    async startNavigationTutorial(_options = {}) {
        const target = this.getNavigationTutorialTarget();
        if (!target) {
            window.LK?.toast?.info('Ainda nao existe tutorial de navegacao.');
            return false;
        }

        return this.startTutorial(target);
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
        const pageTarget = this.getPageTutorialTarget();
        const target = resolveElement(pageTarget?.config?.primarySelector);
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

    async markCompleted(target = this.getPageTutorialTarget()) {
        if (!target) {
            return;
        }

        this.preferences.tour_completed[target.key] = target.version;
        delete this.preferences.offer_dismissed[target.key];
        this.renderMenuState();

        await this.persistPreference('complete_tour', {
            page: target.key,
            version: target.version,
        }, { silent: true });
    }

    async markDismissed(target = this.getPageTutorialTarget()) {
        if (!target) {
            return;
        }

        this.preferences.offer_dismissed[target.key] = target.version;
        this.renderMenuState();

        await this.persistPreference('dismiss_offer', {
            page: target.key,
            version: target.version,
        }, { silent: true });
    }

    async markTipsSeen() {
        const pageTarget = this.getPageTutorialTarget();
        if (!pageTarget) {
            return;
        }

        this.preferences.tips_seen[pageTarget.baseKey] = pageTarget.version;
        await this.persistPreference('view_tips', {
            page: pageTarget.baseKey,
            version: pageTarget.version,
        }, { silent: true });
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

