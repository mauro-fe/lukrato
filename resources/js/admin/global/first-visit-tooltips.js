/**
 * ============================================
 * FIRST VISIT TOOLTIPS - Lukrato
 * ============================================
 * Sistema de tooltips contextuais que aparecem
 * apenas na primeira visita a cada tela.
 * 
 * Ajuda usuários novos a entenderem funcionalidades
 * sem ser intrusivo para usuários experientes.
 */

(function () {
    'use strict';

    // ============================================
    // CONFIGURAÇÃO
    // ============================================

    const CONFIG = {
        storageKey: 'lukrato_visited_pages',
        tooltipDuration: 8000, // 8 segundos
        animationDuration: 300,
        maxTooltipsPerPage: 3,
    };

    // ============================================
    // DEFINIÇÕES DE TOOLTIPS POR PÁGINA
    // ============================================

    const PAGE_TOOLTIPS = {
        'dashboard': [
            {
                selector: '.streak-card',
                title: 'Dias Ativos',
                message: 'Mantenha seu streak! Acesse o sistema todos os dias para ganhar mais pontos e manter sua sequência.',
                position: 'bottom',
                icon: 'flame'
            },
            {
                selector: '.level-progress-card',
                title: 'Seu Nível',
                message: 'Ganhe pontos registrando lançamentos e organizando suas finanças. Cada nível desbloqueia novas conquistas!',
                position: 'bottom',
                icon: 'star'
            },
            {
                selector: '.stats-row, .stat-mini',
                title: 'Resumo Rápido',
                message: 'Veja seu progresso: total de lançamentos, categorias usadas, meses ativos e pontos acumulados.',
                position: 'top',
                icon: 'bar-chart'
            }
        ],
        'lancamentos': [
            {
                selector: '.filter-card',
                title: 'Filtros Avançados',
                message: 'Use os filtros para encontrar lançamentos por tipo (receita/despesa), categoria ou conta específica.',
                position: 'bottom',
                icon: 'filter'
            },
            {
                selector: '.export-card',
                title: 'Exportar Dados',
                message: 'Exporte seus lançamentos em PDF ou Excel para análises externas ou backup. Recurso disponível no plano Pro.',
                position: 'bottom',
                icon: 'download',
                proOnly: true
            },
            {
                selector: '#tableContainer, .lk-transactions-table',
                title: 'Seus Lançamentos',
                message: 'Clique em qualquer lançamento para editar ou excluir. Use as colunas para ordenar por data, valor ou tipo.',
                position: 'top',
                icon: 'list'
            }
        ],
        'cartoes': [
            {
                selector: '#btnNovoCartao',
                title: 'Adicionar Cartão',
                message: 'Cadastre seus cartões de crédito para acompanhar limites, faturas e gastos de forma automática.',
                position: 'bottom',
                icon: 'plus'
            },
            {
                selector: '.stats-grid .stat-card[data-stat="disponivel"]',
                title: 'Limite Disponível',
                message: 'Acompanhe quanto ainda pode gastar nos seus cartões. O valor é atualizado conforme você registra compras.',
                position: 'bottom',
                icon: 'circle-check'
            },
            {
                selector: '.cartoes-toolbar .search-box',
                title: 'Busca Rápida',
                message: 'Encontre rapidamente um cartão específico digitando o nome ou bandeira.',
                position: 'bottom',
                icon: 'search'
            }
        ],
        'faturas': [
            {
                selector: '.fatura-header, .fatura-card-header',
                title: 'Detalhes da Fatura',
                message: 'Veja o valor total, data de vencimento e status de pagamento da sua fatura.',
                position: 'bottom',
                icon: 'receipt'
            },
            {
                selector: '.btn-pagar, [data-action="pagar"]',
                title: 'Pagar Fatura',
                message: 'Ao pagar a fatura, um lançamento de despesa será criado automaticamente na conta selecionada.',
                position: 'left',
                icon: 'banknote'
            }
        ],
        'relatorios': [
            {
                selector: '.quick-stats-grid, .stat-card',
                title: 'Resumo do Mês',
                message: 'Visualize rapidamente receitas, despesas e saldo do período selecionado.',
                position: 'bottom',
                icon: 'pie-chart'
            },
            {
                selector: '.insights-card',
                title: 'Insights Inteligentes',
                message: 'Análise automática dos seus dados financeiros com dicas personalizadas.',
                position: 'bottom',
                icon: 'lightbulb'
            },
            {
                selector: '.export-card',
                title: 'Exportar Relatório',
                message: 'Baixe seus relatórios em PDF ou Excel. Recurso exclusivo do plano Pro.',
                position: 'left',
                icon: 'file-output',
                proOnly: true
            }
        ],
        'contas': [
            {
                selector: '#btnNovaConta',
                title: 'Nova Conta',
                message: 'Cadastre suas contas bancárias, carteiras ou qualquer local onde guarda dinheiro.',
                position: 'bottom',
                icon: 'plus'
            },
            {
                selector: '.stat-card',
                title: 'Saldo Total',
                message: 'O saldo é calculado automaticamente com base nos lançamentos de cada conta.',
                position: 'bottom',
                icon: 'coins'
            },
            {
                selector: '#viewToggle',
                title: 'Modo de Visualização',
                message: 'Alterne entre visualização em cards ou lista conforme sua preferência.',
                position: 'left',
                icon: 'layout-grid'
            }
        ],
        'categorias': [
            {
                selector: '.create-card, #formNova',
                title: 'Criar Categoria',
                message: 'Crie categorias personalizadas para organizar seus gastos e receitas da forma que fizer mais sentido para você.',
                position: 'bottom',
                icon: 'circle-plus'
            },
            {
                selector: '.receitas-card',
                title: 'Categorias de Receita',
                message: 'Aqui ficam suas categorias de entrada: salário, freelance, vendas, reembolsos e similares.',
                position: 'right',
                icon: 'arrow-up'
            },
            {
                selector: '.despesas-card',
                title: 'Categorias de Despesa',
                message: 'Organize seus gastos: alimentação, transporte, lazer, contas fixas, etc.',
                position: 'left',
                icon: 'arrow-down'
            }
        ],
        'agendamentos': [
            {
                selector: '.agendamentos-list, .agenda-grid',
                title: 'Transações Agendadas',
                message: 'Configure lançamentos recorrentes (salário, aluguel, streaming) para serem registrados automaticamente.',
                position: 'top',
                icon: 'clock'
            },
            {
                selector: '.btn-novo-agendamento, #btnNovoAgendamento',
                title: 'Novo Agendamento',
                message: 'Crie um agendamento definindo valor, frequência (diária, semanal, mensal) e data de início.',
                position: 'bottom',
                icon: 'plus'
            }
        ],
        'gamification': [
            {
                selector: '.conquista-card, .achievement-card, .achievement',
                title: 'Conquistas',
                message: 'Desbloqueie conquistas completando desafios financeiros. Cada uma vale pontos!',
                position: 'bottom',
                icon: 'medal'
            },
            {
                selector: '.nivel-progress, .level-section',
                title: 'Sistema de Níveis',
                message: 'Suba de nível acumulando pontos. Níveis mais altos desbloqueiam emblemas exclusivos.',
                position: 'right',
                icon: 'star'
            }
        ],
        'billing': [
            {
                selector: '.plan-card--recommended, .plan-card.pro',
                title: 'Plano Pro',
                message: 'Com o Pro você tem recursos ilimitados, relatórios avançados, exportação e suporte prioritário.',
                position: 'left',
                icon: 'crown'
            },
            {
                selector: '.billing-cycle-toggle, .cycle-toggle',
                title: 'Ciclo de Pagamento',
                message: 'Escolha entre mensal, semestral ou anual. Quanto maior o período, maior o desconto!',
                position: 'bottom',
                icon: 'calendar-check'
            }
        ]
    };

    // ============================================
    // ESTADO
    // ============================================

    let visitedPages = new Set();
    let activeTooltips = [];

    // ============================================
    // STORAGE
    // ============================================

    function loadVisitedPages() {
        try {
            const stored = localStorage.getItem(CONFIG.storageKey);
            if (stored) {
                visitedPages = new Set(JSON.parse(stored));
            }
        } catch (e) {
            console.warn('[FirstVisitTooltips] Erro ao carregar páginas visitadas:', e);
        }
    }

    function saveVisitedPages() {
        try {
            localStorage.setItem(CONFIG.storageKey, JSON.stringify([...visitedPages]));
        } catch (e) {
            console.warn('[FirstVisitTooltips] Erro ao salvar páginas visitadas:', e);
        }
    }

    function markPageVisited(page) {
        visitedPages.add(page);
        saveVisitedPages();
    }

    function hasVisitedPage(page) {
        return visitedPages.has(page);
    }

    // ============================================
    // DETECÇÃO DE PÁGINA
    // ============================================

    function getCurrentPage() {
        const path = window.location.pathname.toLowerCase();

        // Mapear URLs para identificadores de página
        const pageMap = {
            '/dashboard': 'dashboard',
            '/lancamentos': 'lancamentos',
            '/cartoes': 'cartoes',
            '/faturas': 'faturas',
            '/relatorios': 'relatorios',
            '/contas': 'contas',
            '/categorias': 'categorias',
            '/agendamentos': 'agendamentos',
            '/gamification': 'gamification',
            '/billing': 'billing',
            '/perfil': 'perfil',
        };

        for (const [url, page] of Object.entries(pageMap)) {
            if (path.includes(url)) {
                return page;
            }
        }

        // Dashboard é a página padrão
        if (path === '/' || path.endsWith('/public/') || path.endsWith('/public')) {
            return 'dashboard';
        }

        return null;
    }

    // ============================================
    // CRIAÇÃO DE TOOLTIPS
    // ============================================

    function createTooltipElement(config) {
        const tooltip = document.createElement('div');
        tooltip.className = 'fvt-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-live', 'polite');

        const proTag = config.proOnly ? '<span class="fvt-pro-tag">PRO</span>' : '';

        tooltip.innerHTML = `
            <div class="fvt-tooltip-content">
                <div class="fvt-tooltip-header">
                    <i data-lucide="${config.icon}" class="fvt-icon"></i>
                    <span class="fvt-title">${config.title}</span>
                    ${proTag}
                    <button class="fvt-close" aria-label="Fechar dica" title="Fechar">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <p class="fvt-message">${config.message}</p>
                <div class="fvt-footer">
                    <span class="fvt-hint">Dica de primeira visita</span>
                    <button class="fvt-got-it">Entendi</button>
                </div>
            </div>
            <div class="fvt-arrow"></div>
        `;

        if (window.lucide) lucide.createIcons({ nodes: [tooltip] });
        return tooltip;
    }

    function positionTooltip(tooltip, target, position) {
        const targetRect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        let top, left;
        const offset = 12;

        switch (position) {
            case 'top':
                top = targetRect.top + scrollTop - tooltipRect.height - offset;
                left = targetRect.left + scrollLeft + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + scrollTop + offset;
                left = targetRect.left + scrollLeft + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + scrollTop + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left + scrollLeft - tooltipRect.width - offset;
                break;
            case 'right':
                top = targetRect.top + scrollTop + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + scrollLeft + offset;
                break;
            default:
                top = targetRect.bottom + scrollTop + offset;
                left = targetRect.left + scrollLeft;
        }

        // Ajustar se sair da tela
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        if (left < 10) left = 10;
        if (left + tooltipRect.width > viewportWidth - 10) {
            left = viewportWidth - tooltipRect.width - 10;
        }
        if (top < scrollTop + 10) top = scrollTop + 10;

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
        tooltip.dataset.position = position;
    }

    function showTooltip(config, index = 0) {
        const target = document.querySelector(config.selector);
        if (!target) return null;

        // Verificar se elemento está visível
        const rect = target.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return null;

        // Verificar se é feature Pro e usuário é Free
        if (config.proOnly && window.PlanLimits && !window.PlanLimits.isPro()) {
            // Ainda mostrar, mas com indicação de que é Pro
        }

        const tooltip = createTooltipElement(config);
        document.body.appendChild(tooltip);

        // Aguardar renderização para posicionar
        requestAnimationFrame(() => {
            positionTooltip(tooltip, target, config.position);

            // Animar entrada
            setTimeout(() => {
                tooltip.classList.add('fvt-visible');
            }, 50 + (index * 200)); // Delay escalonado
        });

        // Highlight no elemento alvo
        target.classList.add('fvt-highlighted');

        // Eventos de fechamento
        const closeBtn = tooltip.querySelector('.fvt-close');
        const gotItBtn = tooltip.querySelector('.fvt-got-it');

        const closeTooltip = () => {
            tooltip.classList.remove('fvt-visible');
            target.classList.remove('fvt-highlighted');
            setTimeout(() => {
                tooltip.remove();
                activeTooltips = activeTooltips.filter(t => t !== tooltip);
            }, CONFIG.animationDuration);
        };

        closeBtn?.addEventListener('click', closeTooltip);
        gotItBtn?.addEventListener('click', closeTooltip);

        // Auto-fechar após duração
        setTimeout(closeTooltip, CONFIG.tooltipDuration + (index * 2000));

        activeTooltips.push(tooltip);
        return tooltip;
    }

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    function showTooltipsForPage(page) {
        const tooltips = PAGE_TOOLTIPS[page];
        if (!tooltips || tooltips.length === 0) return;

        // Mostrar até o máximo configurado
        let shown = 0;
        for (const config of tooltips) {
            if (shown >= CONFIG.maxTooltipsPerPage) break;

            const tooltip = showTooltip(config, shown);
            if (tooltip) shown++;
        }

        // Marcar página como visitada
        if (shown > 0) {
            markPageVisited(page);
        }
    }

    function init() {
        loadVisitedPages();

        const currentPage = getCurrentPage();
        if (!currentPage) return;

        // Só mostrar tooltips se for primeira visita
        if (hasVisitedPage(currentPage)) return;

        // Aguardar carregamento completo da página
        setTimeout(() => {
            // Verificar se onboarding está ativo (não mostrar tooltips durante onboarding)
            if (window.gamificationPaused) return;

            showTooltipsForPage(currentPage);
        }, 1500);
    }

    function removeAllTooltips() {
        activeTooltips.forEach(tooltip => {
            tooltip.remove();
        });
        activeTooltips = [];
        document.querySelectorAll('.fvt-highlighted').forEach(el => {
            el.classList.remove('fvt-highlighted');
        });
    }

    function resetVisitedPages() {
        visitedPages.clear();
        saveVisitedPages();
    }

    // ============================================
    // API PÚBLICA
    // ============================================

    window.FirstVisitTooltips = {
        init,
        showTooltipsForPage,
        removeAllTooltips,
        resetVisitedPages,
        hasVisitedPage,
        markPageVisited,
    };

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 500);
    }

})();
