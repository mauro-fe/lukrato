// 🎮 Gamification Page - Dashboard completo
(function () {
    'use strict';


    // Configuração
    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || window.BASE_URL || '/';
    let currentFilter = 'all';

    // Cache de elementos
    const elements = {
        userLevelLarge: document.getElementById('userLevelLarge'),
        totalPointsCard: document.getElementById('totalPointsCard'),
        currentLevelCard: document.getElementById('currentLevelCard'),
        currentStreakCard: document.getElementById('currentStreakCard'),
        achievementsCountCard: document.getElementById('achievementsCountCard'),
        nextLevel: document.getElementById('nextLevel'),
        progressPointsLarge: document.getElementById('progressPointsLarge'),
        progressFillLarge: document.getElementById('progressFillLarge'),
        achievementsGridPage: document.getElementById('achievementsGridPage'),
        pointsHistory: document.getElementById('pointsHistory'),
        leaderboardContainer: document.getElementById('leaderboardContainer')
    };

    // Mapa de níveis (expandido para 15)
    const levelThresholds = {
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
    };

    const MAX_LEVEL = 15;

    // Carregar todos os dados
    async function loadAllData() {
        try {

            const [progressData, achievementsData, historyData, leaderboardData] = await Promise.all([
                fetch(`${BASE_URL}api/gamification/progress`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/achievements`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/history?limit=20`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/leaderboard`, { credentials: 'same-origin' }).then(r => r.json())
            ]);

            updateProgressSection(progressData);
            updateAchievements(achievementsData);
            updatePointsHistory(historyData);
            updateLeaderboard(leaderboardData);

        } catch (error) {
            console.error('❌ [PAGE] Erro ao carregar dados:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Não foi possível carregar os dados da gamificação',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }

    // Atualizar seção de progresso
    function updateProgressSection(data) {
        const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

        if (!isSuccess) {
            console.warn('⚠️ [PAGE] Resposta inválida:', data);
            return;
        }

        const progress = data.data;
        const level = progress.current_level || 1;
        const totalPoints = progress.total_points || 0;
        const streak = progress.current_streak || 0;

        // Atualizar cards de stats
        if (elements.userLevelLarge) {
            elements.userLevelLarge.querySelector('span').textContent = `Nível ${level}`;
        }
        if (elements.totalPointsCard) elements.totalPointsCard.textContent = totalPoints;
        if (elements.currentLevelCard) elements.currentLevelCard.textContent = level;
        if (elements.currentStreakCard) elements.currentStreakCard.textContent = streak;

        // Calcular progresso para próximo nível
        const isMaxLevel = level >= MAX_LEVEL;
        const nextLevel = level + 1;
        const currentLevelPoints = levelThresholds[level] || 0;
        const nextLevelPoints = levelThresholds[nextLevel] || levelThresholds[MAX_LEVEL];
        const pointsInLevel = nextLevelPoints - currentLevelPoints;
        let currentInLevel = totalPoints - currentLevelPoints;

        // Proteção contra valores negativos
        if (currentInLevel < 0) currentInLevel = 0;

        const percentage = isMaxLevel ? 100 : Math.round((currentInLevel / pointsInLevel) * 100);

        // Atualizar barra de progresso
        if (elements.nextLevel) {
            elements.nextLevel.textContent = isMaxLevel ? 'MAX' : nextLevel;
        }
        if (elements.progressPointsLarge) {
            if (isMaxLevel) {
                elements.progressPointsLarge.textContent = `${totalPoints.toLocaleString('pt-BR')} pontos (Máximo!)`;
            } else {
                elements.progressPointsLarge.textContent = `${currentInLevel.toLocaleString('pt-BR')} / ${pointsInLevel.toLocaleString('pt-BR')}`;
            }
        }
        if (elements.progressFillLarge) {
            elements.progressFillLarge.style.width = `${Math.max(0, percentage)}%`;
        }
    }

    // Atualizar conquistas
    function updateAchievements(data) {

        const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

        if (!isSuccess || !data.data || !data.data.achievements) {
            console.warn('⚠️ [PAGE] Dados de conquistas inválidos:', data);
            return;
        }

        const achievements = data.data.achievements;
        const stats = data.data.stats || {};
        const unlockedCount = stats.unlocked_count || achievements.filter(a => a.unlocked).length;


        // Atualizar contador no card
        if (elements.achievementsCountCard) {
            elements.achievementsCountCard.textContent = `${unlockedCount}/${achievements.length}`;
        }

        renderAchievements(achievements);
    }

    // Renderizar conquistas
    function renderAchievements(achievements) {
        if (!elements.achievementsGridPage) {
            console.error('❌ [ACHIEVEMENTS] Elemento achievementsGridPage não encontrado!');
            return;
        }

        const filtered = filterAchievements(achievements, currentFilter);

        elements.achievementsGridPage.innerHTML = filtered.map(achievement => {
            const isUnlocked = achievement.unlocked;
            const cardClass = isUnlocked ? 'achievement-card unlocked' : 'achievement-card';

            return `
                <div class="${cardClass}" data-achievement='${JSON.stringify(achievement).replace(/'/g, "&#39;")}' style="cursor: pointer;">
                    <div class="achievement-icon">${achievement.icon}</div>
                    <div class="achievement-info">
                        <h3 class="achievement-title">${achievement.name}</h3>
                        <p class="achievement-description">${achievement.description}</p>
                        <div class="achievement-meta">
                            <span class="achievement-points">+${achievement.points_reward} pts</span>
                            ${isUnlocked ?
                    `<span class="achievement-date">✓ ${formatDate(achievement.unlocked_at)}</span>` :
                    '<span class="achievement-locked">🔒 Bloqueada</span>'
                }
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Adicionar eventos de clique nos cards
        document.querySelectorAll('.achievement-card').forEach(card => {
            card.addEventListener('click', function () {
                const achievement = JSON.parse(this.dataset.achievement);
                showAchievementDetail(achievement);
            });
        });
    }

    // Filtrar conquistas
    function filterAchievements(achievements, filter) {
        switch (filter) {
            case 'unlocked':
                return achievements.filter(a => a.unlocked);
            case 'locked':
                return achievements.filter(a => !a.unlocked);
            default:
                return achievements;
        }
    }

    // Atualizar histórico de pontos
    function updatePointsHistory(data) {
        const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

        if (!isSuccess || !data.data) {
            console.warn('⚠️ [PAGE] Dados de histórico inválidos:', data);
            if (elements.pointsHistory) {
                elements.pointsHistory.innerHTML = '<p class="empty-state">Nenhuma atividade recente</p>';
            }
            return;
        }

        const history = data.data.history || [];

        if (!elements.pointsHistory) return;

        if (history.length === 0) {
            elements.pointsHistory.innerHTML = '<p class="empty-state">Nenhuma atividade recente</p>';
            return;
        }

        elements.pointsHistory.innerHTML = history.map(action => `
            <div class="history-item">
                <div class="history-icon">${getActionIcon(action.action)}</div>
                <div class="history-content">
                    <div class="history-title">${action.description || formatAction(action.action)}</div>
                    <div class="history-date">${action.relative_time || formatDate(action.created_at)}</div>
                </div>
                <div class="history-points ${action.points >= 0 ? 'positive' : 'negative'}">
                    ${action.points >= 0 ? '+' : ''}${action.points} pts
                </div>
            </div>
        `).join('');
    }

    // Atualizar ranking
    function updateLeaderboard(data) {
        const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';

        if (!isSuccess || !data.data || !data.data.leaderboard) {
            console.warn('⚠️ [PAGE] Dados de ranking inválidos:', data);
            return;
        }

        const leaderboard = data.data.leaderboard;

        if (!elements.leaderboardContainer) return;

        if (leaderboard.length === 0) {
            elements.leaderboardContainer.innerHTML = '<p class="empty-state">Nenhum usuário no ranking</p>';
            return;
        }

        elements.leaderboardContainer.innerHTML = `
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Posição</th>
                        <th>Usuário</th>
                        <th>Nível</th>
                        <th>Pontos</th>
                    </tr>
                </thead>
                <tbody>
                    ${leaderboard.map((user) => {
            const rankClass = user.position <= 3 ? `rank-${user.position}` : '';
            const rankIcon = user.position === 1 ? '🥇' : user.position === 2 ? '🥈' : user.position === 3 ? '🥉' : '';

            // Pegar apenas os dois primeiros nomes
            const nomeCompleto = user.user_name || '';
            const partesNome = nomeCompleto.trim().split(' ');
            const nomeCurto = partesNome.slice(0, 2).join(' ');

            return `
                            <tr class="${rankClass}">
                                <td class="rank-cell">${rankIcon} ${user.position}º</td>
                                <td class="user-cell">
                                    <div class="user-info">
                                        <strong>${nomeCurto}</strong>
                                    </div>
                                </td>
                                <td class="level-cell">
                                    <span class="level-badge">Nível ${user.current_level}</span>
                                </td>
                                <td class="points-cell"><strong>${user.total_points}</strong> pts</td>
                            </tr>
                        `;
        }).join('')}
                </tbody>
            </table>
        `;
    }

    // Utilitários
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return 'Hoje';
        if (diffDays === 1) return 'Ontem';
        if (diffDays < 7) return `${diffDays} dias atrás`;

        return date.toLocaleDateString('pt-BR');
    }

    // Mostrar detalhes da conquista em modal
    function showAchievementDetail(achievement) {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 não está carregado');
            return;
        }

        const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
        let statusHtml = '';

        if (achievement.unlocked) {
            statusHtml = `<p style="color: #10b981; font-weight: 600; margin-top: 15px;">✓ Desbloqueada${achievement.unlocked_at ? ` em ${formatDate(achievement.unlocked_at)}` : ''}</p>`;
        } else if (achievement.unlocked_ever) {
            statusHtml = `<p style="color: #10b981; font-weight: 600; margin-top: 15px;">✓ Conquistada anteriormente</p>`;
        } else {
            statusHtml = '<p style="color: #94a3b8; font-weight: 600; margin-top: 15px;">🔒 Ainda não desbloqueada</p>';
        }

        const proTag = achievement.is_pro_only
            ? '<p style="color: #f59e0b; font-weight: 600; margin-top: 10px;"><i class="fas fa-gem"></i> Conquista exclusiva PRO</p>'
            : '';

        Swal.fire({
            title: `${achievement.icon} ${achievement.name}`,
            html: `
                <p style="font-size: 16px; color: #64748b; margin-bottom: 15px;">${achievement.description}</p>
                <p style="font-size: 18px; color: #f59e0b; font-weight: 700;">
                    ⭐ ${achievement.points_reward} pontos
                </p>
                ${proTag}
                ${statusHtml}
            `,
            icon: isUnlocked ? 'success' : 'info',
            confirmButtonText: 'Fechar',
            confirmButtonColor: '#f97316',
            customClass: {
                popup: 'achievement-modal'
            }
        });
    }

    function formatAction(action) {
        const actions = {
            'CREATE_LANCAMENTO': 'Criou lançamento',
            'CREATE_CATEGORIA': 'Criou categoria',
            'VIEW_REPORT': 'Visualizou relatório',
            'CREATE_META': 'Criou meta',
            'CLOSE_MONTH': 'Fechou mês',
            'DAILY_ACTIVITY': 'Atividade diária',
            'STREAK_3_DAYS': 'Sequência de 3 dias',
            'STREAK_7_DAYS': 'Sequência de 7 dias',
            'STREAK_30_DAYS': 'Sequência de 30 dias',
            'POSITIVE_MONTH': 'Mês positivo',
            'LEVEL_UP': 'Subiu de nível'
        };
        return actions[action] || action;
    }

    function getActionIcon(action) {
        const icons = {
            // Lançamentos
            'LAUNCH_CREATED': '💰',
            'LAUNCH_EDITED': '✏️',
            'LAUNCH_DELETED': '🗑️',
            'CREATE_LANCAMENTO': '💰',
            'FIRST_LAUNCH_DAY': '🌅',

            // Categorias
            'CREATE_CATEGORIA': '🏷️',
            'CATEGORY_CREATED': '🏷️',

            // Atividade
            'DAILY_LOGIN': '👋',
            'DAILY_ACTIVITY': '✅',
            'VIEW_REPORT': '📊',

            // Metas
            'CREATE_META': '🎯',
            'META_ACHIEVED': '🏆',

            // Meses
            'CLOSE_MONTH': '📅',
            'POSITIVE_MONTH': '💚',

            // Streaks
            'STREAK_BONUS': '🔥',
            'STREAK_3_DAYS': '🔥',
            'STREAK_7_DAYS': '🔥🔥',
            'STREAK_30_DAYS': '🔥🔥🔥',

            // Níveis
            'LEVEL_UP': '⭐',

            // Conquistas
            'ACHIEVEMENT_UNLOCKED': '🏅',

            // Cartões
            'CARD_CREATED': '💳',
            'INVOICE_PAID': '🧾'
        };
        return icons[action] || '📌';
    }

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
     * Notificar conquista desbloqueada
     */
    window.notifyAchievementUnlocked = function (achievement) {
        // Tocar som imediatamente
        playAchievementSound();
        
        // Confetes estouram 100ms depois (sincronizado com o som)
        setTimeout(() => {
            createAchievementConfetti();
        }, 100);
        
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
            confirmButtonText: '🚀 Próxima conquista!',
            customClass: {
                popup: 'achievement-unlock-modal',
                confirmButton: 'btn btn-primary'
            }
        });

        // Recarregar conquistas e progresso
        setTimeout(() => {
            loadAllData();
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
            loadAllData();
        }, 500);
    };

    // Event Listeners
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;

            // Recarregar conquistas
            fetch(`${BASE_URL}api/gamification/achievements`, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (data.data && data.data.achievements) {
                        renderAchievements(data.data.achievements);
                    }
                });
        });
    });

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAllData);
    } else {
        loadAllData();
    }

})();
