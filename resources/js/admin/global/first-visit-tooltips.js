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
        mobileTooltipDuration: 12000,
        animationDuration: 300,
        maxTooltipsPerPage: 3,
        maxMobileTooltipsPerPage: 3,
        viewportMargin: 20,
        mobileViewportMargin: 12,
        floatingTooltipGap: 14,
        visibleTargetThreshold: 0.18,
        mobileBreakpoint: 992,
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
                selector: '.cart-search-wrapper, #searchCartoes',
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
                selector: '#exportBtn, #exportControl',
                title: 'Exportar Relatório',
                message: 'Baixe seus relatórios em PDF ou Excel. Recurso exclusivo do plano Pro.',
                position: 'left',
                icon: 'file-output',
                proOnly: true
            }
        ],
        'metas': [
            {
                selector: '#btnNovaMetaHeader, #btnNovaMeta, #btnNovaMetaEmpty',
                title: 'Crie sua primeira meta',
                message: 'Defina valor e prazo para acompanhar seu objetivo sem depender de planilha.',
                position: 'bottom',
                icon: 'circle-plus'
            },
            {
                selector: '#summaryMetas, .met-summary-grid',
                title: 'Resumo das metas',
                message: 'Aqui vocÃª enxerga metas ativas, total acumulado e o progresso geral do que estÃ¡ construindo.',
                position: 'bottom',
                icon: 'target'
            },
            {
                selector: '#metasGrid, .met-grid',
                title: 'Acompanhamento',
                message: 'Cada card mostra quanto falta, o percentual jÃ¡ atingido e onde vale concentrar seus aportes.',
                position: 'top',
                icon: 'flag'
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

    function isMobileViewport() {
        return window.matchMedia(`(max-width: ${CONFIG.mobileBreakpoint}px)`).matches;
    }

    function getRuntimeTooltipDuration(index = 0) {
        if (isMobileViewport()) {
            return CONFIG.mobileTooltipDuration + (index * 1500);
        }

        return CONFIG.tooltipDuration + (index * 2000);
    }

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
            '/metas': 'metas',
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

    function adaptTooltipConfigForViewport(config) {
        if (!isMobileViewport()) {
            return config;
        }

        if (config?.skipOnMobile) {
            return null;
        }

        return {
            ...config,
            selector: config.mobileSelector || config.selector,
            message: config.mobileMessage || config.message,
            position: config.mobilePosition || config.position || 'bottom',
        };
    }

    // ============================================
    // CRIAÇÃO DE TOOLTIPS
    // ============================================

    function createTooltipElement(config, options = {}) {
        const tooltip = document.createElement('div');
        tooltip.className = 'fvt-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-live', 'polite');

        const proTag = config.proOnly ? '<span class="fvt-pro-tag">PRO</span>' : '';
        const isMobileFlow = options.mobileFlow === true;
        const step = Number(options.stepIndex || 0) + 1;
        const total = Number(options.totalSteps || 0);
        const isLastStep = step >= total;
        const hintText = isMobileFlow && total > 0
            ? `Dica ${step} de ${total}`
            : 'Dica de primeira visita';
        const primaryText = isMobileFlow
            ? (isLastStep ? 'Concluir' : 'Proxima')
            : 'Entendi';
        const skipButton = isMobileFlow
            ? '<button class="fvt-skip" type="button">Pular</button>'
            : '';

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
                    <span class="fvt-hint">${hintText}</span>
                    <div class="fvt-actions">
                        ${skipButton}
                        <button class="fvt-got-it" type="button">${primaryText}</button>
                    </div>
                </div>
            </div>
            <div class="fvt-arrow"></div>
        `;

        if (window.lucide) lucide.createIcons({ nodes: [tooltip] });
        return tooltip;
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function getViewportSafeArea() {
        const margin = isMobileViewport() ? CONFIG.mobileViewportMargin : CONFIG.viewportMargin;
        const topNavbar = document.querySelector('.top-navbar');
        const navbarBottom = topNavbar ? topNavbar.getBoundingClientRect().bottom : 0;

        return {
            top: Math.max(margin, navbarBottom + 16),
            right: margin,
            bottom: margin,
            left: margin,
        };
    }

    function getVisibleMetrics(rect) {
        const visibleWidth = Math.max(0, Math.min(rect.right, window.innerWidth) - Math.max(rect.left, 0));
        const visibleHeight = Math.max(0, Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0));
        const visibleArea = visibleWidth * visibleHeight;
        const totalArea = Math.max(1, rect.width * rect.height);

        return {
            visibleWidth,
            visibleHeight,
            visibleArea,
            visibleRatio: visibleArea / totalArea,
        };
    }

    function resolveTooltipTarget(selector) {
        const candidates = Array.from(document.querySelectorAll(selector))
            .map((element) => {
                const rect = element.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0) {
                    return null;
                }

                const { visibleRatio } = getVisibleMetrics(rect);
                const centerX = rect.left + (rect.width / 2);
                const centerY = rect.top + (rect.height / 2);
                const distanceToViewportCenter = Math.abs(centerX - (window.innerWidth / 2))
                    + Math.abs(centerY - (window.innerHeight / 2));

                return {
                    element,
                    visibleRatio,
                    distanceToViewportCenter,
                };
            })
            .filter(Boolean)
            .sort((a, b) => {
                if (b.visibleRatio !== a.visibleRatio) {
                    return b.visibleRatio - a.visibleRatio;
                }

                return a.distanceToViewportCenter - b.distanceToViewportCenter;
            });

        return candidates[0]?.element || null;
    }

    function shouldDetachTooltip(targetRect) {
        return getVisibleMetrics(targetRect).visibleRatio < CONFIG.visibleTargetThreshold;
    }

    function ensureTooltipTargetInViewport(target) {
        if (!isMobileViewport() || !(target instanceof HTMLElement)) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const safeArea = getViewportSafeArea();
        const minVisibleTop = safeArea.top + 12;
        const maxVisibleBottom = window.innerHeight - 220;
        const needsScroll = rect.top < minVisibleTop || rect.bottom > maxVisibleBottom;

        if (!needsScroll) {
            return;
        }

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest',
        });
    }

    function getDetachedTooltipPosition(tooltipRect, index) {
        const safeArea = getViewportSafeArea();
        const gap = CONFIG.floatingTooltipGap;
        const maxTop = Math.max(safeArea.top, window.innerHeight - tooltipRect.height - safeArea.bottom);

        return {
            top: clamp(
                safeArea.top + (index * (tooltipRect.height + gap)),
                safeArea.top,
                maxTop
            ),
            left: clamp(
                window.innerWidth - tooltipRect.width - safeArea.right,
                safeArea.left,
                Math.max(safeArea.left, window.innerWidth - tooltipRect.width - safeArea.right)
            ),
            detached: true,
            appliedPosition: 'floating',
        };
    }

    function getMobileSheetPosition(tooltipRect) {
        const safeArea = getViewportSafeArea();
        const maxWidth = Math.max(280, window.innerWidth - (safeArea.left + safeArea.right));
        const left = clamp(
            (window.innerWidth - Math.min(tooltipRect.width, maxWidth)) / 2,
            safeArea.left,
            Math.max(safeArea.left, window.innerWidth - tooltipRect.width - safeArea.right)
        );
        const top = Math.max(
            safeArea.top,
            window.innerHeight - tooltipRect.height - safeArea.bottom
        );

        return {
            top,
            left,
            detached: true,
            appliedPosition: 'mobile-sheet',
        };
    }

    function getAnchoredTooltipPosition(targetRect, tooltipRect, position) {
        const safeArea = getViewportSafeArea();
        const offset = 12;
        const minLeft = safeArea.left;
        const maxLeft = Math.max(minLeft, window.innerWidth - tooltipRect.width - safeArea.right);
        const minTop = safeArea.top;
        const maxTop = Math.max(minTop, window.innerHeight - tooltipRect.height - safeArea.bottom);

        let top;
        let left;

        switch (position) {
            case 'top':
                top = targetRect.top - tooltipRect.height - offset;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + offset;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left - tooltipRect.width - offset;
                break;
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + offset;
                break;
            default:
                top = targetRect.bottom + offset;
                left = targetRect.left;
        }

        return {
            top: clamp(top, minTop, maxTop),
            left: clamp(left, minLeft, maxLeft),
            detached: false,
            appliedPosition: position,
        };
    }

    function syncTargetHighlight(target, shouldHighlight) {
        if (!target) return;
        target.classList.toggle('fvt-highlighted', shouldHighlight);
    }

    function positionTooltip(tooltip, target, position, index = 0) {
        if (!tooltip || !target) return;

        const tooltipRect = tooltip.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        const coordinates = isMobileViewport()
            ? getMobileSheetPosition(tooltipRect)
            : (shouldDetachTooltip(targetRect)
                ? getDetachedTooltipPosition(tooltipRect, index)
                : getAnchoredTooltipPosition(targetRect, tooltipRect, position));

        tooltip.style.top = `${coordinates.top}px`;
        tooltip.style.left = `${coordinates.left}px`;
        tooltip.dataset.position = coordinates.appliedPosition;
        tooltip.classList.toggle('fvt-detached', coordinates.detached);
        tooltip.classList.toggle('fvt-mobile-sheet', isMobileViewport());

        const shouldHighlight = isMobileViewport()
            ? getVisibleMetrics(targetRect).visibleRatio >= CONFIG.visibleTargetThreshold
            : !coordinates.detached;
        syncTargetHighlight(target, shouldHighlight);
    }

    function repositionActiveTooltips() {
        activeTooltips = activeTooltips.filter((tooltip) => {
            const target = tooltip.__fvtTarget;
            if (!tooltip.isConnected || !target || !target.isConnected) {
                if (tooltip.__fvtAutoCloseTimer) {
                    clearTimeout(tooltip.__fvtAutoCloseTimer);
                    tooltip.__fvtAutoCloseTimer = null;
                }
                tooltip.remove();
                return false;
            }

            positionTooltip(
                tooltip,
                target,
                tooltip.__fvtPreferredPosition,
                tooltip.__fvtIndex || 0
            );

            return true;
        });
    }

    let repositionFrame = 0;

    function scheduleTooltipReposition() {
        if (repositionFrame) return;

        repositionFrame = requestAnimationFrame(() => {
            repositionFrame = 0;
            repositionActiveTooltips();
        });
    }

    function showTooltip(inputConfig, index = 0, options = {}) {
        const config = adaptTooltipConfigForViewport(inputConfig);
        if (!config) return null;

        const target = resolveTooltipTarget(config.selector);
        if (!target) return null;

        // Verificar se elemento está visível
        const rect = target.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return null;

        // Verificar se é feature Pro e usuário é Free
        if (config.proOnly && window.PlanLimits && !window.PlanLimits.isPro()) {
            // Ainda mostrar, mas com indicação de que é Pro
        }

        ensureTooltipTargetInViewport(target);

        const tooltip = createTooltipElement(config, options);
        tooltip.__fvtTarget = target;
        tooltip.__fvtPreferredPosition = config.position;
        tooltip.__fvtIndex = index;
        document.body.appendChild(tooltip);

        // Aguardar renderização para posicionar
        requestAnimationFrame(() => {
            positionTooltip(tooltip, target, config.position, index);

            // Animar entrada
            setTimeout(() => {
                tooltip.classList.add('fvt-visible');
            }, 50 + (index * 200)); // Delay escalonado
        });
        // Eventos de fechamento
        const closeBtn = tooltip.querySelector('.fvt-close');
        const skipBtn = tooltip.querySelector('.fvt-skip');
        const gotItBtn = tooltip.querySelector('.fvt-got-it');

        const closeTooltip = (reason = 'dismiss') => {
            if (tooltip.__fvtClosed) {
                return;
            }

            tooltip.__fvtClosed = true;
            tooltip.classList.remove('fvt-visible');
            target.classList.remove('fvt-highlighted');

            if (tooltip.__fvtAutoCloseTimer) {
                clearTimeout(tooltip.__fvtAutoCloseTimer);
                tooltip.__fvtAutoCloseTimer = null;
            }

            setTimeout(() => {
                tooltip.remove();
                activeTooltips = activeTooltips.filter(t => t !== tooltip);

                if (reason === 'primary') {
                    options.onPrimary?.();
                    return;
                }

                options.onDismiss?.();
            }, CONFIG.animationDuration);
        };

        closeBtn?.addEventListener('click', () => closeTooltip('dismiss'));
        skipBtn?.addEventListener('click', () => closeTooltip('dismiss'));
        gotItBtn?.addEventListener('click', () => closeTooltip('primary'));

        // Auto-fechar após duração
        if (options.autoClose !== false) {
            tooltip.__fvtAutoCloseTimer = setTimeout(() => {
                closeTooltip('dismiss');
            }, getRuntimeTooltipDuration(index));
        }

        activeTooltips.push(tooltip);
        return tooltip;
    }

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    function showTooltipsForPage(page) {
        const tooltips = PAGE_TOOLTIPS[page];
        if (!tooltips || tooltips.length === 0) return;

        if (isMobileViewport()) {
            const queue = tooltips
                .map((config) => adaptTooltipConfigForViewport(config))
                .filter(Boolean)
                .slice(0, CONFIG.maxMobileTooltipsPerPage);
            if (queue.length === 0) return;

            removeAllTooltips();

            let stepIndex = 0;
            const runMobileStep = () => {
                if (stepIndex >= queue.length) {
                    markPageVisited(page);
                    return;
                }

                const currentIndex = stepIndex;
                const tooltip = showTooltip(queue[currentIndex], currentIndex, {
                    mobileFlow: true,
                    stepIndex: currentIndex,
                    totalSteps: queue.length,
                    autoClose: false,
                    onPrimary: () => {
                        stepIndex += 1;
                        runMobileStep();
                    },
                    onDismiss: () => {
                        markPageVisited(page);
                    },
                });

                if (!tooltip) {
                    stepIndex += 1;
                    runMobileStep();
                }
            };

            runMobileStep();
            return;
        }

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

    function hasTooltipsForPage(page) {
        const tooltips = PAGE_TOOLTIPS[page];
        if (!Array.isArray(tooltips) || tooltips.length === 0) {
            return false;
        }

        if (!isMobileViewport()) {
            return true;
        }

        return tooltips.some((config) => Boolean(adaptTooltipConfigForViewport(config)));
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
            if (tooltip.__fvtAutoCloseTimer) {
                clearTimeout(tooltip.__fvtAutoCloseTimer);
                tooltip.__fvtAutoCloseTimer = null;
            }

            tooltip.__fvtClosed = true;
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
        hasTooltipsForPage,
        getCurrentPage,
    };

    // Auto-inicializar
    window.addEventListener('resize', scheduleTooltipReposition, { passive: true });
    window.addEventListener('scroll', scheduleTooltipReposition, { passive: true });

    if (!window.__LK_HELP_CENTER_MANAGED) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            setTimeout(init, 500);
        }
    }

})();
