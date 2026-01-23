/**
 * Gamification System - Dashboard
 * Gerencia carregamento e exibição de dados de gamificação
 * Usa funções globais de window.GAMIFICATION
 */

(function () {
    'use strict';

    // Atalhos para funções globais
    const GAM = window.GAMIFICATION;
    const getLevelThreshold = GAM.getLevelThreshold.bind(GAM);
    const MAX_LEVEL = GAM.MAX_LEVEL;
    const formatNumber = GAM.formatNumber.bind(GAM);
    const formatDate = GAM.formatDate.bind(GAM);

    // ========== VARIÁVEIS ==========

    // Ler BASE_URL do meta tag
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    const BASE = baseUrlMeta ? baseUrlMeta.content : (window.BASE_URL || '/');

    let isPro = false;
    let currentProgress = {};

    /**
     * Recarregar todos os dados de gamificação
     */
    function refreshGamification() {
        loadGamificationProgress();
        loadGamificationStats();
        loadAchievements();
    }

    /**
     * Inicializar sistema de gamificação
     */
    function initGamification() {
        // Verificar se estamos na página correta
        const gamificationSection = document.querySelector('.gamification-section');
        if (!gamificationSection) {
            return;
        }

        loadGamificationProgress();
        loadGamificationStats();
        loadAchievements();

        // Event listeners
        const btnProUpgrade = document.querySelector('.btn-pro-upgrade');

        if (btnProUpgrade) {
            btnProUpgrade.addEventListener('click', showProUpgrade);
        }

        // Escutar mudança de mês para atualizar gamificação dinamicamente
        document.addEventListener('lukrato:month-changed', () => {
            refreshGamification();
        });

        // Escutar mudança de dados para atualizar gamificação
        document.addEventListener('lukrato:data-changed', () => {
            refreshGamification();
        });
    }

    /**
     * Carregar progresso do usuário
     */
    async function loadGamificationProgress() {
        try {
            const response = await fetch(`${BASE}api/gamification/progress`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

            if (isSuccess && data.data) {
                currentProgress = data.data;
                isPro = data.data.is_pro;
                updateProgressUI(data.data);
            }
        } catch (error) {
            // Silenciar erros
        }
    }

    /**
     * Atualizar UI com dados de progresso
     */
    function updateProgressUI(progress) {
        console.log('📊 [GAMIFICATION] Atualizando UI com progresso:', progress);

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

        // Barra de progresso de nível usando funções globais
        const progressBar = document.getElementById('levelProgressBar');
        const progressPoints = document.getElementById('levelProgressPoints');
        const progressText = document.getElementById('levelProgressText');

        if (progressBar && progressPoints) {
            const progressData = GAM.calculateProgress(progress.current_level, progress.total_points);
            const percentage = progressData.percentage;
            const currentInLevel = progressData.current;
            const neededPoints = progressData.needed;
            const isMaxLevel = progressData.isMaxLevel;

            console.log('📈 [PROGRESS] Cálculo:', {
                level: progress.current_level,
                totalPoints: progress.total_points,
                currentInLevel,
                neededPoints,
                percentage: percentage.toFixed(2) + '%',
                isMaxLevel,
                isPro: progress.is_pro
            });

            progressBar.style.width = `${percentage}%`;

            if (isMaxLevel) {
                progressPoints.textContent = `${formatNumber(progress.total_points)} pontos`;
            } else {
                progressPoints.textContent = `${formatNumber(currentInLevel)} / ${formatNumber(neededPoints)} pontos`;
            }

            if (progressText) {
                if (isMaxLevel) {
                    progressText.textContent = '🎉 Nível máximo alcançado!';
                } else {
                    const remaining = progress.points_to_next_level || 0;
                    progressText.textContent = `Faltam ${formatNumber(remaining)} pontos para o próximo nível`;
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
     * Obter mês atual selecionado no header
     */
    function getCurrentMonth() {
        // Tentar ler do LukratoHeader (API pública)
        if (window.LukratoHeader && typeof window.LukratoHeader.getMonth === 'function') {
            return window.LukratoHeader.getMonth();
        }
        // Fallback: ler do sessionStorage
        const stored = sessionStorage.getItem('lkMes');
        if (stored && /^\d{4}-(0[1-9]|1[0-2])$/.test(stored)) {
            return stored;
        }
        // Fallback: mês atual
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    }

    /**
     * Carregar conquistas
     */
    async function loadAchievements() {
        try {
            // Não filtra por mês - conquistas são permanentes
            // O filtro por mês é apenas para destacar conquistas recentes
            const month = getCurrentMonth();
            const response = await fetch(`${BASE}api/gamification/achievements?month=${month}`, {
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

        // Ordenar conquistas:
        // 1. Desbloqueadas (unlocked ou unlocked_ever = true) - aparecem primeiro
        // 2. Não desbloqueadas (locked) - aparecem por último
        // Dentro das desbloqueadas, as do mês atual aparecem primeiro
        const sorted = [...achievements].sort((a, b) => {
            const aUnlocked = a.unlocked || a.unlocked_ever;
            const bUnlocked = b.unlocked || b.unlocked_ever;

            // Desbloqueadas primeiro
            if (aUnlocked && !bUnlocked) return -1;
            if (!aUnlocked && bUnlocked) return 1;

            // Entre as desbloqueadas, priorizar as do mês atual
            if (aUnlocked && bUnlocked) {
                if (a.unlocked && !b.unlocked) return -1;
                if (!a.unlocked && b.unlocked) return 1;
            }

            return 0;
        });

        // Mostrar apenas as primeiras 6 conquistas no dashboard
        const displayAchievements = sorted.slice(0, 6);

        badgesGrid.innerHTML = '';

        displayAchievements.forEach(achievement => {
            const badgeItem = document.createElement('div');

            // Definir classes baseado no status
            // Uma conquista é considerada "unlocked" se foi desbloqueada em qualquer momento
            const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
            let statusClass = isUnlocked ? 'unlocked' : 'locked';

            // Se foi desbloqueada mas não neste mês, usar estilo ligeiramente diferente
            if (achievement.unlocked_ever && !achievement.unlocked) {
                statusClass = 'unlocked'; // Ainda mostra como unlocked, pois é permanente
            }

            badgeItem.className = `badge-item ${statusClass}`;

            // Mostrar tag PRO apenas em conquistas pro_only para usuários que não são PRO
            if (achievement.is_pro_only && !isPro) {
                badgeItem.classList.add('pro-only');
            }

            badgeItem.title = achievement.description;

            // Mostrar check para conquistas já desbloqueadas
            let checkMark = '';
            if (isUnlocked) {
                checkMark = `<div class="badge-unlocked-check">✓</div>`;
            }

            badgeItem.innerHTML = `
                <div class="badge-icon">${achievement.icon}</div>
                <div class="badge-name">${achievement.name}</div>
                ${achievement.is_pro_only ? '<div class="badge-pro-tag">PRO</div>' : ''}
                ${checkMark}
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

        // Determinar status de desbloqueio
        const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
        let statusHtml = '';

        if (achievement.unlocked) {
            statusHtml = `<p class="achievement-unlocked">✓ Desbloqueada neste mês${achievement.unlocked_at ? ` em ${formatDate(achievement.unlocked_at)}` : ''}</p>`;
        } else if (achievement.unlocked_ever) {
            statusHtml = `<p class="achievement-unlocked past">✓ Conquistada anteriormente</p>`;
        } else {
            statusHtml = '<p class="achievement-locked">🔒 Ainda não desbloqueada</p>';
        }

        Swal.fire({
            title: `${achievement.icon} ${achievement.name}`,
            html: `
                <p class="achievement-description">${achievement.description}</p>
                <p class="achievement-points">
                    <i class="fas fa-star"></i> ${achievement.points_reward} pontos
                </p>
                ${achievement.is_pro_only ? '<p class="achievement-pro-tag"><i class="fas fa-gem"></i> Conquista exclusiva Pro</p>' : ''}
                ${statusHtml}
            `,
            icon: isUnlocked ? 'success' : 'info',
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
                            <span>Alcance o <strong>nível máximo 15</strong></span>
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
                window.location.href = `${BASE}billing`;
            }
        });
    }

    /**
     * Notificar ganho de pontos
     */
    window.notifyPointsGained = function (points, message = 'Pontos ganhos!') {
        if (typeof Swal === 'undefined') {
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
     * Criar confetes na tela (versão profissional com duas ondas)
     */
    function createAchievementConfetti() {
        const colors = ['#e67e22', '#f39c12', '#2ecc71', '#3498db', '#e74c3c', '#9b59b6', '#f1c40f', '#1abc9c'];
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;
        
        // Primeira onda: explosão forte e rápida (50 confetes)
        createWave(50, 0, 3.5, 5.5);
        
        // Segunda onda: explosão suave e elegante (40 confetes, 150ms depois)
        setTimeout(() => createWave(40, 0, 2.5, 4), 150);
        
        function createWave(count, delayOffset, minVelocity, maxVelocity) {
            for (let i = 0; i < count; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    
                    // 70% retângulos, 30% quadrados
                    const isSquare = Math.random() > 0.7;
                    const width = isSquare ? Math.random() * 8 + 5 : Math.random() * 6 + 4;
                    const height = isSquare ? width : Math.random() * 12 + 8;
                    
                    confetti.style.position = 'fixed';
                    confetti.style.width = width + 'px';
                    confetti.style.height = height + 'px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = centerX + 'px';
                    confetti.style.top = centerY + 'px';
                    confetti.style.borderRadius = isSquare ? '2px' : '1px';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '999999';
                    confetti.style.opacity = '0.95';
                    confetti.style.transformStyle = 'preserve-3d';
                    confetti.style.boxShadow = '0 0 3px rgba(0,0,0,0.2)';
                    
                    document.body.appendChild(confetti);
                    
                    // Física realista com variação
                    const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.3 - 0.15);
                    const velocity = Math.random() * (maxVelocity - minVelocity) + minVelocity;
                    let vx = Math.cos(angle) * velocity;
                    let vy = Math.sin(angle) * velocity - Math.random() * 1.5;
                    let x = 0;
                    let y = 0;
                    let rotationX = Math.random() * 360;
                    let rotationY = Math.random() * 360;
                    let rotationZ = Math.random() * 360;
                    let velocityRotX = Math.random() * 10 - 5;
                    let velocityRotY = Math.random() * 10 - 5;
                    let velocityRotZ = Math.random() * 10 - 5;
                    
                    // Alguns confetes mais pesados, outros mais leves
                    const mass = Math.random() * 0.5 + 0.7;
                    const gravity = 0.15 * mass;
                    const friction = 0.985 + (Math.random() * 0.01);
                    
                    const animate = () => {
                        vy += gravity;
                        vx *= friction;
                        vy *= friction;
                        
                        x += vx;
                        y += vy;
                        
                        rotationX += velocityRotX * friction;
                        rotationY += velocityRotY * friction;
                        rotationZ += velocityRotZ * friction;
                        
                        confetti.style.transform = `
                            translate(${x}px, ${y}px) 
                            rotateX(${rotationX}deg) 
                            rotateY(${rotationY}deg) 
                            rotateZ(${rotationZ}deg)
                        `;
                        
                        if (y < window.innerHeight + 100 && (Math.abs(vx) > 0.05 || Math.abs(vy) > 0.05)) {
                            requestAnimationFrame(animate);
                        } else {
                            confetti.remove();
                        }
                    };
                    
                    requestAnimationFrame(animate);
                }, i * 3 + delayOffset);
            }
        }
    }

    /**
     * Tocar som de conquista (Success Fanfare Trumpets)
     */
    function playAchievementSound() {
        try {
            const audio = new Audio('/lukrato/public/assets/audio/success-fanfare-trumpets-6185.mp3');
            audio.volume = 0.5; // 50% do volume
            audio.play().catch(error => {
                console.log('Não foi possível reproduzir o som:', error);
            });
        } catch (error) {
            console.log('Erro ao carregar o áudio:', error);
        }
    }

    /**
     * As funções de notificação são gerenciadas globalmente
     * por gamification-global.js. Podemos adicionar listeners
     * para recarregar dados quando necessário.
     */

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGamification);
    } else {
        initGamification();
    }
})();
