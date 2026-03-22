/**
 * ============================================================================
 * LUKRATO — Dashboard Guided Tour
 * ============================================================================
 * Sistema de tooltips guiados para primeira visita ao dashboard
 * Ajuda o usuário a entender onde estão as principais funcionalidades
 * ============================================================================
 */

const BASE_URL = window.__LK_CONFIG?.baseUrl || '/';
const TOUR_STORAGE_KEY = 'lk_dashboard_tour_completed';

/**
 * Configuração dos passos do tour
 */
const TOUR_STEPS = [
    {
        target: '.lk-health-score',
        title: 'Sua Saúde Financeira',
        content: 'Este número mostra como estão suas finanças. Quanto mais alto, melhor!',
        position: 'bottom',
        icon: 'heart-pulse'
    },
    {
        target: '.lk-kpi-cards',
        title: 'Resumo Financeiro',
        content: 'Veja seu saldo, receitas e despesas do mês em um só lugar.',
        position: 'bottom',
        icon: 'bar-chart-3'
    },
    {
        target: '#addTransactionBtn, .fab, [data-action="add-transaction"]',
        title: 'Adicionar Lançamentos',
        content: 'Use este botão para registrar suas receitas e despesas.',
        position: 'top',
        icon: 'plus-circle',
        highlight: true
    },
    {
        target: '.sidebar .nav-item[href*="lancamentos"]',
        title: 'Todos os Lançamentos',
        content: 'Aqui você vê o histórico completo de tudo que entrou e saiu.',
        position: 'right',
        icon: 'layers'
    },
    {
        target: '.sidebar .nav-item[href*="relatorios"]',
        title: 'Relatórios',
        content: 'Descubra para onde vai seu dinheiro com gráficos detalhados.',
        position: 'right',
        icon: 'pie-chart'
    }
];

/**
 * Classe do Tour Guiado
 */
class DashboardTour {
    constructor() {
        this.currentStep = 0;
        this.isActive = false;
        this.overlay = null;
        this.tooltip = null;
    }

    /**
     * Verifica se deve mostrar o tour
     */
    shouldShowTour() {
        // Verificar se é primeira visita via URL parameter ou global flag
        const isFirstVisitParam = new URLSearchParams(window.location.search).get('first_visit') === '1';
        const isFirstVisitFlag = window.__lkFirstVisit === true;
        const tourCompleted = localStorage.getItem(TOUR_STORAGE_KEY) === 'true';

        return (isFirstVisitParam || isFirstVisitFlag) && !tourCompleted;
    }

    /**
     * Inicia o tour
     */
    start() {
        if (this.isActive) return;

        // Verificar se elementos existem
        const firstTarget = document.querySelector(TOUR_STEPS[0].target);
        if (!firstTarget) {
            console.warn('[Tour] First target not found, skipping tour');
            return;
        }

        this.isActive = true;
        this.currentStep = 0;

        this.createOverlay();
        this.showStep(0);

        // Analytics
        this.trackEvent('tour_started');
    }

    /**
     * Cria o overlay de fundo
     */
    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'lk-tour-overlay';
        this.overlay.innerHTML = `
            <div class="lk-tour-backdrop"></div>
        `;
        document.body.appendChild(this.overlay);

