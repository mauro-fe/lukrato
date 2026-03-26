import '../../../../css/admin/onboarding/v2/index.css';
import { onboardingStore } from './context/OnboardingContext.js';
import { STEP_ORDER, ONBOARDING_STEPS } from './types.js';
import { createProgressBar } from './components/ProgressBar.js';
import { renderWelcomeStep } from './components/WelcomeStep.js';
import { renderGoalStep } from './components/GoalStep.js';
import { renderAccountStep } from './components/AccountStep.js';
import { renderTransactionStep } from './components/TransactionStep.js';
import { renderSuccessStep } from './components/SuccessStep.js';

const root = document.getElementById('onboardingRoot');
const config = window.__LK_ONBOARDING_CONFIG__ || {};

function normalizeStep(step) {
    return STEP_ORDER.includes(step) ? step : ONBOARDING_STEPS.WELCOME;
}

function renderProgress(container, currentStep) {
    const shouldShowProgress = currentStep !== ONBOARDING_STEPS.SUCCESS;

    container.style.display = shouldShowProgress ? '' : 'none';
    container.innerHTML = shouldShowProgress ? createProgressBar(currentStep) : '';

    if (shouldShowProgress && window.lucide) {
        lucide.createIcons();
    }
}

function renderCurrentStep(container, state) {
    const currentStep = normalizeStep(state.currentStep);

    switch (currentStep) {
        case ONBOARDING_STEPS.GOAL:
            renderGoalStep(container);
            break;
        case ONBOARDING_STEPS.ACCOUNT:
            renderAccountStep(container, Array.isArray(config.instituicoes) ? config.instituicoes : []);
            break;
        case ONBOARDING_STEPS.TRANSACTION:
            renderTransactionStep(container, {
                categorias: Array.isArray(config.categorias) ? config.categorias : [],
                conta: config.conta || null
            });
            break;
        case ONBOARDING_STEPS.SUCCESS:
            renderSuccessStep(container);
            break;
        case ONBOARDING_STEPS.WELCOME:
        default:
            renderWelcomeStep(container);
            break;
    }
}

function createShell() {
    root.innerHTML = `
        <div class="lk-ob2-app">
            <div class="lk-ob2-progress-container" id="onboardingProgress"></div>
            <div class="lk-ob2-content-container">
                <div id="onboardingStepContainer"></div>
            </div>
        </div>
    `;

    return {
        progress: root.querySelector('#onboardingProgress'),
        step: root.querySelector('#onboardingStepContainer')
    };
}

function renderApp() {
    if (!root) {
        return;
    }

    const state = onboardingStore.getState();
    const shell = createShell();

    renderProgress(shell.progress, normalizeStep(state.currentStep));
    renderCurrentStep(shell.step, state);
}

function init() {
    if (!root) {
        return;
    }

    let previousStep = null;

    onboardingStore.subscribe((state) => {
        const currentStep = normalizeStep(state.currentStep);
        if (currentStep === previousStep) {
            return;
        }

        previousStep = currentStep;
        renderApp();
    });

    previousStep = normalizeStep(onboardingStore.getState().currentStep);
    renderApp();
}

init();
