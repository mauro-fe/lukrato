/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Progress Bar Component
 * ============================================================================
 * Barra de progresso com contagem clara de passos.
 * ============================================================================
 */

import { STEP_CONFIG, STEP_ORDER } from '../types.js';

const ACTIONABLE_STEPS = STEP_ORDER.slice(0, -1);

function getProgressMeta(currentStep) {
    const currentIndex = ACTIONABLE_STEPS.indexOf(currentStep);
    const safeIndex = currentIndex >= 0 ? currentIndex : 0;
    const currentNumber = safeIndex + 1;
    const total = ACTIONABLE_STEPS.length;

    return {
        currentIndex: safeIndex,
        currentNumber,
        total,
        progress: Math.round((currentNumber / total) * 100)
    };
}

/**
 * Cria a barra de progresso
 * @param {string} currentStep
 * @returns {string} HTML string
 */
export function createProgressBar(currentStep) {
    const { currentIndex, currentNumber, total, progress } = getProgressMeta(currentStep);
    const currentConfig = STEP_CONFIG[currentStep] || STEP_CONFIG[ACTIONABLE_STEPS[currentIndex]];

    return `
        <div class="lk-ob2-progress-bar">
            <div class="lk-ob2-progress-meta">
                <span class="lk-ob2-progress-kicker">Passo ${currentNumber} de ${total}</span>
                <strong class="lk-ob2-progress-current">${currentConfig?.title || 'Inicio'}</strong>
            </div>
            <div class="lk-ob2-progress-track">
                <div class="lk-ob2-progress-fill" style="width: ${progress}%"></div>
            </div>
            <div class="lk-ob2-progress-steps">
                ${ACTIONABLE_STEPS.map((step, index) => {
                    const stepConfig = STEP_CONFIG[step];
                    const isActive = index === currentIndex;
                    const isDone = index < currentIndex;

                    return `
                        <div class="lk-ob2-progress-step ${isActive ? 'active' : ''} ${isDone ? 'done' : ''}">
                            <div class="lk-ob2-progress-dot">
                                ${isDone ? '<i data-lucide="check"></i>' : index + 1}
                            </div>
                            <span class="lk-ob2-progress-label">${stepConfig.title}</span>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

/**
 * Atualiza a barra de progresso existente
 * @param {HTMLElement} container
 * @param {string} currentStep
 */
export function updateProgressBar(container, currentStep) {
    const progressBar = container.querySelector('.lk-ob2-progress-bar');
    if (!progressBar) {
        return;
    }

    container.innerHTML = createProgressBar(currentStep);

    if (window.lucide) {
        lucide.createIcons();
    }
}
