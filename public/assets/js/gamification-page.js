// 🎮 Gamification Page - Dashboard completo
// Usa funções globais de window.GAMIFICATION
(function () {
    'use strict';

    // Atalhos para funções globais
    const GAM = window.GAMIFICATION;
    const formatNumber = GAM.formatNumber.bind(GAM);
    const formatDate = GAM.formatDate.bind(GAM);
    const MAX_LEVEL = GAM.MAX_LEVEL;

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

        // Calcular progresso usando função global
        const progressData = GAM.calculateProgress(level, totalPoints);
        const isMaxLevel = progressData.isMaxLevel;
        const nextLevel = level + 1;
        const percentage = progressData.percentage;
        const currentInLevel = progressData.current;
        const pointsInLevel = progressData.needed;

        // Atualizar barra de progresso
        if (elements.nextLevel) {
            elements.nextLevel.textContent = isMaxLevel ? 'MAX' : nextLevel;
        }
        if (elements.progressPointsLarge) {
            if (isMaxLevel) {
                elements.progressPointsLarge.textContent = `${formatNumber(totalPoints)} pontos (Máximo!)`;
            } else {
                elements.progressPointsLarge.textContent = `${formatNumber(currentInLevel)} / ${formatNumber(pointsInLevel)}`;
            }
        }
        if (elements.progressFillLarge) {
            elements.progressFillLarge.style.width = `${percentage}%`;
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

    function getCategoryColor(category) {
        return GAM.getCategoryColor(category);
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

})();
