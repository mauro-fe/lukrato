/**
 * ============================================================================
 * LUKRATO - Onboarding V2: Step Success
 * ============================================================================
 * Tela de celebracao e transicao para o dashboard.
 * ============================================================================
 */

import { useOnboarding } from '../context/OnboardingContext.js';
import { GOAL_MESSAGES, USER_GOALS } from '../types.js';

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Renderiza o step de sucesso
 * @param {HTMLElement} container
 */
export function renderSuccessStep(container) {
    const { state, completeOnboarding } = useOnboarding();

    const goal = USER_GOALS.find((item) => item.id === state.data.goal);
    const goalMessage = state.data.goal
        ? GOAL_MESSAGES[state.data.goal]?.success
        : 'Agora voce ja sabe para onde seu dinheiro esta indo.';

    const hasTransaction = !!state.data.transaction;
    const accountName = state.data.account?.nome || 'Sua conta';

    container.innerHTML = `
        <div class="lk-ob2-step lk-ob2-success" data-step="success">
            <div class="lk-ob2-confetti" id="confettiContainer"></div>

            <div class="lk-ob2-content">
                <div class="lk-ob2-success-icon">
                    <div class="lk-ob2-success-ring"></div>
                    <div class="lk-ob2-success-ring lk-ob2-success-ring-2"></div>
                    <div class="lk-ob2-success-check">
                        <i data-lucide="check"></i>
                    </div>
                </div>

                <h1 class="lk-ob2-success-title">Tudo pronto!</h1>

                <p class="lk-ob2-success-message">
                    ${goalMessage || 'Agora voce ja sabe para onde seu dinheiro esta indo.'}
                </p>

                <div class="lk-ob2-success-summary">
                    <div class="lk-ob2-summary-item">
                        <div class="lk-ob2-summary-icon lk-ob2-summary-account">
                            <i data-lucide="wallet"></i>
                        </div>
                        <div class="lk-ob2-summary-text">
                            <span class="lk-ob2-summary-label">Conta criada</span>
                            <span class="lk-ob2-summary-value">${escapeHtml(accountName)}</span>
                        </div>
                        <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                    </div>

                    ${hasTransaction ? `
                        <div class="lk-ob2-summary-item">
                            <div class="lk-ob2-summary-icon lk-ob2-summary-transaction">
                                <i data-lucide="receipt"></i>
                            </div>
                            <div class="lk-ob2-summary-text">
                                <span class="lk-ob2-summary-label">Primeiro lancamento</span>
                                <span class="lk-ob2-summary-value">
                                    ${escapeHtml(state.data.transaction?.descricao || 'Registrado')}
                                </span>
                            </div>
                            <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                        </div>
                    ` : ''}

                    ${goal ? `
                        <div class="lk-ob2-summary-item">
                            <div class="lk-ob2-summary-icon" style="background: ${goal.color}20; color: ${goal.color};">
                                <span>${goal.emoji}</span>
                            </div>
                            <div class="lk-ob2-summary-text">
                                <span class="lk-ob2-summary-label">Seu foco</span>
                                <span class="lk-ob2-summary-value">${escapeHtml(goal.title)}</span>
                            </div>
                            <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                        </div>
                    ` : ''}
                </div>

                <div class="lk-ob2-xp-earned">
                    <div class="lk-ob2-xp-badge">
                        <span class="lk-ob2-xp-value">+${hasTransaction ? 75 : 50}</span>
                        <span class="lk-ob2-xp-label">XP</span>
                    </div>
                    <span class="lk-ob2-xp-text">conquistados!</span>
                </div>

                <button class="lk-ob2-btn-primary lk-ob2-btn-success" id="btnGoToDashboard" type="button">
                    <span>Ver meu dashboard</span>
                    <i data-lucide="arrow-right"></i>
                </button>

                <div class="lk-ob2-redirect-hint" id="redirectHint" style="display: none;">
                    <div class="lk-ob2-spinner"></div>
                    <span>Preparando seu dashboard...</span>
                </div>
            </div>
        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    const dashboardBtn = container.querySelector('#btnGoToDashboard');
    const redirectHint = container.querySelector('#redirectHint');

    fireConfetti();
    playSuccessSound();

    if (dashboardBtn) {
        dashboardBtn.addEventListener('click', async () => {
            dashboardBtn.style.display = 'none';
            redirectHint.style.display = 'flex';

            await completeOnboarding();
        });

        setTimeout(() => {
            if (!redirectHint.style.display || redirectHint.style.display === 'none') {
                dashboardBtn.click();
            }
        }, 4000);
    }

    requestAnimationFrame(() => {
        container.querySelector('.lk-ob2-step')?.classList.add('visible');
    });
}

function fireConfetti() {
    if (typeof confetti !== 'function') {
        return;
    }

    const duration = 3000;
    const end = Date.now() + duration;
    const defaults = {
        startVelocity: 30,
        spread: 360,
        ticks: 60,
        zIndex: 99999,
        colors: ['#e67e22', '#f39c12', '#10b981', '#3b82f6', '#8b5cf6']
    };

    const interval = setInterval(() => {
        const timeLeft = end - Date.now();
        if (timeLeft <= 0) {
            clearInterval(interval);
            return;
        }

        const particleCount = 50 * (timeLeft / duration);

        try {
            confetti({
                ...defaults,
                particleCount,
                origin: { x: Math.random() * 0.3 + 0.1, y: Math.random() - 0.2 }
            });
            confetti({
                ...defaults,
                particleCount,
                origin: { x: Math.random() * 0.3 + 0.6, y: Math.random() - 0.2 }
            });
        } catch (error) {
            clearInterval(interval);
        }
    }, 200);

    setTimeout(() => {
        try {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { x: 0.5, y: 0.5 },
                colors: ['#e67e22', '#f39c12', '#10b981'],
                zIndex: 99999
            });
        } catch (error) {
            // ignore
        }
    }, 100);
}

function playSuccessSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(587.33, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime + 0.1);

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (error) {
        // Audio unavailable.
    }
}
