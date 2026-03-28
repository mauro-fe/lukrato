/**
 * ============================================================================
 * LUKRATO — Gamification Page (Vite Module)
 * ============================================================================
 * Página completa de gamificação: progresso, conquistas, histórico, ranking,
 * missões diárias e insights inteligentes.
 * Usa window.GAMIFICATION global (gamification-global.js).
 *
 * Substitui: public/assets/js/gamification-page.js
 * ============================================================================
 */

import { apiGet, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { toastError } from '../shared/ui.js';
import { escapeHtml, formatDate as sharedFormatDate } from '../shared/utils.js';

// ─── Globals ────────────────────────────────────────────────────────────────

const GAM = window.GAMIFICATION;
const formatNumber = GAM.formatNumber.bind(GAM);
const formatDate = GAM.formatDate.bind(GAM);
const BASE_URL = getBaseUrl();
const CURRENT_USER_ID = window.__LK_CONFIG?.userId ?? null;
const CURRENT_USERNAME = window.__LK_CONFIG?.username ?? '';

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
    pageHeaderTitle: document.getElementById('pageHeaderTitle'),
    pageHeaderSubtitle: document.getElementById('pageHeaderSubtitle'),
    userLevelLarge: document.getElementById('userLevelLarge'),
    totalPointsCard: document.getElementById('totalPointsCard'),
    currentLevelCard: document.getElementById('currentLevelCard'),
    currentStreakCard: document.getElementById('currentStreakCard'),
    achievementsCountCard: document.getElementById('achievementsCountCard'),
    nextLevel: document.getElementById('nextLevel'),
    progressPointsLarge: document.getElementById('progressPointsLarge'),
    progressFillLarge: document.getElementById('progressFillLarge'),
    progressRemaining: document.getElementById('progressRemaining'),
    achievementsGridPage: document.getElementById('achievementsGridPage'),
    pointsHistory: document.getElementById('pointsHistory'),
    leaderboardContainer: document.getElementById('leaderboardContainer'),
    leaderboardGap: document.getElementById('leaderboardGap'),
    missionsSection: document.getElementById('missionsSection'),
    missionsGrid: document.getElementById('missionsGrid'),
    missionsBadge: document.getElementById('missionsBadge'),
    missionsCountdown: document.getElementById('missionsCountdown'),
    missionsTotalReward: document.getElementById('missionsTotalReward'),
    insightBanner: document.getElementById('insightBanner'),
    insightText: document.getElementById('insightText'),
    insightDismiss: document.getElementById('insightDismiss'),
};

let currentFilter = 'all';
let cachedAchievements = null;
let cachedProgress = null;

// ─── Animated Number Counter ────────────────────────────────────────────────

