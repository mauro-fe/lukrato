/**
 * Sistema Global de Gamificação
 * Carregado em todas as páginas para exibir conquistas e level ups
 */

(function () {
    'use strict';

    // Mapeamento de cores para ícones de conquistas
    function getAchievementIconColor(icon) {
        const colors = {
            'target': '#ef4444', 'flame': '#f97316', 'zap': '#eab308',
            'calendar': '#3b82f6', 'bar-chart-3': '#06b6d4', 'palette': '#a855f7',
            'user-check': '#22c55e', 'coins': '#eab308', 'hash': '#6366f1',
            'graduation-cap': '#3b82f6', 'star': '#f59e0b', 'crown': '#f59e0b',
            'gem': '#a855f7', 'trophy': '#f59e0b', 'award': '#f59e0b',
            'sparkles': '#ec4899', 'file-text': '#64748b', 'library': '#92400e',
            'landmark': '#3b82f6', 'sparkle': '#ec4899', 'orbit': '#6366f1',
            'banknote': '#22c55e', 'piggy-bank': '#ec4899', 'building-2': '#64748b',
            'trending-up': '#22c55e', 'crosshair': '#ef4444', 'medal': '#f59e0b',
            'folder-open': '#f59e0b', 'folders': '#f59e0b', 'check-circle': '#22c55e',
            'credit-card': '#3b82f6', 'receipt': '#14b8a6', 'calendar-check': '#22c55e',
            'cake': '#ec4899', 'shield-check': '#22c55e', 'wand-sparkles': '#a855f7',
            'sunrise': '#f97316', 'moon': '#6366f1', 'tree-pine': '#22c55e',
            'party-popper': '#ef4444', 'swords': '#64748b', 'rocket': '#ef4444',
            'handshake': '#3b82f6', 'users': '#3b82f6', 'megaphone': '#f97316',
            'lock': '#94a3b8', 'check': '#22c55e'
        };
        return colors[icon] || '#f97316';
    }

    /**
     * Criar confetes animados
     */
    function createAchievementConfetti() {
        // Verificar se a biblioteca confetti está disponível
        if (typeof confetti !== 'function') {
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
            const baseUrl = window.BASE_URL || window.LK?.getBase?.() || '/';
            const audio = new Audio(baseUrl + 'assets/audio/success-fanfare-trumpets-6185.mp3');
            audio.volume = 0.5;
            audio.play().catch(err => {
            });
        } catch (error) {
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
     * Processar múltiplas conquistas (usa sistema de fila se houver mais de uma)
     */
    window.notifyMultipleAchievements = function (achievements) {
        if (!achievements || !Array.isArray(achievements) || achievements.length === 0) {
            return;
        }

        ('  [notifyMultipleAchievements] Recebeu', achievements.length, 'conquista(s)');

        if (achievements.length === 1) {
            // Apenas uma conquista - exibir diretamente
            window.notifyAchievementUnlocked(achievements[0]);
        } else {
            // Múltiplas conquistas - usar sistema de fila

            if (window.gamificationPaused === true) {
                // Se pausada, adicionar à fila pendente
                if (!window.pendingAchievements) window.pendingAchievements = [];
                achievements.forEach(ach => window.pendingAchievements.push(ach));
            } else {
                // Criar fila combinada e mostrar sequencialmente
                window.combinedQueue = achievements.map(ach => ({ type: 'achievement', data: ach }));
                showNextQueuedItem();
            }
        }
    };

    /**
     * Notificar conquista desbloqueada
     */
    window.notifyAchievementUnlocked = function (achievement) {

        // VERIFICAR SE GAMIFICAÇÃO ESTÁ PAUSADA (onboarding em andamento)
        if (window.gamificationPaused === true) {
            if (!window.pendingAchievements) window.pendingAchievements = [];
            window.pendingAchievements.push(achievement);
            return;
        }

        // Validar se achievement existe e tem os campos necessários
        if (!achievement || typeof achievement !== 'object') {
            console.error('Conquista inválida:', achievement);
            return;
        }

        // Garantir valores padrão e escapar HTML
        const ach = {
            name: escapeHtml(achievement.name || 'Conquista Desbloqueada'),
            description: escapeHtml(achievement.description || ''),
            icon: achievement.icon || 'trophy',
            points_reward: parseInt(achievement.points_reward || achievement.points || 0)
        };

        // Tocar som imediatamente
        playAchievementSound();

        // Confetes estouram 100ms depois (sincronizado com o som)
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);


        if (typeof Swal !== 'undefined') {

            try {
                // Fechar qualquer modal Bootstrap que possa estar aberto
                const bootstrapModals = document.querySelectorAll('.modal.show');
                bootstrapModals.forEach(modal => {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                });

                // Aguardar um momento para modais fecharem
                setTimeout(() => {
                    Swal.fire({
                        title: 'Conquista Desbloqueada!',
                        html: `
                            <div class="achievement-unlock-animation">
                                <div class="achievement-icon-big" style="color:${getAchievementIconColor(ach.icon)}"><i data-lucide="${ach.icon}"></i></div>
                                <h2>${ach.name}</h2>
                                <p>${ach.description}</p>
                                <p class="achievement-points-reward">
                                    <i data-lucide="star"></i> +${ach.points_reward} pontos
                                </p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Continuar!',
                        customClass: {
                            popup: 'achievement-unlock-modal',
                            confirmButton: 'btn btn-primary',
                            container: 'swal2-achievement-container'
                        },
                        showClass: {
                            popup: 'animate__animated animate__bounceIn'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOut'
                        },
                        backdrop: true,
                        allowOutsideClick: false
                    }).then(() => {
                        // Marcar conquista como vista
                        if (achievement.id) {
                            markAchievementsSeen([achievement.id]);
                        }
                        // Renderizar ícones Lucide
                        if (window.lucide) lucide.createIcons();
                    });
                }, 300);
            } catch (error) {
                console.error('[notifyAchievementUnlocked] Erro ao exibir Swal:', error);
                alert(`Conquista Desbloqueada!\n\n${ach.name}\n${ach.description}\n\n+${ach.points_reward} pontos`);
            }
        } else {
            console.warn('[notifyAchievementUnlocked] Swal ainda não carregado, tentando novamente em 500ms...');
            // Tentar novamente após 500ms
            setTimeout(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Conquista Desbloqueada!',
                        html: `
                            <div class="achievement-unlock-animation">
                                <div class="achievement-icon-big" style="color:${getAchievementIconColor(ach.icon)}"><i data-lucide="${ach.icon}"></i></div>
                                <h2>${ach.name}</h2>
                                <p>${ach.description}</p>
                                <p class="achievement-points-reward">
                                    <i data-lucide="star"></i> +${ach.points_reward} pontos
                                </p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Continuar!',
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
                    }).then(() => {
                        // Marcar conquista como vista
                        if (achievement.id) {
                            markAchievementsSeen([achievement.id]);
                        }
                    });
                } else {
                    console.error('[notifyAchievementUnlocked] Swal NÃO disponível mesmo após 500ms!');
                    alert(`Conquista Desbloqueada!\n\n${ach.name}\n${ach.description}\n\n+${ach.points_reward} pontos`);
                    // Marcar como vista mesmo com fallback
                    if (achievement.id) {
                        markAchievementsSeen([achievement.id]);
                    }
                }
            }, 500);
        }
    };

    /**
     * Notificar subida de nível
     */
    window.notifyLevelUp = function (newLevel) {
        // VERIFICAR SE GAMIFICAÇÃO ESTÁ PAUSADA (onboarding em andamento)
        if (window.gamificationPaused) {
            if (!window.pendingLevelUps) window.pendingLevelUps = [];
            window.pendingLevelUps.push(newLevel);
            return;
        }

        // Tocar som
        playAchievementSound();

        // Confetes
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Subiu de Nível!',
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
                confirmButtonText: 'Vamos lá!',
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
            alert(`Subiu de Nível!\n\nVocê alcançou o nível ${newLevel}!\n\nContinue assim e alcance novos patamares!`);
        }
    };

    /**
     * Notificar level up com callback quando fechar
     */
    function notifyLevelUpWithCallback(newLevel, onClose) {
        // Tocar som
        playAchievementSound();

        // Confetes
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Subiu de Nível!',
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
                confirmButtonText: 'Vamos lá!',
                customClass: {
                    popup: 'level-up-modal',
                    confirmButton: 'btn btn-primary'
                },
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut'
                },
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                // Quando fechar, chamar callback para mostrar próximo item
                if (onClose) {
                    setTimeout(onClose, 300); // Pequeno delay para transição suave
                }
            });
        } else {
            alert(`Subiu de Nível!\n\nVocê alcançou o nível ${newLevel}!\n\nContinue assim e alcance novos patamares!`);
            if (onClose) onClose();
        }
    }

    /**
     * Notificar ganho de pontos (toast rápido)
     */
    window.notifyPointsGained = function (points) {
        if (points <= 0) return;


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
    let isCheckingPending = false; // Evitar verificações simultâneas

    async function checkPendingAchievements() {
        // Evitar verificações duplicadas
        if (isCheckingPending) {
            return;
        }

        // Não verificar se gamificação está pausada
        if (window.gamificationPaused === true) {
            return;
        }

        isCheckingPending = true;

        try {
            const baseUrl = window.BASE_URL || window.LK?.getBase?.() || '/';
            const response = await fetch(`${baseUrl}api/gamification/achievements/pending`, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                isCheckingPending = false;
                return;
            }

            const data = await response.json();

            if (data.success && data.data && data.data.pending && data.data.pending.length > 0) {
                const pending = data.data.pending;

                // Guardar IDs para marcar como vistas APÓS exibição dos modais
                // (o markSeen é chamado pelo notifyAchievementUnlocked no .then() de cada modal)
                // Marcar como vistas apenas para evitar duplicação em outras abas
                // mas somente após breve delay para garantir que os modais foram criados
                const achievementIds = pending.map(a => a.id);

                // Exibir conquistas sequencialmente usando o sistema de fila
                if (pending.length === 1) {
                    // Apenas uma conquista - notifyAchievementUnlocked já chama markSeen no .then()
                    window.notifyAchievementUnlocked(pending[0]);
                } else {
                    // Múltiplas conquistas - usar sistema de fila
                    window.combinedQueue = pending.map(ach => ({ type: 'achievement', data: ach }));
                    showNextQueuedItem();
                }

                // Marcar como vistas após 3s de segurança (backup caso o modal falhe)
                setTimeout(() => {
                    markAchievementsSeen(achievementIds);
                }, 3000);
            }
        } catch (error) {
            if (error instanceof TypeError && error.message.includes('NetworkError')) return;
            console.error('🎮 [GAMIFICATION] Erro ao verificar conquistas pendentes:', error);
        } finally {
            isCheckingPending = false;
        }
    }

    // Expor função para uso externo (opcional)
    window.checkPendingAchievements = checkPendingAchievements;

    /**
     * Marcar conquistas como vistas
     */
    async function markAchievementsSeen(achievementIds) {
        try {
            const baseUrl = window.BASE_URL || window.LK?.getBase?.() || '/';

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
            console.error('🎮 [GAMIFICATION] Erro ao marcar conquistas como vistas:', error);
        }
    }

    // ====================================================================
    // VERIFICAÇÃO DE RECOMPENSAS DE INDICAÇÃO - Modal de parabéns
    // ====================================================================
    let isCheckingReferralRewards = false;

    async function checkReferralRewards() {
        if (isCheckingReferralRewards) return;
        if (window.gamificationPaused === true) return;

        isCheckingReferralRewards = true;

        try {
            const baseUrl = window.BASE_URL || window.LK?.getBase?.() || '/';
            const response = await fetch(`${baseUrl}api/notificacoes/referral-rewards`, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                isCheckingReferralRewards = false;
                return;
            }

            const data = await response.json();

            if (data.success && data.data && data.data.rewards && data.data.rewards.length > 0) {
                const rewards = data.data.rewards;

                // Mostrar modal para cada recompensa
                for (const reward of rewards) {
                    await showReferralRewardModal(reward);
                }

                // Marcar como vistas
                const ids = rewards.map(r => r.id);
                await markReferralRewardsSeen(ids);
            }
        } catch (error) {
            if (error instanceof TypeError && error.message.includes('NetworkError')) return;
            console.error('🎁 [REFERRAL] Erro ao verificar recompensas:', error);
        } finally {
            isCheckingReferralRewards = false;
        }
    }

    /**
     * Mostra modal de parabéns para recompensa de indicação
     */
    function showReferralRewardModal(reward) {
        return new Promise((resolve) => {
            // Tocar som
            playAchievementSound();

            // Confetes
            setTimeout(() => {
                createAchievementConfetti();
            }, 100);

            const isReferrer = reward.tipo === 'referral_referrer';
            const icon = isReferrer ? '<i data-lucide="gift"></i>' : '<i data-lucide="party-popper"></i>';
            const buttonText = isReferrer ? 'Continuar indicando!' : 'Aproveitar!';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `${reward.titulo}`,
                    html: `
                        <div class="referral-reward-animation">
                            <div class="referral-icon-big">${isReferrer ? '<i data-lucide="users"></i>' : '<i data-lucide="sparkles"></i>'}</div>
                            <p class="referral-message">${reward.mensagem}</p>
                            <div class="referral-pro-badge">
                                <i data-lucide="gem"></i> Acesso PRO ativado!
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: buttonText,
                    customClass: {
                        popup: 'referral-reward-modal',
                        confirmButton: 'btn btn-primary'
                    },
                    showClass: {
                        popup: 'animate__animated animate__bounceIn'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOut'
                    },
                    allowOutsideClick: false
                }).then(() => {
                    resolve();
                });
            } else {
                alert(`${icon} ${reward.titulo}\n\n${reward.mensagem}`);
                resolve();
            }
        });
    }

    /**
     * Marca recompensas de indicação como vistas
     */
    async function markReferralRewardsSeen(ids) {
        try {
            const baseUrl = window.BASE_URL || window.LK?.getBase?.() || '/';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.content : '';

            await fetch(`${baseUrl}api/notificacoes/referral-rewards/seen`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ ids: ids })
            });
        } catch (error) {
            console.error('🎁 [REFERRAL] Erro ao marcar recompensas como vistas:', error);
        }
    }

    // ====================================================================
    // VERIFICAÇÃO DE CONQUISTAS PENDENTES - Verificar ao carregar página
    // ====================================================================
    // Isto garante que conquistas desbloqueadas em outros contextos
    // (como verificação de email, ações em background) sejam notificadas
    function initPendingAchievementsCheck() {
        const onboardingInProgress = localStorage.getItem('lukrato_onboarding_in_progress') === 'true';

        // Não verificar durante onboarding
        if (onboardingInProgress || window.gamificationPaused === true) {
            return;
        }

        // Verificar recompensas de indicação primeiro (após 1 segundo)
        setTimeout(checkReferralRewards, 1000);

        // Verificar conquistas pendentes após 2.5 segundos (depois das recompensas)
        setTimeout(checkPendingAchievements, 2500);
    }

    // Iniciar verificação quando página carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPendingAchievementsCheck);
    } else {
        initPendingAchievementsCheck();
    }

    /**
     * Mostrar conquistas que foram pausadas pelo onboarding
     */
    window.showPendingAchievements = function () {

        // Fazer cópias e limpar arrays IMEDIATAMENTE para evitar duplicação
        const achievementsCopy = window.pendingAchievements ? [...window.pendingAchievements] : [];
        const levelUpsCopy = window.pendingLevelUps ? [...window.pendingLevelUps] : [];
        window.pendingAchievements = [];
        window.pendingLevelUps = [];

        // Criar uma fila combinada de conquistas e level ups
        window.combinedQueue = [];

        // Adicionar conquistas
        if (achievementsCopy.length > 0) {
            achievementsCopy.forEach(achievement => {
                window.combinedQueue.push({ type: 'achievement', data: achievement });
            });
        }

        // Adicionar level ups
        if (levelUpsCopy.length > 0) {
            levelUpsCopy.forEach(level => {
                window.combinedQueue.push({ type: 'levelup', data: level });
            });
        }

        if (window.combinedQueue.length === 0) {
            return;
        }


        // Mostrar o primeiro item da fila combinada
        showNextQueuedItem();
    };

    /**
     * Mostrar a próxima conquista da fila (uma por vez)
     */
    function showNextPendingAchievement() {
        if (!window.pendingAchievements || window.pendingAchievements.length === 0) {
            return;
        }

        // Pegar a primeira conquista da fila
        const achievement = window.pendingAchievements.shift();

        // Mostrar a conquista com callback para mostrar a próxima
        notifyAchievementWithCallback(achievement, showNextPendingAchievement);
    }

    /**
     * Mostrar o próximo item da fila combinada (conquista ou level up)
     */
    function showNextQueuedItem() {
        if (!window.combinedQueue || window.combinedQueue.length === 0) {
            return;
        }

        // Pegar o primeiro item da fila
        const item = window.combinedQueue.shift();

        if (item.type === 'achievement') {
            // Mostrar conquista com callback
            notifyAchievementWithCallback(item.data, showNextQueuedItem);
        } else if (item.type === 'levelup') {
            // Mostrar level up com callback
            notifyLevelUpWithCallback(item.data, showNextQueuedItem);
        }
    }

    /**
     * Notificar conquista com callback quando fechar
     */
    function notifyAchievementWithCallback(achievement, onClose) {
        // Validar se achievement existe e tem os campos necessários
        if (!achievement || typeof achievement !== 'object') {
            console.error('Conquista inválida:', achievement);
            if (onClose) onClose();
            return;
        }

        // Garantir valores padrão e escapar HTML
        const ach = {
            name: escapeHtml(achievement.name || 'Conquista Desbloqueada'),
            description: escapeHtml(achievement.description || ''),
            icon: achievement.icon || 'trophy',
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
                title: 'Conquista Desbloqueada!',
                html: `
                    <div class="achievement-unlock-animation">
                        <div class="achievement-icon-big" style="color:${getAchievementIconColor(ach.icon)}"><i data-lucide="${ach.icon}"></i></div>
                        <h2>${ach.name}</h2>
                        <p>${ach.description}</p>
                        <p class="achievement-points-reward">
                            <i data-lucide="star"></i> +${ach.points_reward} pontos
                        </p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Continuar!',
                customClass: {
                    popup: 'achievement-unlock-modal',
                    confirmButton: 'btn btn-primary'
                },
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut'
                },
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                // Quando fechar, chamar callback para mostrar próxima conquista
                if (onClose) {
                    setTimeout(onClose, 300); // Pequeno delay para transição suave
                }
            });
        } else {
            // Fallback se SweetAlert2 não estiver disponível
            alert(`Conquista Desbloqueada!\n\n${ach.name}\n${ach.description}\n\n+${ach.points_reward} pontos`);
            if (onClose) onClose();
        }
    }
    // ====================================================================
    // INICIALIZAÇÃO AUTOMÁTICA - Verificar conquistas pendentes ao carregar
    // ====================================================================
    // Verificar se onboarding está completo ao carregar a página
    window.addEventListener('DOMContentLoaded', function () {
        const onboardingCompleted = localStorage.getItem('lukrato_onboarding_completed') === 'true';
        const onboardingInProgress = localStorage.getItem('lukrato_onboarding_in_progress') === 'true';


        if (onboardingCompleted && !onboardingInProgress) {
            // Garantir que gamificação não está pausada
            window.gamificationPaused = false;

            // Se houver conquistas pendentes, exibir após 1 segundo
            if (window.pendingAchievements && window.pendingAchievements.length > 0) {
                setTimeout(() => {
                    if (typeof window.showPendingAchievements === 'function') {
                        window.showPendingAchievements();
                    }
                }, 1000);
            }
        }
    });
})();
