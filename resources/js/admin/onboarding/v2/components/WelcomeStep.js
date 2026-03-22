/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Step Welcome
 * ============================================================================
 * Tela de boas-vindas com proposta de valor direta.
 * ============================================================================
 */

import { useOnboarding } from '../context/OnboardingContext.js';
import { ESTIMATED_TIME_SECONDS } from '../types.js';

const GLOBAL_CONFIG = window.__LK_CONFIG__ || window.__LK_CONFIG || {};

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Renderiza o step de boas-vindas
 * @param {HTMLElement} container
 */
export function renderWelcomeStep(container) {
    const { state, nextStep } = useOnboarding();
    const firstName = state.userName ? state.userName.split(' ')[0] : '';

    container.innerHTML = `
        <div class="lk-ob2-step lk-ob2-welcome" data-step="welcome">
            <div class="lk-ob2-bg-particles"></div>

            <div class="lk-ob2-content">
                <div class="lk-ob2-logo-wrapper">
                    <div class="lk-ob2-logo-glow"></div>
                    <img src="${GLOBAL_CONFIG.logoUrl || `${GLOBAL_CONFIG.baseUrl || '/'}assets/img/icone.png`}"
                         alt="Lukrato" class="lk-ob2-logo">
                </div>

                <div class="lk-ob2-greeting">
                    ${firstName ? `
                        <span class="lk-ob2-hello">Ola, ${escapeHtml(firstName)}!</span>
                    ` : ''}
                    <h1 class="lk-ob2-title">
                        Bem-vindo ao <span class="lk-highlight">Lukrato</span>
                    </h1>
                    <p class="lk-ob2-subtitle">
                        Voce vai entender seu dinheiro em menos de ${Math.ceil(ESTIMATED_TIME_SECONDS / 60)} minuto
                    </p>
                </div>

                <div class="lk-ob2-value-props">
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="eye"></i>
                        </div>
                        <span>Descubra para onde seu dinheiro esta indo</span>
                    </div>
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="wallet"></i>
                        </div>
                        <span>Comece com sua conta principal e ajuste o resto depois</span>
                    </div>
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="receipt"></i>
                        </div>
                        <span>Veja valor rapido assim que salvar o primeiro registro</span>
                    </div>
                </div>

                <button class="lk-ob2-btn-primary" id="btnStartOnboarding" type="button">
                    <span>Comecar agora</span>
                    <i data-lucide="arrow-right"></i>
                </button>

                <div class="lk-ob2-time-hint">
                    <i data-lucide="clock"></i>
                    <span>Leva menos de 1 minuto</span>
                </div>
            </div>
        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    const startBtn = container.querySelector('#btnStartOnboarding');
    if (startBtn) {
        startBtn.addEventListener('click', () => {
            startBtn.classList.add('loading');
            setTimeout(nextStep, 300);
        });
    }

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}