        // Click no backdrop para pular
        this.overlay.querySelector('.lk-tour-backdrop').addEventListener('click', () => {
            this.askToSkip();
        });
    }

    /**
     * Mostra um passo do tour
     */
    showStep(index) {
        if (index >= TOUR_STEPS.length) {
            this.complete();
            return;
        }

        const step = TOUR_STEPS[index];
        const target = document.querySelector(step.target);

        if (!target) {
            // Skip para próximo se target não existe
            this.showStep(index + 1);
            return;
        }

        this.currentStep = index;

        // Remover tooltip anterior
        if (this.tooltip) {
            this.tooltip.remove();
        }

        // Destacar elemento
        this.highlightElement(target, step.highlight);

        // Criar tooltip
        this.createTooltip(target, step, index);

        // Scroll para elemento se necessário
        this.scrollToElement(target);
    }

    /**
     * Destaca um elemento
     */
    highlightElement(element, isHighlighted = false) {
        // Remover highlight anterior
        document.querySelectorAll('.lk-tour-highlighted').forEach(el => {
            el.classList.remove('lk-tour-highlighted');
        });

        // Adicionar novo highlight
        element.classList.add('lk-tour-highlighted');

        if (isHighlighted) {
            element.classList.add('lk-tour-pulse');
        }

        // Posicionar spotlight
        const rect = element.getBoundingClientRect();
        const spotlight = this.overlay.querySelector('.lk-tour-spotlight') ||
            document.createElement('div');

        spotlight.className = 'lk-tour-spotlight';
        spotlight.style.cssText = `
            position: fixed;
            top: ${rect.top - 8}px;
            left: ${rect.left - 8}px;
            width: ${rect.width + 16}px;
            height: ${rect.height + 16}px;
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
            pointer-events: none;
            z-index: 10000;
            transition: all 0.3s ease;
        `;

        if (!spotlight.parentElement) {
            this.overlay.appendChild(spotlight);
        }
    }

    /**
     * Cria o tooltip
     */
    createTooltip(target, step, index) {
        const rect = target.getBoundingClientRect();
        const isLast = index === TOUR_STEPS.length - 1;

        this.tooltip = document.createElement('div');
        this.tooltip.className = `lk-tour-tooltip lk-tour-tooltip-${step.position}`;
        this.tooltip.innerHTML = `
            <div class="lk-tour-tooltip-content">
                <div class="lk-tour-tooltip-header">
                    <div class="lk-tour-tooltip-icon">
                        <i data-lucide="${step.icon}"></i>
                    </div>
                    <div class="lk-tour-tooltip-title">${step.title}</div>
                </div>
                <p class="lk-tour-tooltip-text">${step.content}</p>
                <div class="lk-tour-tooltip-footer">
                    <div class="lk-tour-tooltip-progress">
                        ${index + 1} de ${TOUR_STEPS.length}
                    </div>
                    <div class="lk-tour-tooltip-actions">
                        <button class="lk-tour-btn-skip" type="button">Pular</button>
                        <button class="lk-tour-btn-next" type="button">
                            ${isLast ? 'Concluir' : 'Próximo'}
                            <i data-lucide="${isLast ? 'check' : 'arrow-right'}"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="lk-tour-tooltip-arrow"></div>
        `;

        document.body.appendChild(this.tooltip);

        // Posicionar tooltip
        this.positionTooltip(rect, step.position);

        // Init icons
        if (window.lucide) {
            lucide.createIcons({ icons: this.tooltip });
        }

        // Bind events
        this.tooltip.querySelector('.lk-tour-btn-next').addEventListener('click', () => {
            this.next();
        });

        this.tooltip.querySelector('.lk-tour-btn-skip').addEventListener('click', () => {
            this.askToSkip();
        });

        // Animate in
        requestAnimationFrame(() => {
            this.tooltip.classList.add('visible');
        });
    }

    /**
     * Posiciona o tooltip
     */
    positionTooltip(targetRect, position) {
        const tooltip = this.tooltip;
        const tooltipRect = tooltip.getBoundingClientRect();
        let top, left;

        switch (position) {
            case 'top':
                top = targetRect.top - tooltipRect.height - 16;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + 16;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left - tooltipRect.width - 16;
                break;
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + 16;
                break;
        }

        // Keep within viewport
        const padding = 16;
        left = Math.max(padding, Math.min(left, window.innerWidth - tooltipRect.width - padding));
        top = Math.max(padding, Math.min(top, window.innerHeight - tooltipRect.height - padding));

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    }

    /**
     * Scroll para elemento
     */
    scrollToElement(element) {
        const rect = element.getBoundingClientRect();
        const isInView = rect.top >= 0 &&
            rect.bottom <= window.innerHeight &&
            rect.left >= 0 &&
            rect.right <= window.innerWidth;

        if (!isInView) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    /**
     * Próximo passo
     */
    next() {
        this.trackEvent('tour_step_completed', { step: this.currentStep });
        this.showStep(this.currentStep + 1);
    }

    /**
     * Pergunta se quer pular
     */
    askToSkip() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Pular o tour?',
                text: 'Você pode acessar o tour novamente pelo menu de ajuda.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-primary, #e67e22)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, pular',
                cancelButtonText: 'Continuar tour'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.skip();
                }
            });
        } else {
            if (confirm('Pular o tour? Você pode acessá-lo novamente pelo menu de ajuda.')) {
                this.skip();
            }
        }
    }

    /**
     * Pula o tour
     */
    skip() {
        this.trackEvent('tour_skipped', { step: this.currentStep });
        this.cleanup();
        localStorage.setItem(TOUR_STORAGE_KEY, 'true');
    }

    /**
     * Completa o tour
     */
    complete() {
        this.trackEvent('tour_completed');
        this.cleanup();
        localStorage.setItem(TOUR_STORAGE_KEY, 'true');

        // Mostrar mensagem de conclusão
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Tour concluído! 🎉',
                text: 'Agora você conhece o básico do Lukrato. Bora organizar suas finanças!',
                icon: 'success',
                confirmButtonColor: 'var(--color-primary, #e67e22)',
                confirmButtonText: 'Vamos lá!'
            });
        }
    }

    /**
     * Limpa elementos do tour
     */
    cleanup() {
        this.isActive = false;

        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }

        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }

        document.querySelectorAll('.lk-tour-highlighted, .lk-tour-pulse').forEach(el => {
            el.classList.remove('lk-tour-highlighted', 'lk-tour-pulse');
        });
    }

    /**
     * Rastreia eventos (analytics)
     */
    trackEvent(event, data = {}) {
        // Implementar tracking aqui (Google Analytics, Mixpanel, etc.)
        console.log('[Tour]', event, data);
    }
}

