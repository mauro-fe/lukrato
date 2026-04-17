import { apiGet } from '../shared/api.js';
import { resolveBirthdayCheckEndpoint } from '../api/endpoints/preferences.js';

/**
 * Birthday Modal - Sistema de Celebração de Aniversário
 * 
 * Exibe um modal especial quando o usuário faz aniversário.
 * Usa localStorage para não repetir no mesmo dia.
 */

(function () {
    'use strict';

    const BirthdayModal = {
        storageKey: 'lukrato_birthday_shown',

        /**
         * Inicializa o sistema de aniversário
         */
        init: function () {
            // Aguarda DOM estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.checkBirthday());
            } else {
                // Pequeno delay para não competir com outras animações de carregamento
                setTimeout(() => this.checkBirthday(), 1500);
            }
        },

        /**
         * Verifica se hoje é aniversário do usuário
         */
        checkBirthday: async function () {
            try {
                // Verifica se já mostrou hoje
                if (this.wasShownToday()) {
                    return;
                }

                const data = await apiGet(resolveBirthdayCheckEndpoint());

                if (data.success && data.data?.is_birthday) {
                    this.showModal(data.data);
                    this.markAsShown();
                }
            } catch (error) {
                if (!(error instanceof TypeError && error.message.includes('NetworkError'))) {
                    console.error('[BirthdayModal] Erro ao verificar aniversário:', error);
                }
            }
        },

        /**
         * Verifica se o modal já foi mostrado hoje
         */
        wasShownToday: function () {
            const stored = localStorage.getItem(this.storageKey);
            if (!stored) return false;

            const today = new Date().toISOString().split('T')[0];
            return stored === today;
        },

        /**
         * Marca que o modal foi mostrado hoje
         */
        markAsShown: function () {
            const today = new Date().toISOString().split('T')[0];
            localStorage.setItem(this.storageKey, today);
        },

        /**
         * Exibe o modal de aniversário
         */
        showModal: function (userData) {
            const firstName = userData.first_name || 'Você';
            const age = userData.age || '';

            // Criar HTML do modal
            const modalHtml = `
                <div class="birthday-modal-overlay" id="birthdayModalOverlay">
                    <div class="birthday-modal">
                        <div class="birthday-modal-header">
                            <span class="birthday-emoji-main">🎂</span>
                            <h2>Feliz Aniversário, ${firstName}!</h2>
                        </div>
                        
                        <div class="birthday-modal-body">
                            ${age ? `
                            <div class="birthday-age-badge">
                                <span class="age-number">${age}</span>
                                <span class="age-text">anos de<br>conquistas!</span>
                            </div>
                            ` : ''}
                            
                            <p class="birthday-message">
                                Hoje é um dia muito especial e nós, do <strong>Lukrato</strong>, queremos celebrar com você! 🎉
                            </p>
                            
                            <div class="birthday-emoji-row">
                                <span>🎈</span>
                                <span>🎁</span>
                                <span>🎊</span>
                                <span>✨</span>
                                <span>🥳</span>
                            </div>
                            
                            <div class="birthday-motivation-card">
                                <p>
                                    Que este novo ciclo seja repleto de <span class="highlight">prosperidade financeira</span>, realizações e conquistas! Continue cuidando das suas finanças com a gente. <span class="highlight">Você está no caminho certo!</span> 💪
                                </p>
                            </div>
                        </div>
                        
                        <div class="birthday-modal-footer">
                            <button class="birthday-btn-celebrate" onclick="BirthdayModal.celebrate()">
                                🎉 Celebrar! 🎉
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Inserir no DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            window.LK?.modalSystem?.prepareOverlay('#birthdayModalOverlay', { scope: 'app' });

            // Ativar com pequeno delay para animação
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    document.getElementById('birthdayModalOverlay').classList.add('active');
                });
            });

            // Disparar confetti se disponível
            this.triggerConfetti();
        },

        /**
         * Fecha o modal
         */
        close: function () {
            const overlay = document.getElementById('birthdayModalOverlay');
            if (overlay) {
                overlay.classList.remove('active');
                setTimeout(() => overlay.remove(), 400);
            }
        },

        /**
         * Ação do botão "Celebrar!" - mais confetti e fecha
         */
        celebrate: function () {
            // Mais confetti!
            this.triggerConfetti(true);

            // Fecha após animação
            setTimeout(() => this.close(), 1500);
        },

        /**
         * Dispara efeito de confetti
         */
        triggerConfetti: function (intense = false) {
            if (typeof confetti !== 'function') return;

            const defaults = {
                spread: intense ? 180 : 70,
                ticks: intense ? 300 : 200,
                gravity: 0.8,
                decay: 0.94,
                startVelocity: intense ? 45 : 30,
                colors: ['#e67e22', '#f39c12', '#9b59b6', '#3498db', '#2ecc71', '#e74c3c']
            };

            function fire(particleRatio, opts) {
                confetti({
                    ...defaults,
                    ...opts,
                    particleCount: Math.floor((intense ? 250 : 150) * particleRatio)
                });
            }

            fire(0.25, { spread: 26, startVelocity: 55 });
            fire(0.2, { spread: 60 });
            fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
            fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
            fire(0.1, { spread: 120, startVelocity: 45 });

            if (intense) {
                // Confetti dos lados
                setTimeout(() => {
                    confetti({
                        ...defaults,
                        particleCount: 80,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0 }
                    });
                    confetti({
                        ...defaults,
                        particleCount: 80,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 }
                    });
                }, 200);
            }
        }
    };

    // Expõe globalmente para os onclick
    window.BirthdayModal = BirthdayModal;

    // Inicializa
    BirthdayModal.init();

})();
