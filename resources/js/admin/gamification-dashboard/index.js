/**
 * ============================================================================
 * LUKRATO — Gamification Dashboard Widget (Vite Module)
 * ============================================================================
 * Widget de gamificação exibido na página do Dashboard.
 * Carrega progresso, estatísticas e conquistas resumidas.
 * Usa window.GAMIFICATION global (gamification-global.js).
 *
 * Substitui: public/assets/js/gamification-dashboard.js
 * ============================================================================
 */

import { apiGet, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { escapeHtml } from '../shared/utils.js';

// ─── Globals ────────────────────────────────────────────────────────────────

const GAM = window.GAMIFICATION;
const formatNumber = GAM.formatNumber.bind(GAM);
const formatDate = GAM.formatDate.bind(GAM);
const BASE = getBaseUrl();

// ─── Achievement icon colors ────────────────────────────────────────────────

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

// ─── State ──────────────────────────────────────────────────────────────────

let isPro = false;
let currentProgress = {};

// ─── Core Functions ─────────────────────────────────────────────────────────

function refreshGamification() {
    loadGamificationProgress();
    loadGamificationStats();
    loadAchievements();
}

function initGamification() {
    const gamificationSection = document.querySelector('.gamification-section');
    if (!gamificationSection) return;

    loadGamificationProgress();
    loadGamificationStats();
    loadAchievements();

    const btnProUpgrade = document.querySelector('.btn-pro-upgrade');
    if (btnProUpgrade) {
        btnProUpgrade.addEventListener('click', showProUpgrade);
    }

    document.addEventListener('lukrato:month-changed', () => refreshGamification());
    document.addEventListener('lukrato:data-changed', () => refreshGamification());
}

// ─── Progress ───────────────────────────────────────────────────────────────

async function loadGamificationProgress() {
    try {
        const data = await apiGet(`${BASE}api/gamification/progress`);
        const isSuccess = data.success === true;
        if (isSuccess && data.data) {
            currentProgress = data.data;
            isPro = data.data.is_pro;
            updateProgressUI(data.data);
        }
    } catch (error) { /* silenciar */ }
}

function updateProgressUI(progress) {
    const proBadge = document.getElementById('proBadge');
    if (proBadge) proBadge.style.display = progress.is_pro ? 'inline-flex' : 'none';

    const proCTA = document.getElementById('proCTA');
    if (proCTA) proCTA.style.display = progress.is_pro ? 'none' : 'block';

    const levelBadge = document.getElementById('userLevel');
    if (levelBadge) {
        const span = levelBadge.querySelector('span');
        if (span) span.textContent = `Nível ${progress.current_level}`;
    }

    const progressBar = document.getElementById('levelProgressBar');
    const progressPoints = document.getElementById('levelProgressPoints');
    const progressText = document.getElementById('levelProgressText');

    if (progressBar && progressPoints) {
        const progressData = GAM.calculateProgress(progress.current_level, progress.total_points);

        progressBar.style.width = `${progressData.percentage}%`;

        if (progressData.isMaxLevel) {
            progressPoints.textContent = `${formatNumber(progress.total_points)} pontos`;
        } else {
            progressPoints.textContent = `${formatNumber(progressData.current)} / ${formatNumber(progressData.needed)} pontos`;
        }

        if (progressText) {
            if (progressData.isMaxLevel) {
                progressText.textContent = 'Nível máximo alcançado!';
            } else {
                const remaining = progress.points_to_next_level || 0;
                progressText.textContent = `Faltam ${formatNumber(remaining)} pontos para o próximo nível`;
            }
        }
    }

    const streakDays = document.getElementById('streakDays');
    if (streakDays) {
        streakDays.textContent = progress.current_streak || 0;
        if (progress.current_streak > 3) streakDays.classList.add('streak-fire');
    }

    const streakProtection = document.getElementById('streakProtection');
    if (streakProtection) streakProtection.style.display = progress.streak_protection_available ? 'flex' : 'none';

    const pontosTotal = document.getElementById('pontosTotal');
    if (pontosTotal) pontosTotal.textContent = formatNumber(progress.total_points || 0);
}

// ─── Stats ──────────────────────────────────────────────────────────────────

async function loadGamificationStats() {
    try {
        const data = await apiGet(`${BASE}api/gamification/stats`);
        const isSuccess = data.success === true;
        if (isSuccess && data.data) updateStatsUI(data.data);
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

function updateStatsUI(stats) {
    const totalLancamentos = document.getElementById('totalLancamentos');
    const totalCategorias = document.getElementById('totalCategorias');
    const mesesAtivos = document.getElementById('mesesAtivos');

    if (totalLancamentos) totalLancamentos.textContent = formatNumber(stats.total_lancamentos);
    if (totalCategorias) totalCategorias.textContent = formatNumber(stats.total_categorias);
    if (mesesAtivos) mesesAtivos.textContent = formatNumber(stats.meses_ativos);

    updateOrganizationProgress(stats);
}

function updateOrganizationProgress(stats) {
    const organizationBar = document.getElementById('organizationBar');
    const organizationPercentage = document.getElementById('organizationPercentage');
    const organizationText = document.getElementById('organizationText');

    const launchesProgress = Math.min(100, (stats.total_lancamentos / 50) * 100);
    const categoriesProgress = Math.min(100, (stats.total_categorias / 10) * 100);
    const overallProgress = (launchesProgress + categoriesProgress) / 2;

    if (organizationBar) organizationBar.style.width = `${overallProgress}%`;
    if (organizationPercentage) organizationPercentage.textContent = `${Math.round(overallProgress)}%`;

    if (organizationText) {
        if (overallProgress >= 100) organizationText.textContent = 'Parabéns! Você está super organizado!';
        else if (overallProgress >= 75) organizationText.textContent = 'Muito bem! Continue assim!';
        else if (overallProgress >= 50) organizationText.textContent = 'Bom progresso! Continue registrando!';
        else organizationText.textContent = 'Continue registrando seus lançamentos!';
    }
}

// ─── Current Month ──────────────────────────────────────────────────────────

function getCurrentMonth() {
    if (window.LukratoHeader && typeof window.LukratoHeader.getMonth === 'function') {
        return window.LukratoHeader.getMonth();
    }
    const stored = sessionStorage.getItem('lkMes');
    if (stored && /^\d{4}-(0[1-9]|1[0-2])$/.test(stored)) return stored;
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

// ─── Achievements ───────────────────────────────────────────────────────────

async function loadAchievements() {
    try {
        const month = getCurrentMonth();
        const data = await apiGet(`${BASE}api/gamification/achievements`, { month });
        const isSuccess = data.success === true;
        if (isSuccess && data.data) updateAchievementsUI(data.data.achievements);
    } catch (error) {
        console.error('Erro ao carregar conquistas:', error);
    }
}

function updateAchievementsUI(achievements) {
    const badgesGrid = document.getElementById('badgesGrid');
    if (!badgesGrid) return;

    const sorted = [...achievements].sort((a, b) => {
        const aUnlocked = a.unlocked || a.unlocked_ever;
        const bUnlocked = b.unlocked || b.unlocked_ever;
        if (aUnlocked && !bUnlocked) return -1;
        if (!aUnlocked && bUnlocked) return 1;
        if (aUnlocked && bUnlocked) {
            if (a.unlocked && !b.unlocked) return -1;
            if (!a.unlocked && b.unlocked) return 1;
        }
        return 0;
    });

    const displayAchievements = sorted.slice(0, 6);
    badgesGrid.innerHTML = '';

    displayAchievements.forEach(achievement => {
        const badgeItem = document.createElement('div');
        const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
        let statusClass = isUnlocked ? 'unlocked' : 'locked';

        badgeItem.className = `badge-item ${statusClass}`;
        if (achievement.is_pro_only && !isPro) badgeItem.classList.add('pro-only');
        badgeItem.title = achievement.description;

        let checkMark = '';
        if (isUnlocked) {
            checkMark = `<div class="badge-unlocked-check"><i data-lucide="check" style="width:14px;height:14px;"></i></div>`;
        }

        badgeItem.innerHTML = `
            <div class="badge-icon" style="color:${getAchievementIconColor(achievement.icon)}"><i data-lucide="${achievement.icon}"></i></div>
            <div class="badge-name">${escapeHtml(achievement.name)}</div>
            ${achievement.is_pro_only ? '<div class="badge-pro-tag">PRO</div>' : ''}
            ${checkMark}
        `;

        badgeItem.addEventListener('click', () => showAchievementDetail(achievement));
        badgesGrid.appendChild(badgeItem);
    });

    if (window.lucide) lucide.createIcons();
}

// ─── Achievement Detail ─────────────────────────────────────────────────────

function showAchievementDetail(achievement) {
    if (typeof Swal === 'undefined') return;

    const isUnlocked = achievement.unlocked || achievement.unlocked_ever;
    let statusHtml = '';

    if (achievement.unlocked) {
        statusHtml = `<p class="achievement-unlocked"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Desbloqueada neste mês${achievement.unlocked_at ? ` em ${formatDate(achievement.unlocked_at)}` : ''}</p>`;
    } else if (achievement.unlocked_ever) {
        statusHtml = `<p class="achievement-unlocked past"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Conquistada anteriormente</p>`;
    } else {
        statusHtml = '<p class="achievement-locked"><i data-lucide="lock" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Ainda não desbloqueada</p>';
    }

    Swal.fire({
        title: achievement.name,
        html: `
            <p class="achievement-description">${achievement.description}</p>
            <p class="achievement-points"><i data-lucide="star"></i> ${achievement.points_reward} pontos</p>
            ${achievement.is_pro_only ? '<p class="achievement-pro-tag"><i data-lucide="gem"></i> Conquista exclusiva Pro</p>' : ''}
            ${statusHtml}
        `,
        icon: isUnlocked ? 'success' : 'info',
        confirmButtonText: 'Fechar',
        customClass: { popup: 'achievement-modal', confirmButton: 'btn btn-primary' },
        didOpen: () => { if (window.lucide) lucide.createIcons(); }
    });
}

// ─── Show All Achievements Modal ────────────────────────────────────────────

async function showAllAchievements() {
    try {
        const data = await apiGet(`${BASE}api/gamification/achievements`);
        const isSuccess = data.success === true;
        if (!isSuccess || !data.data) return;

        const achievements = data.data.achievements;
        const stats = data.data.stats;

        let html = `
            <div class="achievements-modal-stats">
                <div class="stat-item"><div class="stat-value">${stats.unlocked_count}</div><div class="stat-label">Desbloqueadas</div></div>
                <div class="stat-item"><div class="stat-value">${stats.completion_percentage}%</div><div class="stat-label">Completado</div></div>
            </div>
            <div class="achievements-modal-grid">
        `;

        achievements.forEach(ach => {
            const status = ach.unlocked ? 'unlocked' : 'locked';
            const proTag = ach.is_pro_only ? '<span class="pro-tag">PRO</span>' : '';
            html += `
                <div class="achievement-modal-item ${status}">
                    <div class="achievement-icon" style="color:${getAchievementIconColor(ach.icon)}"><i data-lucide="${ach.icon}"></i></div>
                    <div class="achievement-info">
                        <div class="achievement-name">${escapeHtml(ach.name)} ${proTag}</div>
                        <div class="achievement-desc">${escapeHtml(ach.description)}</div>
                        <div class="achievement-points-small"><i data-lucide="star"></i> ${ach.points_reward} pts</div>
                    </div>
                    ${ach.unlocked ? '<div class="achievement-check"><i data-lucide="check"></i></div>' : ''}
                </div>
            `;
        });

        html += '</div>';

        Swal.fire({
            title: 'Suas Conquistas',
            html: html,
            width: '800px',
            confirmButtonText: 'Fechar',
            customClass: { popup: 'achievements-modal', confirmButton: 'btn btn-primary' },
            didOpen: () => { if (window.lucide) lucide.createIcons(); }
        });
    } catch (error) {
        console.error('Erro ao carregar conquistas:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(error, 'Nao foi possivel carregar as conquistas.') });
        }
    }
}

// ─── Pro Upgrade ────────────────────────────────────────────────────────────

function showProUpgrade() {
    const upgradeFeatures = [
        'Ganhe 1.5x mais pontos em todas as ações',
        'Proteção de streak: 1 dia grátis por mês',
        'Conquistas exclusivas com mais recompensas',
        'Acesso ao nível máximo 15',
    ];

    if (window.PlanLimits?.promptUpgrade) {
        window.PlanLimits.promptUpgrade({
            context: 'gamification',
            title: 'Plano Pro',
            message: 'Acelere seu progresso e desbloqueie vantagens exclusivas.',
            features: upgradeFeatures,
        }).catch(() => { /* ignore */ });
        return;
    }

    if (window.LKFeedback?.upgradePrompt) {
        window.LKFeedback.upgradePrompt({
            context: 'gamification',
            title: 'Plano Pro',
            message: 'Acelere seu progresso e desbloqueie vantagens exclusivas.',
            features: upgradeFeatures,
        }).catch(() => { /* ignore */ });
        return;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Plano Pro',
            text: 'Acelere seu progresso e desbloqueie vantagens exclusivas.',
            showCancelButton: true,
            confirmButtonText: 'Ver planos',
            cancelButtonText: 'Agora não',
        }).then((result) => {
            if (result.isConfirmed) window.location.href = `${BASE}billing`;
        });
        return;
    }

    window.location.href = `${BASE}billing`;
}

// ─── Points Notification (global) ───────────────────────────────────────────

window.notifyPointsGained = function (points, message = 'Pontos ganhos!') {
    if (typeof Swal === 'undefined') return;

    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 3000, timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({ icon: 'success', title: message, text: `+${points} pontos` });

    setTimeout(() => {
        loadGamificationProgress();
        loadGamificationStats();
    }, 500);
};

// ─── Init ───────────────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGamification);
} else {
    initGamification();
}