/**
 * CSS do Tour (injetado dinamicamente)
 */
function injectTourStyles() {
    if (document.getElementById('lk-tour-styles')) return;

    const styles = document.createElement('style');
    styles.id = 'lk-tour-styles';
    styles.textContent = `
        .lk-tour-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
        }

        .lk-tour-backdrop {
            position: absolute;
            inset: 0;
            pointer-events: auto;
        }

        .lk-tour-highlighted {
            position: relative;
            z-index: 10001 !important;
        }

        .lk-tour-pulse {
            animation: lk-tour-pulse 2s ease-in-out infinite;
        }

        @keyframes lk-tour-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(230, 126, 34, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(230, 126, 34, 0); }
        }

        .lk-tour-tooltip {
            position: fixed;
            z-index: 10002;
            max-width: 320px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .lk-tour-tooltip.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .lk-tour-tooltip-content {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .lk-tour-tooltip-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .lk-tour-tooltip-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-primary), #d35400);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lk-tour-tooltip-icon svg {
            width: 20px;
            height: 20px;
            color: white;
        }

        .lk-tour-tooltip-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .lk-tour-tooltip-text {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .lk-tour-tooltip-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .lk-tour-tooltip-progress {
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }

        .lk-tour-tooltip-actions {
            display: flex;
            gap: 8px;
        }

        .lk-tour-btn-skip {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--color-text-muted);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .lk-tour-btn-skip:hover {
            color: var(--color-text);
            border-color: var(--color-text-muted);
        }

        .lk-tour-btn-next {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--color-primary);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .lk-tour-btn-next:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        }

        .lk-tour-btn-next svg {
            width: 14px;
            height: 14px;
        }

        .lk-tour-tooltip-arrow {
            position: absolute;
            width: 12px;
            height: 12px;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            transform: rotate(45deg);
        }

        .lk-tour-tooltip-top .lk-tour-tooltip-arrow {
            bottom: -7px;
            left: 50%;
            margin-left: -6px;
            border-top: none;
            border-left: none;
        }

        .lk-tour-tooltip-bottom .lk-tour-tooltip-arrow {
            top: -7px;
            left: 50%;
            margin-left: -6px;
            border-bottom: none;
            border-right: none;
        }

        .lk-tour-tooltip-left .lk-tour-tooltip-arrow {
            right: -7px;
            top: 50%;
            margin-top: -6px;
            border-left: none;
            border-bottom: none;
        }

        .lk-tour-tooltip-right .lk-tour-tooltip-arrow {
            left: -7px;
            top: 50%;
            margin-top: -6px;
            border-right: none;
            border-top: none;
        }

        @media (max-width: 600px) {
            .lk-tour-tooltip {
                max-width: calc(100vw - 32px);
                left: 16px !important;
                right: 16px;
            }

            .lk-tour-tooltip-content {
                padding: 16px;
            }
        }
    `;
    document.head.appendChild(styles);
}

// Singleton
let tourInstance = null;

/**
 * Inicializa o tour guiado
 */
export function initDashboardTour() {
    injectTourStyles();

    if (!tourInstance) {
        tourInstance = new DashboardTour();
    }

    // Delay para garantir que elementos foram renderizados
    setTimeout(() => {
        if (tourInstance.shouldShowTour()) {
            tourInstance.start();
        }
    }, 1500);

    return tourInstance;
}

/**
 * Inicia o tour manualmente
 */
export function startTour() {
    if (!tourInstance) {
        tourInstance = new DashboardTour();
        injectTourStyles();
    }
    tourInstance.start();
}

/**
 * Reseta o tour (para testes)
 */
export function resetTour() {
    localStorage.removeItem(TOUR_STORAGE_KEY);
}

// Expose para uso global
if (typeof window !== 'undefined') {
    window.__LK_TOUR__ = {
        start: startTour,
        reset: resetTour
    };
}
