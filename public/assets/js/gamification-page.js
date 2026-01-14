// 🎮 Gamification Page - Dashboard completo
(function () {
    'use strict';

    console.log('🎮 [GAMIFICATION PAGE] Script carregado!');

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

    // Carregar todos os dados
    async function loadAllData() {
        try {
            console.log('🎮 [PAGE] Carregando dados...');

            const [progressData, achievementsData, historyData, leaderboardData] = await Promise.all([
                fetch(`${BASE_URL}api/gamification/progress`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/achievements`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/history?limit=20`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${BASE_URL}api/gamification/leaderboard`, { credentials: 'same-origin' }).then(r => r.json())
            ]);

            console.log('🎮 [PAGE] Dados recebidos:', { progressData, achievementsData, historyData, leaderboardData });

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
        const nextLevel = level + 1;
        const currentLevelPoints = levelThresholds[level] || 0;
        const nextLevelPoints = levelThresholds[nextLevel] || levelThresholds[8];
        const pointsInLevel = nextLevelPoints - currentLevelPoints;
        let currentInLevel = totalPoints - currentLevelPoints;

        // Proteção contra valores negativos
        if (currentInLevel < 0) currentInLevel = 0;

        const percentage = Math.round((currentInLevel / pointsInLevel) * 100);

        // Atualizar barra de progresso
        if (elements.nextLevel) elements.nextLevel.textContent = nextLevel;
        if (elements.progressPointsLarge) {
            elements.progressPointsLarge.textContent = `${currentInLevel} / ${pointsInLevel}`;
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

        renderAchievements(achievements);
    }

    // Renderizar conquistas
    function renderAchievements(achievements) {
        if (!elements.achievementsGridPage) return;

        const filtered = filterAchievements(achievements, currentFilter);

        elements.achievementsGridPage.innerHTML = filtered.map(achievement => {
            const isUnlocked = achievement.unlocked;
            const cardClass = isUnlocked ? 'achievement-card unlocked' : 'achievement-card';

            return `
                <div class="${cardClass}">
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

    console.log('🎮 [GAMIFICATION PAGE] Inicializado!');

})();