function animateValue(el, from, to, duration = 800) {
    if (!el || from === to) {
        if (el) el.textContent = typeof to === 'string' ? to : formatNumber(to);
        return;
    }
    const startTime = performance.now();
    const isNumeric = typeof to === 'number';
    if (!isNumeric) {
        el.textContent = to;
        return;
    }

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const t = Math.min(elapsed / duration, 1);
        // easeOutCubic
        const eased = 1 - Math.pow(1 - t, 3);
        const current = Math.round(from + (to - from) * eased);
        el.textContent = formatNumber(current);
        if (t < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

// ─── Data Loading ───────────────────────────────────────────────────────────

async function loadAllData() {
    try {
        const [progressData, achievementsData, historyData, leaderboardData, missionsData] = await Promise.all([
            apiGet(`${BASE_URL}api/gamification/progress`),
            apiGet(`${BASE_URL}api/gamification/achievements`),
            apiGet(`${BASE_URL}api/gamification/history`, { limit: 20 }),
            apiGet(`${BASE_URL}api/gamification/leaderboard`).catch(() => null),
            apiGet(`${BASE_URL}api/gamification/missions`).catch(() => null),
        ]);

        updateProgressSection(progressData);
        updateAchievements(achievementsData);
        updatePointsHistory(historyData);
        updateLeaderboard(leaderboardData);
        updateMissions(missionsData);

        // Generate insights after all data is loaded
        generateInsights(progressData, missionsData);
    } catch (error) {
        console.error('[PAGE] Erro ao carregar dados:', error);
        toastError(getErrorMessage(error, 'Nao foi possivel carregar os dados da gamificacao'));
    }
}

// ─── Progress Section ───────────────────────────────────────────────────────

function updateProgressSection(data) {
    const isSuccess = data.success === true;
    if (!isSuccess) return;

    const progress = data.data;
    cachedProgress = progress;
    const level = progress.current_level || 1;
    const totalPoints = progress.total_points || 0;
    const streak = progress.current_streak || 0;

    // Personalized header with level name
    const firstName = CURRENT_USERNAME ? CURRENT_USERNAME.split(' ')[0] : '';
    const levelName = GAM.getLevelName(level);
    if (elements.pageHeaderTitle && firstName) {
        elements.pageHeaderTitle.textContent = `${firstName}, você é um ${levelName}!`;
    }

    if (elements.userLevelLarge) {
        elements.userLevelLarge.querySelector('span').textContent = `Nível ${level} · ${levelName}`;
    }

    // Animate stat values
    animateValue(elements.totalPointsCard, 0, totalPoints);
    animateValue(elements.currentLevelCard, 0, level);
    animateValue(elements.currentStreakCard, 0, streak);

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

    // Subtitle with contextual motivational message
    if (elements.pageHeaderSubtitle && !progressData.isMaxLevel) {
        const remaining = progressData.needed - progressData.current;
        const nextLevelName = GAM.getLevelName(nextLevel);
        if (progressData.percentage >= 80) {
            elements.pageHeaderSubtitle.textContent = `Quase lá! Só mais ${formatNumber(remaining)} pts para ${nextLevelName}`;
        } else if (progressData.percentage >= 40) {
            elements.pageHeaderSubtitle.textContent = `Bom ritmo! ${formatNumber(remaining)} pts para se tornar ${nextLevelName}`;
        } else {
            elements.pageHeaderSubtitle.textContent = `Nova jornada: conquiste ${formatNumber(remaining)} pts e se torne ${nextLevelName}`;
        }
    } else if (elements.pageHeaderSubtitle && progressData.isMaxLevel) {
        elements.pageHeaderSubtitle.textContent = 'Você alcançou o topo. Lenda do Lukrato!';
    }

    // Animated progress bar (start from 0, animate to real %)
    if (elements.progressFillLarge) {
        elements.progressFillLarge.style.width = '0%';
        setTimeout(() => {
            elements.progressFillLarge.style.width = `${progressData.percentage}%`;
        }, 300);
    }

    // Update milestone dots
    updateMilestones(progressData.percentage);

    // Contextual progress text below bar
    if (elements.progressRemaining && !progressData.isMaxLevel) {
        const remaining = progressData.needed - progressData.current;
        if (remaining <= 10) {
            elements.progressRemaining.innerHTML = `<span class="progress-urgent">Só mais ${formatNumber(remaining)} pontos! Não pare agora!</span>`;
        } else if (remaining <= 50) {
            elements.progressRemaining.innerHTML = `<span class="progress-close">Quase lá! Apenas ${formatNumber(remaining)} pontos!</span>`;
        } else {
            elements.progressRemaining.textContent = `Faltam ${formatNumber(remaining)} pontos para o próximo nível`;
        }
    } else if (elements.progressRemaining) {
        elements.progressRemaining.textContent = '';
    }
}

function updateMilestones(percentage) {
    document.querySelectorAll('.milestone').forEach(dot => {
        const pos = parseFloat(dot.style.left);
        if (percentage >= pos) {
            dot.classList.add('reached');
        } else {
            dot.classList.remove('reached');
        }
    });
}

// ─── Achievements ───────────────────────────────────────────────────────────

function updateAchievements(data) {
    const isSuccess = data.success === true;
    if (!isSuccess || !data.data?.achievements) return;

    const achievements = data.data.achievements;
    const stats = data.data.stats || {};
    const unlockedCount = stats.unlocked_count || achievements.filter(a => a.unlocked).length;

    cachedAchievements = achievements;

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

        // Determine card state classes
        let cardClass = 'achievement-card surface-card surface-card--interactive';
        if (isUnlocked) {
            cardClass += ' unlocked';
            // Check if recently unlocked (within 24h)
            if (achievement.unlocked_at) {
                const unlockedTime = new Date(achievement.unlocked_at).getTime();
                if (Date.now() - unlockedTime < 86400000) {
                    cardClass += ' recently-unlocked';
                }
            }
        } else if (achievement.progress && achievement.progress.current > 0) {
            cardClass += ' in-progress';
        }

        // Achievement progress bar for locked items with progress data
        let progressHtml = '';
        if (!isUnlocked && achievement.progress) {
            const pct = achievement.progress.target > 0
                ? Math.min(100, Math.round((achievement.progress.current / achievement.progress.target) * 100))
                : 0;
            progressHtml = `
                <div class="achievement-progress">
                    <div class="achievement-progress-fill" style="width:${pct}%"></div>
                </div>
                <span class="achievement-progress-label">${achievement.progress.current}/${achievement.progress.target} — ${pct}%</span>
            `;
        }

        // Status badge for recently unlocked
        const newBadge = cardClass.includes('recently-unlocked')
            ? '<span class="achievement-new-badge">NOVA!</span>'
            : '';

        return `
            <div class="${cardClass}" data-achievement='${JSON.stringify(achievement).replace(/'/g, "&#39;")}' style="cursor: pointer;">
                ${newBadge}
                <div class="achievement-icon" style="color:${getAchievementIconColor(achievement.icon)}"><i data-lucide="${achievement.icon}"></i></div>
                <div class="achievement-info">
                    <h3 class="achievement-title">${escapeHtml(achievement.name)}</h3>
                    <p class="achievement-description">${escapeHtml(achievement.description)}</p>
                    ${progressHtml}
                    <div class="achievement-meta">
                        <span class="achievement-points">+${achievement.points_reward} pts</span>
                        ${isUnlocked
                ? `<span class="achievement-date"><i data-lucide="check-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> ${formatDate(achievement.unlocked_at)}</span>`
                : '<span class="achievement-locked"><i data-lucide="lock" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Bloqueada</span>'
            }
                    </div>
                </div>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.achievement-card').forEach(card => {
        card.classList.add('fade-target');
        card.addEventListener('click', function () {
            const achievement = JSON.parse(this.dataset.achievement);
            showAchievementDetail(achievement);
        });
    });

    // IntersectionObserver for fade-in on scroll
    observeAchievementCards();

    if (window.lucide) lucide.createIcons();
}

