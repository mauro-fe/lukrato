/**
 * ============================================================================
 * LUKRATO — Gamification Page (Vite Module)
 * ============================================================================
 * Página completa de gamificação: progresso, conquistas, histórico, ranking.
 * Usa window.GAMIFICATION global (gamification-global.js).
 *
 * Substitui: public/assets/js/gamification-page.js
 * ============================================================================
 */

import { getBaseUrl, getCSRFToken } from '../shared/api.js';
import { toastError } from '../shared/ui.js';
import { escapeHtml, formatDate as sharedFormatDate } from '../shared/utils.js';

// ─── Globals ────────────────────────────────────────────────────────────────

const GAM = window.GAMIFICATION;
const formatNumber = GAM.formatNumber.bind(GAM);
const formatDate = GAM.formatDate.bind(GAM);
const BASE_URL = getBaseUrl();

// ─── Achievement icon colors (shared with dashboard) ────────────────────────

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

// ─── DOM Elements ───────────────────────────────────────────────────────────

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

let currentFilter = 'all';

// ─── Data Loading ───────────────────────────────────────────────────────────

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
        console.error('[PAGE] Erro ao carregar dados:', error);
        toastError('Não foi possível carregar os dados da gamificação');
    }
}

// ─── Progress Section ───────────────────────────────────────────────────────

function updateProgressSection(data) {
    const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
    if (!isSuccess) return;

    const progress = data.data;
    const level = progress.current_level || 1;
    const totalPoints = progress.total_points || 0;
    const streak = progress.current_streak || 0;

    if (elements.userLevelLarge) {
        elements.userLevelLarge.querySelector('span').textContent = `Nível ${level}`;
    }
    if (elements.totalPointsCard) elements.totalPointsCard.textContent = totalPoints;
    if (elements.currentLevelCard) elements.currentLevelCard.textContent = level;
    if (elements.currentStreakCard) elements.currentStreakCard.textContent = streak;

    const progressData = GAM.calculateProgress(level, totalPoints);
    const nextLevel = level + 1;

    if (elements.nextLevel) {
        elements.nextLevel.textContent = progressData.isMaxLevel ? 'MAX' : nextLevel;
    }
    if (elements.progressPointsLarge) {
        elements.progressPointsLarge.textContent = progressData.isMaxLevel
            ? `${formatNumber(totalPoints)} pontos (Máximo!)`
            : `${formatNumber(progressData.current)} / ${formatNumber(progressData.needed)}`;
    }
    if (elements.progressFillLarge) {
        elements.progressFillLarge.style.width = `${progressData.percentage}%`;
    }
}

// ─── Achievements ───────────────────────────────────────────────────────────

function updateAchievements(data) {
    const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
    if (!isSuccess || !data.data?.achievements) return;

    const achievements = data.data.achievements;
    const stats = data.data.stats || {};
    const unlockedCount = stats.unlocked_count || achievements.filter(a => a.unlocked).length;

    if (elements.achievementsCountCard) {
        elements.achievementsCountCard.textContent = `${unlockedCount}/${achievements.length}`;
    }
    renderAchievements(achievements);
}

