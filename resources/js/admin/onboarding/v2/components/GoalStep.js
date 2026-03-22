/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Step Goal
 * ============================================================================
 * Tela para o usuario selecionar seu objetivo principal.
 * ============================================================================
 */

import { useOnboarding } from '../context/OnboardingContext.js';
import { USER_GOALS } from '../types.js';

/**
 * Renderiza o step de objetivo
 * @param {HTMLElement} container
 */
export function renderGoalStep(container) {
    const { state, setGoal, nextStep, prevStep } = useOnboarding();

    container.innerHTML = `
        <div class="lk-ob2-step lk-ob2-goal" data-step="goal">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box">
                        <i data-lucide="target"></i>
                    </div>
                    <h1 class="lk-ob2-title">Qual vai ser seu foco agora?</h1>
                    <p class="lk-ob2-subtitle">
                        Escolha o que mais importa hoje. O restante voce ajusta depois.
                    </p>
                </div>

                <div class="lk-ob2-goals-grid">
                    ${USER_GOALS.map((goal) => `
                        <button type="button"
                                class="lk-ob2-goal-card ${state.data.goal === goal.id ? 'selected' : ''}"
                                data-goal-id="${goal.id}"
                                style="--goal-color: ${goal.color}">
                            <div class="lk-ob2-goal-emoji">${goal.emoji}</div>
                            <div class="lk-ob2-goal-text">
                                <span class="lk-ob2-goal-title">${goal.title}</span>
                                <span class="lk-ob2-goal-desc">${goal.description}</span>
                            </div>
                            <div class="lk-ob2-goal-check">
                                <i data-lucide="check"></i>
                            </div>
                        </button>
                    `).join('')}
                </div>

                <div class="lk-ob2-actions">
                    <button type="button" class="lk-ob2-btn-back" id="btnGoalBack">
                        <i data-lucide="arrow-left"></i>
                        <span>Voltar</span>
                    </button>
                    <button type="button" class="lk-ob2-btn-primary" id="btnGoalNext" disabled>
                        <span>Continuar</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    let selectedGoal = state.data.goal;

    const goalCards = container.querySelectorAll('.lk-ob2-goal-card');
    const nextBtn = container.querySelector('#btnGoalNext');
    const backBtn = container.querySelector('#btnGoalBack');

    function updateButtonState() {
        if (nextBtn) {
            nextBtn.disabled = !selectedGoal;
        }
    }

    goalCards.forEach((card) => {
        card.addEventListener('click', () => {
            const goalId = card.dataset.goalId;

            goalCards.forEach((item) => item.classList.remove('selected'));
            card.classList.add('selected');

            selectedGoal = goalId;
            setGoal(goalId);
            updateButtonState();

            if (navigator.vibrate) {
                navigator.vibrate(10);
            }

            setTimeout(() => {
                if (nextBtn && !nextBtn.disabled) {
                    nextBtn.click();
                }
            }, 350);
        });
    });

    if (backBtn) {
        backBtn.addEventListener('click', prevStep);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (!selectedGoal) {
                return;
            }

            nextBtn.classList.add('loading');
            setTimeout(nextStep, 200);
        });
    }

    updateButtonState();

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}
