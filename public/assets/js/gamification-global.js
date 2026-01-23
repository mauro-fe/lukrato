/**
 * Sistema Global de Gamificação
 * Carregado em todas as páginas para exibir conquistas e level ups
 */

(function () {
    'use strict';

    /**
     * Criar confetes animados
     */
    function createAchievementConfetti() {
        // Verificar se a biblioteca confetti está disponível
        if (typeof confetti !== 'function') {
            console.log('Biblioteca de confetes não carregada');
            return;
        }

        const duration = 3 * 1000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 99999 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            const particleCount = 50 * (timeLeft / duration);

            // Lançar confetes de diferentes posições
            try {
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
                }));
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
                }));
            } catch (error) {
                console.log('Erro ao criar confetes:', error);
                clearInterval(interval);
            }
        }, 250);
    }

    /**
     * Tocar som de conquista
     */
    function playAchievementSound() {
        try {
            // Obter base URL dinamicamente
            const baseUrl = window.BASE_URL || '/lukrato/public/';
            const audio = new Audio(baseUrl + 'assets/audio/success-fanfare-trumpets-6185.mp3');
            audio.volume = 0.5;
            audio.play().catch(err => {
                console.log('Não foi possível tocar o som:', err);
            });
        } catch (error) {
            console.log('Erro ao carregar o áudio:', error);
        }
    }

    /**
     * Escapar HTML para evitar problemas com caracteres especiais
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Notificar conquista desbloqueada
     */
    window.notifyAchievementUnlocked = function (achievement) {
        // Validar se achievement existe e tem os campos necessários
        if (!achievement || typeof achievement !== 'object') {
            console.error('Conquista inválida:', achievement);
            return;
        }

        console.log('🎉 Conquista desbloqueada:', achievement);

        // Garantir valores padrão e escapar HTML
        const ach = {
            name: escapeHtml(achievement.name || 'Conquista Desbloqueada'),
            description: escapeHtml(achievement.description || ''),
            icon: achievement.icon || '🏆',
            points_reward: parseInt(achievement.points_reward || achievement.points || 0)
        };

        // Tocar som imediatamente
        playAchievementSound();

        // Confetes estouram 100ms depois (sincronizado com o som)
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);

        // Verificar se SweetAlert2 está disponível
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '🎉 Conquista Desbloqueada!',
                html: `
                    <div class="achievement-unlock-animation">
                        <div class="achievement-icon-big">${ach.icon}</div>
                        <h2>${ach.name}</h2>
                        <p>${ach.description}</p>
                        <p class="achievement-points-reward">
                            <i class="fas fa-star"></i> +${ach.points_reward} pontos
                        </p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: '🚀 Continuar!',
                customClass: {
                    popup: 'achievement-unlock-modal',
                    confirmButton: 'btn btn-primary'
                },
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut'
                }
            });
        } else {
            // Fallback se SweetAlert2 não estiver disponível
            alert(`🎉 Conquista Desbloqueada!\n\n${ach.name}\n${ach.description}\n\n+${ach.points_reward} pontos`);
        }
    };

    /**
     * Notificar subida de nível
     */
    window.notifyLevelUp = function (newLevel) {
        console.log('⭐ Subiu de nível:', newLevel);

        // Tocar som
        playAchievementSound();

        // Confetes
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '⭐ Subiu de Nível!',
                html: `
                    <div class="level-up-animation">
                        <div class="level-badge-big">
                            <span class="level-number">${newLevel}</span>
                        </div>
                        <h2>Parabéns!</h2>
                        <p>Você alcançou o nível ${newLevel}!</p>
                        <p class="level-up-message">Continue assim e alcance novos patamares!</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: '🎯 Vamos lá!',
                customClass: {
                    popup: 'level-up-modal',
                    confirmButton: 'btn btn-primary'
                },
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut'
                }
            });
        } else {
            alert(`⭐ Subiu de Nível!\n\nVocê alcançou o nível ${newLevel}!\n\nContinue assim e alcance novos patamares!`);
        }
    };

    /**
     * Notificar ganho de pontos (toast rápido)
     */
    window.notifyPointsGained = function (points) {
        if (points <= 0) return;

        console.log('✨ Ganhou pontos:', points);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `+${points} pontos! ✨`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
    };

    /**
     * Utilitários globais de gamificação
     */

    // Níveis expandidos de 1 a 15
    window.GAMIFICATION = {
        MAX_LEVEL: 15,
        levelThresholds: {
            1: 0,
            2: 300,
            3: 500,
            4: 700,
            5: 1000,
            6: 1500,
            7: 2200,
            8: 3000,
            9: 4000,
            10: 5500,
            11: 7500,
            12: 10000,
            13: 15000,
            14: 25000,
            15: 50000
        },

        /**
         * Obter threshold de pontos para um nível
         */
        getLevelThreshold: function (level) {
            return this.levelThresholds[level] !== undefined ? this.levelThresholds[level] : this.levelThresholds[15];
        },

        /**
         * Calcular progresso entre níveis
         */
        calculateProgress: function (currentLevel, totalPoints) {
            const isMaxLevel = currentLevel >= this.MAX_LEVEL;
            if (isMaxLevel) {
                return { percentage: 100, current: totalPoints, needed: totalPoints, isMaxLevel: true };
            }

            const currentLevelPoints = this.getLevelThreshold(currentLevel);
            const nextLevelPoints = this.getLevelThreshold(currentLevel + 1);
            const pointsInCurrentLevel = totalPoints - currentLevelPoints;
            const pointsNeededForNextLevel = nextLevelPoints - currentLevelPoints;
            const percentage = (pointsInCurrentLevel / pointsNeededForNextLevel) * 100;

            return {
                percentage: Math.min(100, Math.max(0, percentage)),
                current: pointsInCurrentLevel,
                needed: pointsNeededForNextLevel,
                isMaxLevel: false
            };
        },

        /**
         * Formatar números
         */
        formatNumber: function (num) {
            return new Intl.NumberFormat('pt-BR').format(num);
        },

        /**
         * Formatar datas
         */
        formatDate: function (dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(date);
        },

        /**
         * Obter cor para categoria
         */
        getCategoryColor: function (category) {
            const categoryColors = {
                'lancamentos': '#3498db',
                'categorias': '#9b59b6',
                'metas': '#2ecc71',
                'economias': '#f39c12',
                'conquistas': '#e67e22',
                'streak': '#e74c3c',
                'planejamento': '#1abc9c'
            };
            return categoryColors[category] || '#95a5a6';
        }
    };

    /**
     * Verificar conquistas pendentes de notificação
     * Chamado ao carregar qualquer página
     */
    async function checkPendingAchievements() {
        try {
            const baseUrl = window.BASE_URL || '/lukrato/public/';
            const response = await fetch(`${baseUrl}api/gamification/achievements/pending`, {
                credentials: 'same-origin'
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.success && data.data && data.data.pending && data.data.pending.length > 0) {
                const pending = data.data.pending;
                console.log(`🎮 [GLOBAL] ${pending.length} conquista(s) pendente(s) de notificação`);

                // Mostrar cada conquista com um pequeno delay entre elas
                for (let i = 0; i < pending.length; i++) {
                    setTimeout(() => {
                        window.notifyAchievementUnlocked(pending[i]);
                    }, i * 3500); // 3.5 segundos entre cada uma
                }

                // Marcar como vistas após exibir
                const achievementIds = pending.map(a => a.id);
                markAchievementsSeen(achievementIds);
            }
        } catch (error) {
            console.log('Não foi possível verificar conquistas pendentes:', error);
        }
    }

    /**
     * Marcar conquistas como vistas
     */
    async function markAchievementsSeen(achievementIds) {
        try {
            const baseUrl = window.BASE_URL || '/lukrato/public/';

            // Obter CSRF token
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.content : '';

            await fetch(`${baseUrl}api/gamification/achievements/mark-seen`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ achievement_ids: achievementIds })
            });
        } catch (error) {
            console.log('Erro ao marcar conquistas como vistas:', error);
        }
    }

    // Verificar conquistas pendentes quando a página carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Pequeno delay para garantir que tudo carregou
            setTimeout(checkPendingAchievements, 1000);
        });
    } else {
        setTimeout(checkPendingAchievements, 1000);
    }

    console.log('✅ Sistema de gamificação global carregado');

})();