function renderAchievements(achievements) {
    if (!elements.achievementsGridPage) return;

    const filtered = filterAchievements(achievements, currentFilter);

    elements.achievementsGridPage.innerHTML = filtered.map(achievement => {
        const isUnlocked = achievement.unlocked;
        const cardClass = isUnlocked ? 'achievement-card unlocked' : 'achievement-card';

        return `
            <div class="${cardClass}" data-achievement='${JSON.stringify(achievement).replace(/'/g, "&#39;")}' style="cursor: pointer;">
                <div class="achievement-icon" style="color:${getAchievementIconColor(achievement.icon)}"><i data-lucide="${achievement.icon}"></i></div>
                <div class="achievement-info">
                    <h3 class="achievement-title">${escapeHtml(achievement.name)}</h3>
                    <p class="achievement-description">${escapeHtml(achievement.description)}</p>
                    <div class="achievement-meta">
                        <span class="achievement-points">+${achievement.points_reward} pts</span>
                        ${isUnlocked
                ? `<span class="achievement-date"><i data-lucide="check" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> ${formatDate(achievement.unlocked_at)}</span>`
                : '<span class="achievement-locked"><i data-lucide="lock" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Bloqueada</span>'
            }
                    </div>
                </div>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.achievement-card').forEach(card => {
        card.addEventListener('click', function () {
            const achievement = JSON.parse(this.dataset.achievement);
            showAchievementDetail(achievement);
        });
    });

    if (window.lucide) lucide.createIcons();
}

function filterAchievements(achievements, filter) {
    switch (filter) {
        case 'unlocked': return achievements.filter(a => a.unlocked);
        case 'locked': return achievements.filter(a => !a.unlocked);
        default: return achievements;
    }
}

// ─── Points History ─────────────────────────────────────────────────────────

function updatePointsHistory(data) {
    const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
    if (!isSuccess || !data.data) {
        if (elements.pointsHistory) elements.pointsHistory.innerHTML = '<p class="empty-state">Nenhuma atividade recente</p>';
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
            <div class="history-icon"><i data-lucide="${getActionIcon(action.action)}"></i></div>
            <div class="history-content">
                <div class="history-title">${action.description || formatAction(action.action)}</div>
                <div class="history-date">${action.relative_time || formatDate(action.created_at)}</div>
            </div>
            <div class="history-points ${action.points >= 0 ? 'positive' : 'negative'}">
                ${action.points >= 0 ? '+' : ''}${action.points} pts
            </div>
        </div>
    `).join('');

    if (window.lucide) lucide.createIcons();
}

// ─── Leaderboard ────────────────────────────────────────────────────────────

function updateLeaderboard(data) {
    const isSuccess = data.success === true || data.status === 'Success' || data.status === 'success';
    if (!isSuccess || !data.data?.leaderboard) return;

    const leaderboard = data.data.leaderboard;
    if (!elements.leaderboardContainer) return;

    if (leaderboard.length === 0) {
        elements.leaderboardContainer.innerHTML = '<p class="empty-state">Nenhum usuário no ranking</p>';
        return;
    }

    elements.leaderboardContainer.innerHTML = `
        <table class="leaderboard-table">
            <thead><tr><th>Posição</th><th>Usuário</th><th>Nível</th><th>Pontos</th></tr></thead>
            <tbody>
                ${leaderboard.map(user => {
        const rankClass = user.position <= 3 ? `rank-${user.position}` : '';
        const rankIcon = user.position === 1 ? '<i data-lucide="medal" style="color:#fbbf24;"></i>'
            : user.position === 2 ? '<i data-lucide="medal" style="color:#94a3b8;"></i>'
                : user.position === 3 ? '<i data-lucide="medal" style="color:#d97706;"></i>' : '';
        const nomeCurto = (user.user_name || '').trim().split(' ').slice(0, 2).join(' ');
        return `
                        <tr class="${rankClass}">
                            <td class="rank-cell">${rankIcon} ${user.position}º</td>
                            <td class="user-cell"><div class="user-info"><strong>${escapeHtml(nomeCurto)}</strong></div></td>
                            <td class="level-cell"><span class="level-badge">Nível ${user.current_level}</span></td>
                            <td class="points-cell"><strong>${user.total_points}</strong> pts</td>
                        </tr>
                    `;
    }).join('')}
            </tbody>
        </table>
    `;

    if (window.lucide) lucide.createIcons();
}

// ─── Achievement Detail Modal ───────────────────────────────────────────────

function showAchievementDetail(achievement) {
    if (typeof Swal === 'undefined') return;

    const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
    let statusHtml = '';

    if (achievement.unlocked) {
        statusHtml = `<p style="color: #10b981; font-weight: 600; margin-top: 15px;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Desbloqueada${achievement.unlocked_at ? ` em ${formatDate(achievement.unlocked_at)}` : ''}</p>`;
    } else if (achievement.unlocked_ever) {
        statusHtml = `<p style="color: #10b981; font-weight: 600; margin-top: 15px;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Conquistada anteriormente</p>`;
    } else {
        statusHtml = '<p style="color: #94a3b8; font-weight: 600; margin-top: 15px;"><i data-lucide="lock" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Ainda não desbloqueada</p>';
    }

    const proTag = achievement.is_pro_only
        ? '<p style="color: #f59e0b; font-weight: 600; margin-top: 10px;"><i data-lucide="gem"></i> Conquista exclusiva PRO</p>'
        : '';

    Swal.fire({
        title: achievement.name,
        html: `
            <div style="font-size:2.5rem;margin-bottom:10px;color:${getAchievementIconColor(achievement.icon)}"><i data-lucide="${achievement.icon}"></i></div>
            <p style="font-size: 16px; color: #64748b; margin-bottom: 15px;">${achievement.description}</p>
            <p style="font-size: 18px; color: #f59e0b; font-weight: 700;">
                <i data-lucide="star" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> ${achievement.points_reward} pontos
            </p>
            ${proTag}
            ${statusHtml}
        `,
        icon: isUnlocked ? 'success' : 'info',
        confirmButtonText: 'Fechar',
        confirmButtonColor: '#f97316',
        customClass: { popup: 'achievement-modal' },
        didOpen: () => { if (window.lucide) lucide.createIcons(); }
    });
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function formatAction(action) {
    const actions = {
        'CREATE_LANCAMENTO': 'Criou lançamento', 'CREATE_CATEGORIA': 'Criou categoria',
        'VIEW_REPORT': 'Visualizou relatório', 'CREATE_META': 'Criou meta',
        'CLOSE_MONTH': 'Fechou mês', 'DAILY_ACTIVITY': 'Atividade diária',
        'STREAK_3_DAYS': 'Sequência de 3 dias', 'STREAK_7_DAYS': 'Sequência de 7 dias',
        'STREAK_30_DAYS': 'Sequência de 30 dias', 'POSITIVE_MONTH': 'Mês positivo',
        'LEVEL_UP': 'Subiu de nível'
    };
    return actions[action] || action;
}

function getActionIcon(action) {
    const icons = {
        'LAUNCH_CREATED': 'coins', 'LAUNCH_EDITED': 'pencil', 'LAUNCH_DELETED': 'trash-2',
        'CREATE_LANCAMENTO': 'coins', 'FIRST_LAUNCH_DAY': 'sunrise',
        'CREATE_CATEGORIA': 'tag', 'CATEGORY_CREATED': 'tag',
        'DAILY_LOGIN': 'hand', 'DAILY_ACTIVITY': 'check-circle', 'VIEW_REPORT': 'bar-chart-3',
        'CREATE_META': 'target', 'META_ACHIEVED': 'trophy',
        'CLOSE_MONTH': 'calendar', 'POSITIVE_MONTH': 'heart',
        'STREAK_BONUS': 'flame', 'STREAK_3_DAYS': 'flame', 'STREAK_7_DAYS': 'flame', 'STREAK_30_DAYS': 'flame',
        'LEVEL_UP': 'star', 'ACHIEVEMENT_UNLOCKED': 'medal',
        'CARD_CREATED': 'credit-card', 'INVOICE_PAID': 'receipt'
    };
    return icons[action] || 'circle-dot';
}

// ─── Event Listeners ────────────────────────────────────────────────────────

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;

        fetch(`${BASE_URL}api/gamification/achievements`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.data?.achievements) {
                    renderAchievements(data.data.achievements);
                }
            });
    });
});

// ─── Init ───────────────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAllData);
} else {
    loadAllData();
}
