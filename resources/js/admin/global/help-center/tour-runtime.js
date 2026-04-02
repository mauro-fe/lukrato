import {
    clamp,
    ensureElementInViewport,
    getOppositeSide,
    resolveElement,
    resolveElementCandidate,
} from './viewport-scroll.js';
import { TUTORIAL_TYPES, TUTORIAL_VARIANTS } from './tour-configs.js';

export function buildStepsRuntime(helpCenter, target = helpCenter.getPageTutorialTarget()) {
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

export function teardownTourRefreshGuardsRuntime(helpCenter) {
    if (typeof helpCenter.tourRefreshCleanup === 'function') {
        helpCenter.tourRefreshCleanup();
    }

    helpCenter.tourRefreshCleanup = null;
}

export function clearTourTargetRuntime(helpCenter) {
    if (!(helpCenter.activeTourTarget instanceof HTMLElement)) {
        helpCenter.activeTourTarget = null;
        return;
    }

    helpCenter.activeTourTarget.removeAttribute('data-lk-help-tour-target');
    helpCenter.activeTourTarget = null;
}

export function setTourTargetRuntime(helpCenter, element) {
    if (!(element instanceof HTMLElement)) {
        clearTourTargetRuntime(helpCenter);
        return;
    }

    if (helpCenter.activeTourTarget === element) {
        return;
    }

    clearTourTargetRuntime(helpCenter);
    element.setAttribute('data-lk-help-tour-target', 'true');
    helpCenter.activeTourTarget = element;
}

export function createTourElementsRuntime() {
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

export function syncTourSpotlightRuntime(helpCenter, state) {
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

export function getTourPopoverPositionRuntime(targetRect, popoverRect, options = {}) {
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

export function positionTourPopoverRuntime(helpCenter, state) {
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
        const position = getTourPopoverPositionRuntime(targetRect, popoverRect, {
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
        const fallback = getTourPopoverPositionRuntime(targetRect, popoverRect, {
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
    syncTourSpotlightRuntime(helpCenter, state);
}

export function renderTourPopoverRuntime(helpCenter, state) {
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
        helpCenter.goToTourStep(state.index - 1);
    });

    state.popover.querySelector('[data-tour-action="cancel"]')?.addEventListener('click', () => {
        void helpCenter.closeTour(state, { markDismissed: !helpCenter.completedCurrentRun });
    });

    state.popover.querySelector('[data-tour-action="next"]')?.addEventListener('click', () => {
        if (isLast) {
            helpCenter.completedCurrentRun = true;
            void helpCenter.closeTour(state, { markCompleted: true });
            return;
        }

        helpCenter.goToTourStep(state.index + 1);
    });
}

export function openMobileSidebarIfNeededRuntime(helpCenter) {
    if (!helpCenter.isMobileViewport()) {
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

export function closeMobileSidebarIfNeededRuntime(helpCenter) {
    if (!helpCenter.isMobileViewport()) {
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

export function syncNavigationUIForStepRuntime(helpCenter, state, step) {
    if (state?.target?.type !== TUTORIAL_TYPES.NAVIGATION) {
        return;
    }

    if (state.target.variant !== TUTORIAL_VARIANTS.MOBILE) {
        return;
    }

    if (step?.ensureSidebarClosed) {
        closeMobileSidebarIfNeededRuntime(helpCenter);
    }

    if (step?.ensureSidebarOpen) {
        openMobileSidebarIfNeededRuntime(helpCenter);
    }
}

export function goToTourStepRuntime(helpCenter, index) {
    const state = helpCenter.tour;
    if (!state || !state.isActive()) {
        return;
    }

    const nextIndex = clamp(index, 0, state.steps.length - 1);
    state.index = nextIndex;

    const step = state.steps[nextIndex];
    syncNavigationUIForStepRuntime(helpCenter, state, step);
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
            helpCenter.setTourTarget(activeTarget);
            helpCenter.renderTourPopover(state);
            helpCenter.positionTourPopover(state);
        });
    });
}

export async function closeTourRuntime(helpCenter, state = helpCenter.tour, options = {}) {
    if (!state) {
        return;
    }

    const {
        silent = false,
        markCompleted = false,
        markDismissed = false,
    } = options;

    if (helpCenter.tour === state) {
        helpCenter.tour = null;
    }

    state.overlay?.remove();
    state.spotlight?.remove();
    state.popover?.remove();
    document.body.classList.remove('lk-help-tour-active');
    helpCenter.clearTourTarget();
    helpCenter.teardownTourRefreshGuards();

    window.removeEventListener('resize', state.repositionHandler);
    window.removeEventListener('scroll', state.repositionHandler);
    document.removeEventListener('keydown', state.keydownHandler);

    if (state.target?.type === TUTORIAL_TYPES.NAVIGATION
        && state.target.variant === TUTORIAL_VARIANTS.MOBILE) {
        closeMobileSidebarIfNeededRuntime(helpCenter);
    }

    if (!silent) {
        if (markCompleted) {
            await helpCenter.markCompleted(state.target);
            if (state.target?.type === TUTORIAL_TYPES.PAGE) {
                helpCenter.highlightPrimaryAction(true);
            }
        } else if (markDismissed) {
            await helpCenter.markDismissed(state.target);
        }

        helpCenter.renderMenuState();
    }
}

export async function startTutorialRuntime(helpCenter, target) {
    const steps = helpCenter.buildSteps(target);

    if (steps.length === 0) {
        window.LK?.toast?.info('Ainda nao existe tutorial pronto para este fluxo.');
        return false;
    }

    if (helpCenter.tour?.isActive?.()) {
        await helpCenter.closeTour(helpCenter.tour, { silent: true });
    }

    helpCenter.hideOffer();
    helpCenter.toggleMenu(false);
    helpCenter.completedCurrentRun = false;
    document.body.classList.remove('lk-help-tour-active');

    const { overlay, spotlight, popover } = createTourElementsRuntime();

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

            helpCenter.positionTourPopover(state);
        },
        keydownHandler: (event) => {
            if (!state.isActive()) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                void helpCenter.closeTour(state, { markDismissed: !helpCenter.completedCurrentRun });
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                helpCenter.goToTourStep(state.index + 1);
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                helpCenter.goToTourStep(state.index - 1);
            }
        },
        isActive: () => helpCenter.tour === state,
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

    helpCenter.tour = state;
    document.body.classList.add('lk-help-tour-active');
    helpCenter.goToTourStep(0);

    return true;
}

export async function startCurrentPageTutorialRuntime(helpCenter, _options = {}) {
    const target = helpCenter.getPageTutorialTarget();
    if (!target) {
        window.LK?.toast?.info('Ainda nao existe tutorial pronto para esta tela.');
        return false;
    }

    return helpCenter.startTutorial(target);
}

export async function startNavigationTutorialRuntime(helpCenter, _options = {}) {
    const target = helpCenter.getNavigationTutorialTarget();
    if (!target) {
        window.LK?.toast?.info('Ainda nao existe tutorial de navegacao.');
        return false;
    }

    return helpCenter.startTutorial(target);
}

export async function showCurrentPageTipsRuntime(helpCenter) {
    if (!helpCenter.hasTips()) {
        window.LK?.toast?.info('Ainda nao existe dica rapida para esta tela.');
        return false;
    }

    helpCenter.hideOffer();
    helpCenter.toggleMenu(false);
    window.FirstVisitTooltips?.removeAllTooltips?.();
    window.FirstVisitTooltips?.showTooltipsForPage?.(helpCenter.currentPage);
    await helpCenter.markTipsSeen();
    helpCenter.highlightPrimaryAction();
    helpCenter.renderMenuState();

    return true;
}

export function highlightPrimaryActionRuntime(helpCenter, scrollIntoView = false) {
    const pageTarget = helpCenter.getPageTutorialTarget();
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
