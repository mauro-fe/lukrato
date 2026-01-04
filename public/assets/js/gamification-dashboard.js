/**
 * Gamification System - Dashboard
 * Gerencia carregamento e exibição de dados de gamificação
 */

console.log('🎮 [GAMIFICATION] Script carregado!');

(function () {
    'use strict';

    console.log('🎮 [GAMIFICATION] Iniciando IIFE...');

    // Ler BASE_URL do meta tag
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    const BASE = baseUrlMeta ? baseUrlMeta.content : (window.BASE_URL || '/');

    console.log('🎮 [GAMIFICATION] BASE:', BASE);

    let isPro = false;
    let currentProgress = {};

    /**
     * Inicializar sistema de gamificação
     */
    function initGamification() {
        console.log('🎮 [GAMIFICATION] initGamification() chamada!');

        // Verificar se estamos na página correta
        const gamificationSection = document.querySelector('.gamification-section');
        if (!gamificationSection) {
            console.log('🎮 [GAMIFICATION] Seção não encontrada, script não será executado');
            return; // Não está na página do dashboard
        }

        console.log('🎮 [GAMIFICATION] Inicializando sistema...');
        console.log('🎮 [GAMIFICATION] BASE_URL:', BASE);

        loadGamificationProgress();
        loadGamificationStats();
        loadAchievements();

        // Event listeners
        const btnProUpgrade = document.querySelector('.btn-pro-upgrade');

        if (btnProUpgrade) {
            btnProUpgrade.addEventListener('click', showProUpgrade);
        }
    }

    /**
     * Carregar progresso do usuário
     */
    async function loadGamificationProgress() {
        console.log('🎮 [GAMIFICATION] loadGamificationProgress() iniciada');
        console.log('🎮 [GAMIFICATION] URL:', `${BASE}api/gamification/progress`);

        try {
            const response = await fetch(`${BASE}api/gamification/progress`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            console.log('🎮 [GAMIFICATION] Response status:', response.status);
            console.log('🎮 [GAMIFICATION] Response ok:', response.ok);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.warn('🎮 [GAMIFICATION] Erro ao carregar progresso:', errorData.message || response.statusText);
                return;
            }

            const data = await response.json();
            console.log('🎮 [GAMIFICATION] Data recebida:', data);

            // Aceitar tanto 'success' quanto 'status'
            const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

            if (isSuccess && data.data) {
                console.log('🎮 [GAMIFICATION] Chamando updateProgressUI...');
                currentProgress = data.data;
                isPro = data.data.is_pro;
                updateProgressUI(data.data);
            } else {
                console.warn('🎮 [GAMIFICATION] Data inválida:', data);
            }
        } catch (error) {
            console.error('🎮 [GAMIFICATION] ERRO ao carregar progresso:', error);
            // Não mostrar erro para o usuário, apenas log no console
        }
    }

    /**
     * Atualizar UI com dados de progresso
     */
    function updateProgressUI(progress) {
        console.log('=== UPDATE PROGRESS UI ===');
        console.log('Progress recebido:', progress);

        // Badge Pro
        const proBadge = document.getElementById('proBadge');
        if (proBadge) {
            proBadge.style.display = progress.is_pro ? 'inline-flex' : 'none';
        }

        // CTA Pro (apenas para free)
        const proCTA = document.getElementById('proCTA');
        if (proCTA) {
            proCTA.style.display = progress.is_pro ? 'none' : 'block';
        }

        // Nível
        const levelBadge = document.getElementById('userLevel');
        if (levelBadge) {
            const span = levelBadge.querySelector('span');
            if (span) span.textContent = `Nível ${progress.current_level}`;
        }

        // Barra de progresso de nível
        const progressBar = document.getElementById('levelProgressBar');
        const progressPoints = document.getElementById('levelProgressPoints');
        const progressText = document.getElementById('levelProgressText');

        console.log('Elementos encontrados:', {
            progressBar: progressBar ? 'SIM' : 'NÃO',
            progressPoints: progressPoints ? 'SIM' : 'NÃO',
            progressText: progressText ? 'SIM' : 'NÃO'
        });

        if (progressBar && progressPoints) {
            const percentage = progress.progress_percentage || 0;
            const nextLevelPoints = getLevelThreshold(progress.current_level + 1);
            const currentLevelPoints = getLevelThreshold(progress.current_level);
            const neededPoints = nextLevelPoints - currentLevelPoints;
            let currentInLevel = progress.total_points - currentLevelPoints;

            // Proteção contra valores negativos (inconsistência de dados)
            if (currentInLevel < 0) {
                console.warn('🎮 [GAMIFICATION] Inconsistência detectada: pontos negativos no nível');
                currentInLevel = 0;
            }

            console.log('Cálculos:', {
                total_points: progress.total_points,
                current_level: progress.current_level,
                currentLevelPoints: currentLevelPoints,
                nextLevelPoints: nextLevelPoints,
                currentInLevel: currentInLevel,
                neededPoints: neededPoints,
                percentage: percentage
            });

            progressBar.style.width = `${Math.max(0, percentage)}%`;
            progressPoints.textContent = `${currentInLevel} / ${neededPoints} pontos`;

            console.log('Texto atualizado para:', `${currentInLevel} / ${neededPoints} pontos`);

            if (progressText) {
                if (progress.current_level >= 8) {
                    progressText.textContent = '🎉 Nível máximo alcançado!';
                } else {
                    const remaining = progress.points_to_next_level || 0;
                    progressText.textContent = `Faltam ${remaining} pontos para o próximo nível`;
                }
            }
        } else {
            console.error('ERRO: Elementos não encontrados!');
        }

        // Streak
        const streakDays = document.getElementById('streakDays');
        if (streakDays) {
            streakDays.textContent = progress.current_streak || 0;

            // Animação se streak > 3
            if (progress.current_streak > 3) {
                streakDays.classList.add('streak-fire');
            }
        }

        // Proteção de streak (apenas Pro)
        const streakProtection = document.getElementById('streakProtection');
        if (streakProtection) {
            streakProtection.style.display = progress.streak_protection_available ? 'flex' : 'none';
        }

        // Pontos totais
        const pontosTotal = document.getElementById('pontosTotal');
        if (pontosTotal) {
            pontosTotal.textContent = formatNumber(progress.total_points || 0);
        }
    }

    /**
     * Carregar estatísticas do usuário
     */
    async function loadGamificationStats() {
        try {
            const response = await fetch(`${BASE}api/gamification/stats`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.warn('Erro ao carregar estatísticas:', errorData.message || response.statusText);
                return;
            }

            const data = await response.json();
            const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
            if (isSuccess && data.data) {
                updateStatsUI(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    /**
     * Atualizar UI de estatísticas
     */
    function updateStatsUI(stats) {
        const totalLancamentos = document.getElementById('totalLancamentos');
        const totalCategorias = document.getElementById('totalCategorias');
        const mesesAtivos = document.getElementById('mesesAtivos');

        if (totalLancamentos) totalLancamentos.textContent = formatNumber(stats.total_lancamentos);
        if (totalCategorias) totalCategorias.textContent = formatNumber(stats.total_categorias);
        if (mesesAtivos) mesesAtivos.textContent = formatNumber(stats.meses_ativos);

        // Atualizar card de organização
        updateOrganizationProgress(stats);
    }

    /**
     * Atualizar progresso de organização
     */
    function updateOrganizationProgress(stats) {
        const organizationBar = document.getElementById('organizationBar');
        const organizationPercentage = document.getElementById('organizationPercentage');
        const organizationText = document.getElementById('organizationText');

        // Cálculo baseado em lançamentos e categorias
        const launchesTarget = 50;
        const categoriesTarget = 10;

        const launchesProgress = Math.min(100, (stats.total_lancamentos / launchesTarget) * 100);
        const categoriesProgress = Math.min(100, (stats.total_categorias / categoriesTarget) * 100);
        const overallProgress = (launchesProgress + categoriesProgress) / 2;

        if (organizationBar) {
            organizationBar.style.width = `${overallProgress}%`;
        }

        if (organizationPercentage) {
            organizationPercentage.textContent = `${Math.round(overallProgress)}%`;
        }

        if (organizationText) {
            if (overallProgress >= 100) {
                organizationText.textContent = '🎉 Parabéns! Você está super organizado!';
            } else if (overallProgress >= 75) {
                organizationText.textContent = 'Muito bem! Continue assim!';
            } else if (overallProgress >= 50) {
                organizationText.textContent = 'Bom progresso! Continue registrando!';
            } else {
                organizationText.textContent = 'Continue registrando seus lançamentos!';
            }
        }
    }

    /**
     * Carregar conquistas
     */
    async function loadAchievements() {
        try {
            const response = await fetch(`${BASE}api/gamification/achievements`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.warn('Erro ao carregar conquistas:', errorData.message || response.statusText);
                return;
            }

            const data = await response.json();
            const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
            if (isSuccess && data.data) {
                updateAchievementsUI(data.data.achievements);
            }
        } catch (error) {
            console.error('Erro ao carregar conquistas:', error);
        }
    }

    /**
     * Atualizar UI de conquistas
     */
    function updateAchievementsUI(achievements) {
        const badgesGrid = document.getElementById('badgesGrid');
        if (!badgesGrid) return;

        // Mostrar apenas as primeiras 6 conquistas no dashboard
        const displayAchievements = achievements.slice(0, 6);

        badgesGrid.innerHTML = '';

        displayAchievements.forEach(achievement => {
            const badgeItem = document.createElement('div');
            badgeItem.className = `badge-item ${achievement.unlocked ? 'unlocked' : 'locked'}`;

            if (achievement.is_pro_only && !isPro) {
                badgeItem.classList.add('pro-only');
            }

            badgeItem.title = achievement.description;

            badgeItem.innerHTML = `
                <div class="badge-icon">${achievement.icon}</div>
                <div class="badge-name">${achievement.name}</div>
                ${achievement.is_pro_only ? '<div class="badge-pro-tag">PRO</div>' : ''}
                ${achievement.unlocked ? '<div class="badge-unlocked-check">✓</div>' : ''}
            `;

            badgeItem.addEventListener('click', () => showAchievementDetail(achievement));

            badgesGrid.appendChild(badgeItem);
        });
    }

    /**
     * Mostrar detalhes de uma conquista
     */
    function showAchievementDetail(achievement) {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 não está carregado');
            return;
        }

        Swal.fire({
            title: `${achievement.icon} ${achievement.name}`,
            html: `
                <p class="achievement-description">${achievement.description}</p>
                <p class="achievement-points">
                    <i class="fas fa-star"></i> ${achievement.points_reward} pontos
                </p>
                ${achievement.is_pro_only ? '<p class="achievement-pro-tag"><i class="fas fa-gem"></i> Conquista exclusiva Pro</p>' : ''}
                ${achievement.unlocked ? `<p class="achievement-unlocked">✓ Desbloqueada em ${formatDate(achievement.unlocked_at)}</p>` : '<p class="achievement-locked">🔒 Ainda não desbloqueada</p>'}
            `,
            icon: achievement.unlocked ? 'success' : 'info',
            confirmButtonText: 'Fechar',
            customClass: {
                popup: 'achievement-modal',
                confirmButton: 'btn btn-primary'
            }
        });
    }

    /**
     * Mostrar modal com todas as conquistas
     */
    async function showAllAchievements() {
        try {
            const response = await fetch(`${BASE}api/gamification/achievements`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erro ao carregar conquistas');
            }

            const data = await response.json();
            const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
            if (isSuccess && data.data) {
                const achievements = data.data.achievements;
                const stats = data.data.stats;

                let html = `
                    <div class="achievements-modal-stats">
                        <div class="stat-item">
                            <div class="stat-value">${stats.unlocked_count}</div>
                            <div class="stat-label">Desbloqueadas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${stats.completion_percentage}%</div>
                            <div class="stat-label">Completado</div>
                        </div>
                    </div>
                    <div class="achievements-modal-grid">
                `;

                achievements.forEach(ach => {
                    const status = ach.unlocked ? 'unlocked' : 'locked';
                    const proTag = ach.is_pro_only ? '<span class="pro-tag">PRO</span>' : '';

                    html += `
                        <div class="achievement-modal-item ${status}">
                            <div class="achievement-icon">${ach.icon}</div>
                            <div class="achievement-info">
                                <div class="achievement-name">${ach.name} ${proTag}</div>
                                <div class="achievement-desc">${ach.description}</div>
                                <div class="achievement-points-small">
                                    <i class="fas fa-star"></i> ${ach.points_reward} pts
                                </div>
                            </div>
                            ${ach.unlocked ? '<div class="achievement-check">✓</div>' : ''}
                        </div>
                    `;
                });

                html += '</div>';

                if (typeof Swal === 'undefined') {
                    console.warn('SweetAlert2 não está carregado');
                    alert('Suas conquistas estão carregadas! Mas o SweetAlert2 não está disponível.');
                    return;
                }

                Swal.fire({
                    title: '🏆 Suas Conquistas',
                    html: html,
                    width: '800px',
                    confirmButtonText: 'Fechar',
                    customClass: {
                        popup: 'achievements-modal',
                        confirmButton: 'btn btn-primary'
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar conquistas:', error);
            console.error('BASE URL:', BASE);
            console.error('Fetch URL completa:', `${BASE}api/gamification/achievements`);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Não foi possível carregar as conquistas: ' + error.message,
                });
            } else {
                console.error('SweetAlert2 não disponível para mostrar erro');
            }
        }
    }

    /**
     * Mostrar upgrade Pro
     */
    function showProUpgrade() {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 não está carregado');
            window.location.href = `${BASE}premium`;
            return;
        }

        Swal.fire({
            title: '💎 Plano Pro',
            html: `
                <div class="pro-upgrade-modal">
                    <h3>Acelere seu progresso!</h3>
                    <div class="pro-benefits">
                        <div class="pro-benefit">
                            <i class="fas fa-star"></i>
                            <span>Ganhe <strong>1.5x mais pontos</strong> em todas as ações</span>
                        </div>
                        <div class="pro-benefit">
                            <i class="fas fa-shield-alt"></i>
                            <span><strong>Proteção de streak</strong> - 1 dia grátis por mês</span>
                        </div>
                        <div class="pro-benefit">
                            <i class="fas fa-trophy"></i>
                            <span><strong>Conquistas exclusivas</strong> com mais recompensas</span>
                        </div>
                        <div class="pro-benefit">
                            <i class="fas fa-crown"></i>
                            <span>Alcance o <strong>nível máximo 8</strong></span>
                        </div>
                    </div>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-gem"></i> Assinar Pro',
            cancelButtonText: 'Agora não',
            customClass: {
                popup: 'pro-upgrade-modal',
                confirmButton: 'btn btn-primary btn-pro',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirecionar para página de upgrade
                window.location.href = `${BASE}premium`;
            }
        });
    }

    /**
     * Notificar ganho de pontos
     */
    window.notifyPointsGained = function (points, message = 'Pontos ganhos!') {
        if (typeof Swal === 'undefined') {
            console.log(`${message}: +${points} pontos`);
            return;
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: 'success',
            title: message,
            text: `+${points} pontos`
        });

        // Recarregar progresso
        setTimeout(() => {
            loadGamificationProgress();
            loadGamificationStats();
        }, 500);
    };

    /**
     * Notificar conquista desbloqueada
     */
    window.notifyAchievementUnlocked = function (achievement) {
        Swal.fire({
            title: '🎉 Conquista Desbloqueada!',
            html: `
                <div class="achievement-unlock-animation">
                    <div class="achievement-icon-big">${achievement.icon}</div>
                    <h2>${achievement.name}</h2>
                    <p>${achievement.description}</p>
                    <p class="achievement-points-reward">
                        <i class="fas fa-star"></i> +${achievement.points_reward} pontos
                    </p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Awesome!',
            customClass: {
                popup: 'achievement-unlock-modal',
                confirmButton: 'btn btn-primary'
            }
        });

        // Recarregar conquistas e progresso
        setTimeout(() => {
            loadAchievements();
            loadGamificationProgress();
        }, 500);
    };

    /**
     * Notificar subida de nível
     */
    window.notifyLevelUp = function (newLevel) {
        Swal.fire({
            title: '⭐ Subiu de Nível!',
            html: `
                <div class="level-up-animation">
                    <div class="level-number">${newLevel}</div>
                    <p>Parabéns! Você alcançou o nível ${newLevel}!</p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Continuar',
            customClass: {
                popup: 'level-up-modal',
                confirmButton: 'btn btn-primary'
            }
        });

        // Recarregar progresso
        setTimeout(() => {
            loadGamificationProgress();
        }, 500);
    };

    // Utilitários
    function getLevelThreshold(level) {
        const thresholds = {
            1: 0,
            2: 300,
            3: 500,
            4: 700,
            5: 1000,
            6: 1500,
            7: 2200,
            8: 3000,
            9: 3000 // máximo
        };
        return thresholds[level] || 3000;
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    }

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGamification);
    } else {
        initGamification();
    }
})();