let achievementObserver = null;
function observeAchievementCards() {
    if (achievementObserver) achievementObserver.disconnect();
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.achievement-card.fade-target').forEach(c => c.classList.add('visible'));
        return;
    }
    achievementObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                achievementObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.achievement-card.fade-target').forEach(card => {
        achievementObserver.observe(card);
    });
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
    const isSuccess = data.success === true;
    if (!isSuccess || !data.data) {
        if (elements.pointsHistory) {
            elements.pointsHistory.innerHTML = `
                <div class="empty-state-enhanced">
                    <i data-lucide="sparkles"></i>
                    <p>Nenhuma atividade ainda.<br>Registre seu primeiro lançamento para começar a ganhar pontos!</p>
                </div>`;
            if (window.lucide) lucide.createIcons();
        }
        return;
    }

    const history = data.data.history || [];
    if (!elements.pointsHistory) return;

    if (history.length === 0) {
        elements.pointsHistory.innerHTML = `
            <div class="empty-state-enhanced">
                <i data-lucide="sparkles"></i>
                <p>Nenhuma atividade ainda.<br>Registre seu primeiro lançamento para começar a ganhar pontos!</p>
            </div>`;
        if (window.lucide) lucide.createIcons();
        return;
    }

    elements.pointsHistory.innerHTML = history.map(action => {
        const highValue = action.points > 50 ? ' high-value' : '';
        return `
        <div class="history-item surface-card surface-card--interactive${highValue}">
            <div class="history-icon"><i data-lucide="${getActionIcon(action.action)}"></i></div>
            <div class="history-content">
                <div class="history-title">${escapeHtml(formatActionHumanized(action))}</div>
                <div class="history-date">${escapeHtml(action.relative_time || formatDate(action.created_at))}</div>
            </div>
            <div class="history-points ${action.points >= 0 ? 'positive' : 'negative'}">
                ${action.points >= 0 ? '+' : ''}${action.points} pts
            </div>
        </div>
    `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

// ─── Missions ───────────────────────────────────────────────────────────────

function updateMissions(data) {
    if (!data || !data.success || !data.data?.missions) {
        if (elements.missionsSection) elements.missionsSection.style.display = 'none';
        return;
    }

    const missions = data.data.missions;
    if (missions.length === 0) {
        if (elements.missionsSection) elements.missionsSection.style.display = 'none';
        return;
    }

    if (elements.missionsSection) elements.missionsSection.style.display = '';

    // Missions badge (completed count)
    const completedCount = missions.filter(m => m.completed).length;
    if (elements.missionsBadge) {
        elements.missionsBadge.textContent = `${completedCount}/${missions.length} concluídas`;
        elements.missionsBadge.classList.toggle('all-done', completedCount === missions.length);
        elements.missionsBadge.classList.toggle('surface-chip--success', completedCount === missions.length);
    }

    // Countdown to midnight
    startMissionsCountdown();

    // Total reward remaining
    const remainingReward = missions.filter(m => !m.completed).reduce((sum, m) => sum + (m.points_reward || 0), 0);
    if (elements.missionsTotalReward && remainingReward > 0) {
        elements.missionsTotalReward.innerHTML = `<i data-lucide="zap" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Complete todas e ganhe <strong>+${remainingReward} pts</strong> hoje!`;
        elements.missionsTotalReward.style.display = '';
    } else if (elements.missionsTotalReward && completedCount === missions.length) {
        elements.missionsTotalReward.innerHTML = `<i data-lucide="check-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Todas as missões concluídas hoje! Parabéns!`;
        elements.missionsTotalReward.classList.add('all-complete');
        elements.missionsTotalReward.style.display = '';
    }

    if (!elements.missionsGrid) return;

    elements.missionsGrid.innerHTML = missions.map(mission => {
        const pct = mission.progress.target > 0
            ? Math.min(100, Math.round((mission.progress.current / mission.progress.target) * 100))
            : 0;
        let extraClass = mission.completed ? ' completed' : '';
        if (!mission.completed && mission.progress.current === mission.progress.target - 1) {
            extraClass += ' almost-done';
        }

        return `
            <div class="mission-card surface-card surface-card--interactive${extraClass}">
                <div class="mission-header">
                    <div class="mission-icon"><i data-lucide="${escapeHtml(mission.icon)}"></i></div>
                    <div class="mission-info">
                        <p class="mission-title">${escapeHtml(mission.title)}</p>
                    </div>
                    <span class="mission-reward">${mission.completed ? '<i data-lucide="check" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i>' : `+${mission.points_reward} pts`}</span>
                </div>
                <div class="mission-progress-bar">
                    <div class="mission-progress-fill" style="width:${pct}%"></div>
                </div>
                <div class="mission-footer">
                    <span class="mission-status">${mission.completed
                ? '<i data-lucide="check-circle" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i> Concluída'
                : `${mission.progress.current}/${mission.progress.target}`
            }</span>
                    <span>${escapeHtml(mission.description)}</span>
                </div>
            </div>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

let countdownInterval = null;
function startMissionsCountdown() {
    if (countdownInterval) clearInterval(countdownInterval);
    function updateCountdown() {
        const now = new Date();
        const midnight = new Date(now);
        midnight.setHours(23, 59, 59, 999);
        const diff = midnight - now;
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        if (elements.missionsCountdown) {
            elements.missionsCountdown.textContent = `Renovam em ${hours}h ${minutes}min`;
        }
    }
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 60000);
}

// ─── Smart Insights ─────────────────────────────────────────────────────────

function generateInsights(progressData, missionsData) {
    if (!elements.insightBanner || !elements.insightText) return;

    // Check sessionStorage for dismissed insights
    const dismissed = sessionStorage.getItem('gamification_insight_dismissed');
    if (dismissed === 'true') return;

    const insights = [];
    const progress = progressData?.data;
    const missions = missionsData?.data?.missions;

    if (progress) {
        // Streak insight (best ever)
        if (progress.current_streak > 0 && progress.current_streak >= (progress.best_streak || 0)) {
            insights.push(`Seu streak de ${progress.current_streak} dias é o seu melhor! Continue assim.`);
        }

        // Close to next level
        const remaining = progress.points_to_next_level;
        if (remaining > 0 && remaining <= 50) {
            insights.push(`Você está a apenas ${formatNumber(remaining)} pontos do próximo nível!`);
        }

        // Points milestone
        const milestones = [10000, 5000, 2000, 1000, 500];
        for (const m of milestones) {
            if (progress.total_points >= m) {
                insights.push(`Você já passou dos ${formatNumber(m)} pontos!`);
                break;
            }
        }

        // Streak at risk (has streak but it's a new day signal)
        if (progress.current_streak > 3 && progress.streak_today === false) {
            insights.push(`Registre algo hoje para manter seu streak de ${progress.current_streak} dias!`);
        }
    }

    // Mission proximity
    if (missions) {
        const almostDone = missions.find(m => !m.completed && m.progress.current === m.progress.target - 1);
        if (almostDone) {
            insights.push(`Falta apenas 1 ação para completar "${almostDone.title}" e ganhar +${almostDone.points_reward} pts!`);
        }
        const allDone = missions.every(m => m.completed);
        if (allDone && missions.length > 0) {
            insights.push('Todas as missões do dia concluídas! Volte amanhã para novas missões.');
        }
    }

    // Achievement proximity (if cached)
    if (cachedAchievements) {
        const nearComplete = cachedAchievements.find(a => !a.unlocked && a.progress && a.progress.target > 0 && (a.progress.current / a.progress.target) >= 0.75);
        if (nearComplete) {
            const pct = Math.round((nearComplete.progress.current / nearComplete.progress.target) * 100);
            insights.push(`Sua conquista "${nearComplete.name}" está em ${pct}%! Falta pouco!`);
        }
    }

    if (insights.length === 0) return;

    elements.insightText.textContent = insights[0];
    elements.insightBanner.style.display = '';

    // Inline contextual insights
    const inlineBeforeAchievements = document.getElementById('insightBeforeAchievements');
    const inlineBeforeRanking = document.getElementById('insightBeforeRanking');

    if (inlineBeforeAchievements && missions && missions.some(m => !m.completed)) {
        inlineBeforeAchievements.innerHTML = '<i data-lucide="lightbulb" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Dica: Complete missões para desbloquear conquistas mais rápido';
        inlineBeforeAchievements.style.display = '';
    }
    if (inlineBeforeRanking) {
        inlineBeforeRanking.innerHTML = '<i data-lucide="lightbulb" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Cada ponto conta no ranking. Registre lançamentos diariamente!';
        inlineBeforeRanking.style.display = '';
    }

    if (window.lucide) lucide.createIcons();
}

// ─── Leaderboard ────────────────────────────────────────────────────────────

function updateLeaderboard(data) {
    if (!data) return;
    const isSuccess = data.success === true;
    if (!isSuccess || !data.data?.leaderboard) return;

    const leaderboard = data.data.leaderboard;
    const userPosition = data.data.user_position;
    if (!elements.leaderboardContainer) return;

    if (leaderboard.length === 0) {
        elements.leaderboardContainer.innerHTML = '<p class="empty-state">Nenhum usu&aacute;rio no ranking</p>';
        return;
    }

    elements.leaderboardContainer.innerHTML = `
        <table class="leaderboard-table">
            <thead><tr><th>Posi&ccedil;&atilde;o</th><th>Usu&aacute;rio</th><th>N&iacute;vel</th><th>Pontos</th></tr></thead>
            <tbody>
                ${leaderboard.map((user) => {
        const rankClass = user.position <= 3 ? `rank-${user.position}` : '';
        const isCurrentUser = CURRENT_USER_ID && user.user_id === CURRENT_USER_ID;
        const currentUserClass = isCurrentUser ? ' current-user' : '';
        const rankIcon = user.position === 1 ? '<i data-lucide="medal" style="color:#fbbf24;"></i>'
            : user.position === 2 ? '<i data-lucide="medal" style="color:#94a3b8;"></i>'
                : user.position === 3 ? '<i data-lucide="medal" style="color:#d97706;"></i>' : '';
        const nomeCurto = (user.user_name || '').trim().split(' ').slice(0, 2).join(' ');
        const avatarHtml = user.avatar
            ? `<img src="${escapeHtml(user.avatar)}" alt="" class="leaderboard-avatar">`
            : `<span class="leaderboard-avatar leaderboard-avatar-fallback">${escapeHtml((nomeCurto || 'U')[0].toUpperCase())}</span>`;

        return `
                        <tr class="${rankClass}${currentUserClass}">
                            <td class="rank-cell" data-label="Posicao">
                                <span class="rank-pill">${rankIcon}<span>${user.position}&ordm;</span></span>
                            </td>
                            <td class="user-cell" data-label="Usuario">
                                <div class="user-info">
                                    ${avatarHtml}
                                    <div class="user-meta">
                                        <strong>${escapeHtml(nomeCurto)}${isCurrentUser ? ' (Você)' : ''}</strong>
                                        <span class="user-meta-label">Ranking global</span>
                                    </div>
                                </div>
                            </td>
                            <td class="level-cell" data-label="Nivel">
                                <span class="level-badge">N&iacute;vel ${user.current_level}</span>
                            </td>
                            <td class="points-cell" data-label="Pontos">
                                <strong>${formatNumber(user.total_points)}</strong>
                                <span>pts</span>
                            </td>
                        </tr>
                    `;
    }).join('')}
            </tbody>
        </table>
    `;

    // Points gap message
    if (elements.leaderboardGap && CURRENT_USER_ID && userPosition) {
        const currentUserEntry = leaderboard.find(u => u.user_id === CURRENT_USER_ID);
        if (currentUserEntry && currentUserEntry.position > 1) {
            const above = leaderboard.find(u => u.position === currentUserEntry.position - 1);
            if (above) {
                const gap = above.total_points - currentUserEntry.total_points;
                if (gap > 0) {
                    elements.leaderboardGap.innerHTML = `<i data-lucide="trending-up"></i> Você está a <strong>${formatNumber(gap)} pts</strong> do ${currentUserEntry.position - 1}º lugar`;
                    elements.leaderboardGap.style.display = '';
                }
            }
        } else if (!currentUserEntry && userPosition > 10) {
            elements.leaderboardGap.innerHTML = `<i data-lucide="trending-up"></i> Você está na posição <strong>${userPosition}º</strong>. Continue ganhando pontos para entrar no Top 10!`;
            elements.leaderboardGap.style.display = '';
        }
    }

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

    // Progress bar in modal for locked achievements
    let progressHtml = '';
    if (!achievement.unlocked && achievement.progress) {
        const pct = achievement.progress.target > 0
            ? Math.min(100, Math.round((achievement.progress.current / achievement.progress.target) * 100))
            : 0;
        progressHtml = `
            <div style="margin-top:12px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-bottom:6px;">
                    <span>Progresso</span><span>${achievement.progress.current}/${achievement.progress.target}</span>
                </div>
                <div style="height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden;">
                    <div style="height:100%;width:${pct}%;background:linear-gradient(90deg,#e67e22,#f59e0b);border-radius:3px;"></div>
                </div>
            </div>
        `;
    }

    const proTag = achievement.is_pro_only
        ? '<p style="color: #f59e0b; font-weight: 600; margin-top: 10px;"><i data-lucide="gem"></i> Conquista exclusiva PRO</p>'
        : '';

    Swal.fire({
        title: achievement.name,
        html: `
            <div style="font-size:2rem;margin-bottom:10px;color:${getAchievementIconColor(achievement.icon)}"><i data-lucide="${achievement.icon}"></i></div>
            <p style="font-size: 16px; color: #64748b; margin-bottom: 15px;">${achievement.description}</p>
            <p style="font-size: 18px; color: #f59e0b; font-weight: 700;">
                <i data-lucide="star" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> ${achievement.points_reward} pontos
            </p>
            ${proTag}
            ${progressHtml}
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

function formatActionHumanized(action) {
    const humanized = {
        'CREATE_LANCAMENTO': 'Lançamento registrado — cada registro conta!',
        'create_lancamento': 'Lançamento registrado — cada registro conta!',
        'CREATE_CATEGORIA': 'Nova categoria criada — organização é poder!',
        'create_categoria': 'Nova categoria criada — organização é poder!',
        'VIEW_REPORT': 'Relatório visualizado — conhecimento é progresso!',
        'view_report': 'Relatório visualizado — conhecimento é progresso!',
        'CREATE_META': 'Nova meta definida — foco no objetivo!',
        'create_meta': 'Nova meta definida — foco no objetivo!',
        'CLOSE_MONTH': 'Mês fechado com sucesso!',
        'close_month': 'Mês fechado com sucesso!',
        'DAILY_ACTIVITY': 'Presença marcada! Mais um dia ativo ✓',
        'daily_activity': 'Presença marcada! Mais um dia ativo ✓',
        'STREAK_3_DAYS': '3 dias seguidos! O hábito está se formando!',
        'streak_3_days': '3 dias seguidos! O hábito está se formando!',
        'STREAK_7_DAYS': '7 dias seguidos! Você está on fire! 🔥',
        'streak_7_days': '7 dias seguidos! Você está on fire! 🔥',
        'STREAK_30_DAYS': '30 dias seguidos! Consistência de mestre! 🏆',
        'streak_30_days': '30 dias seguidos! Consistência de mestre! 🏆',
        'POSITIVE_MONTH': 'Mês no verde! Suas finanças agradecem 💰',
        'positive_month': 'Mês no verde! Suas finanças agradecem 💰',
        'LEVEL_UP': 'Level up! Novo patamar desbloqueado! ⭐',
        'level_up': 'Level up! Novo patamar desbloqueado! ⭐',
        'COMPLETE_ONBOARDING': 'Onboarding concluído — bem-vindo à jornada!',
        'complete_onboarding': 'Onboarding concluído — bem-vindo à jornada!',
        'LAUNCH_CREATED': 'Lançamento registrado — cada registro conta!',
        'LAUNCH_EDITED': 'Lançamento ajustado — precisão é tudo!',
        'LAUNCH_DELETED': 'Lançamento removido',
        'CATEGORY_CREATED': 'Nova categoria criada — organização é poder!',
        'DAILY_LOGIN': 'Presença marcada! Mais um dia ativo ✓',
        'STREAK_BONUS': 'Bônus de sequência conquistado! 🔥',
        'ACHIEVEMENT_UNLOCKED': 'Conquista desbloqueada! 🏆',
        'FIRST_LAUNCH_DAY': 'Primeiro registro do dia — bom começo!',
        'CARD_CREATED': 'Cartão cadastrado — controle total!',
        'INVOICE_PAID': 'Fatura paga — responsabilidade em dia!',
    };

    const desc = action.description;
    if (desc && !desc.match(/^[A-Z_]+$/)) return desc;

    return humanized[action.action] || action.description || action.action;
}

function getActionIcon(action) {
    const icons = {
        'LAUNCH_CREATED': 'coins', 'LAUNCH_EDITED': 'pencil', 'LAUNCH_DELETED': 'trash-2',
        'CREATE_LANCAMENTO': 'coins', 'create_lancamento': 'coins', 'FIRST_LAUNCH_DAY': 'sunrise',
        'CREATE_CATEGORIA': 'tag', 'create_categoria': 'tag', 'CATEGORY_CREATED': 'tag',
        'DAILY_LOGIN': 'hand', 'DAILY_ACTIVITY': 'check-circle', 'daily_activity': 'check-circle',
        'VIEW_REPORT': 'bar-chart-3', 'view_report': 'bar-chart-3',
        'CREATE_META': 'target', 'create_meta': 'target', 'META_ACHIEVED': 'trophy',
        'CLOSE_MONTH': 'calendar', 'close_month': 'calendar',
        'POSITIVE_MONTH': 'heart', 'positive_month': 'heart',
        'STREAK_BONUS': 'flame', 'STREAK_3_DAYS': 'flame', 'streak_3_days': 'flame',
        'STREAK_7_DAYS': 'flame', 'streak_7_days': 'flame',
        'STREAK_30_DAYS': 'flame', 'streak_30_days': 'flame',
        'LEVEL_UP': 'star', 'level_up': 'star',
        'ACHIEVEMENT_UNLOCKED': 'medal',
        'CARD_CREATED': 'credit-card', 'INVOICE_PAID': 'receipt',
        'COMPLETE_ONBOARDING': 'graduation-cap', 'complete_onboarding': 'graduation-cap',
    };
    return icons[action] || 'circle-dot';
}

// ─── Event Listeners ────────────────────────────────────────────────────────

// Achievement filters
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;

        if (cachedAchievements) {
            renderAchievements(cachedAchievements);
        } else {
            apiGet(`${BASE_URL}api/gamification/achievements`)
                .then(data => {
                    if (data.data?.achievements) {
                        cachedAchievements = data.data.achievements;
                        renderAchievements(cachedAchievements);
                    }
                });
        }
    });
});

// Insight dismiss
if (elements.insightDismiss) {
    elements.insightDismiss.addEventListener('click', () => {
        if (elements.insightBanner) elements.insightBanner.style.display = 'none';
        sessionStorage.setItem('gamification_insight_dismissed', 'true');
    });
}

// Listen for data changes (new lancamento, etc.)
document.addEventListener('lukrato:data-changed', () => {
    loadAllData();
});

// ─── Init ───────────────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAllData);
} else {
    loadAllData();
}
