<?php
$firstName = '';
if (!empty($currentUser->nome)) {
    $firstName = explode(' ', trim($currentUser->nome))[0];
} elseif (!empty($username)) {
    $firstName = explode(' ', trim($username))[0];
}
$firstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
?>
<div class="gamification-page">
    <div class="gam-customize-trigger">
        <button class="gam-customize-open" id="btnCustomizeGamification" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>

    <div class="page-header surface-card surface-card--interactive" id="gamHeaderSection">
        <div class="page-header-content">
            <div class="page-icon"><i data-lucide="trophy"></i></div>
            <div>
                <h1 id="pageHeaderTitle">
                    <?= $firstName ? "{$firstName}, você está no Nível 1" : "Sua Jornada de Gamificação" ?></h1>
                <p id="pageHeaderSubtitle">Acompanhe seu progresso, conquistas e ranking</p>
            </div>
        </div>
        <div class="level-badge-large surface-chip surface-chip--soft surface-chip--lg" id="userLevelLarge">
            <i data-lucide="star"></i>
            <span>Nível 1</span>
        </div>
    </div>

    <!-- Insight Banner -->
    <div class="insight-banner surface-card" id="insightBanner" style="display:none;">
        <div class="insight-icon"><i data-lucide="zap"></i></div>
        <div class="insight-text" id="insightText"></div>
        <button class="insight-dismiss" id="insightDismiss" aria-label="Fechar">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- Progresso Geral -->
    <section class="progress-section" id="gamProgressSection">
        <div class="stats-grid">
            <div class="stat-card surface-card surface-card--interactive">
                <div class="stat-icon"><i data-lucide="star"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="totalPointsCard" data-animate="true">0</div>
                    <div class="stat-label">Pontos Totais</div>
                </div>
            </div>
            <div class="stat-card surface-card surface-card--interactive">
                <div class="stat-icon"><i data-lucide="bar-chart-3"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="currentLevelCard" data-animate="true">1</div>
                    <div class="stat-label">Nível Atual</div>
                </div>
            </div>
            <div class="stat-card surface-card surface-card--interactive">
                <div class="stat-icon"><i data-lucide="flame"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="currentStreakCard" data-animate="true">0</div>
                    <div class="stat-label">Dias Ativos</div>
                </div>
            </div>
            <div class="stat-card surface-card surface-card--interactive">
                <div class="stat-icon"><i data-lucide="target"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="achievementsCountCard">0</div>
                    <div class="stat-label">Conquistas</div>
                </div>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="level-progress-large surface-card surface-card--interactive">
            <div class="progress-header">
                <span>Progresso para o Nível <span id="nextLevel">2</span></span>
                <span class="progress-points" id="progressPointsLarge">0 / 300</span>
            </div>
            <div class="progress-bar-large surface-card" role="progressbar" aria-valuenow="0" aria-valuemin="0"
                aria-valuemax="100" aria-label="Progresso para o próximo nível">
                <div class="progress-milestones">
                    <span class="milestone" style="left:25%"></span>
                    <span class="milestone" style="left:50%"></span>
                    <span class="milestone" style="left:75%"></span>
                </div>
                <div class="progress-fill" id="progressFillLarge" style="width: 0%"></div>
            </div>
            <div class="progress-remaining" id="progressRemaining"></div>
        </div>
    </section>

    <!-- Missões do Dia -->
    <section class="missions-section surface-card surface-card--interactive" id="missionsSection" style="display:none;">
        <div class="missions-header">
            <h2><i data-lucide="target"></i> Missões do Dia</h2>
            <div class="missions-meta">
                <span class="missions-badge surface-chip surface-chip--highlight surface-chip--compact"
                    id="missionsBadge"></span>
                <span class="missions-countdown" id="missionsCountdown"></span>
            </div>
        </div>
        <div class="missions-grid" id="missionsGrid">
            <div class="lk-loading-state" style="grid-column:1/-1;">
                <i data-lucide="loader-2"></i>
                <p>Carregando missões...</p>
            </div>
        </div>
        <div class="missions-total-reward" id="missionsTotalReward" style="display:none;"></div>
    </section>

    <!-- Inline insight -->
    <div class="insight-inline" id="insightBeforeAchievements" style="display:none;"></div>

    <!-- Conquistas -->
    <section class="achievements-section surface-card surface-card--interactive" id="gamAchievementsSection">
        <h2><i data-lucide="medal"></i> Conquistas</h2>

        <div class="achievements-filter">
            <button class="filter-btn surface-filter surface-filter--soft active" data-filter="all">Todas</button>
            <button class="filter-btn surface-filter surface-filter--soft" data-filter="unlocked">Desbloqueadas</button>
            <button class="filter-btn surface-filter surface-filter--soft" data-filter="locked">Bloqueadas</button>
        </div>

        <div class="achievements-grid" id="achievementsGridPage">
            <!-- Loading state -->
            <div class="lk-loading-state" id="achievementsLoading" style="grid-column:1/-1;">
                <i data-lucide="loader-2"></i>
                <p>Carregando conquistas...</p>
            </div>
        </div>
    </section>

    <!-- Histórico de Pontos -->
    <section class="history-section surface-card surface-card--interactive" id="gamHistorySection">
        <h2><i data-lucide="history"></i> Histórico Recente</h2>
        <div class="history-list" id="pointsHistory">
            <!-- Loading state -->
            <div class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Carregando histórico...</p>
            </div>
        </div>
    </section>

    <!-- Inline insight -->
    <div class="insight-inline" id="insightBeforeRanking" style="display:none;"></div>

    <!-- Ranking -->
    <section class="leaderboard-section surface-card surface-card--interactive" id="gamLeaderboardSection">
        <h2><i data-lucide="trophy"></i> Ranking</h2>

        <?php if ($isPro ?? false): ?>
            <!-- Ranking para usuários PRO -->
            <div class="leaderboard-container" id="leaderboardContainer">
                <!-- Loading state -->
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando ranking...</p>
                </div>
            </div>
            <div class="leaderboard-gap" id="leaderboardGap" style="display:none;"></div>
        <?php else: ?>
            <!-- CTA de Upgrade para acessar o Ranking -->
            <div class="leaderboard-locked surface-card surface-card--clip">
                <div class="locked-icon">
                    <i data-lucide="crown"></i>
                </div>
                <h3><i data-lucide="trophy" style="width:20px;height:20px;display:inline-block;vertical-align:middle;"></i>
                    Ranking Exclusivo PRO</h3>
                <p>Compare seu progresso com outros usuários e veja sua posição no ranking global!</p>
                <div class="locked-features">
                    <div class="locked-feature surface-control-box">
                        <i data-lucide="medal"></i>
                        <span>Top 10 usuários</span>
                    </div>
                    <div class="locked-feature surface-control-box">
                        <i data-lucide="line-chart"></i>
                        <span>Sua posição no ranking</span>
                    </div>
                    <div class="locked-feature surface-control-box">
                        <i data-lucide="trophy"></i>
                        <span>Pontuação global</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>billing"
                    class="btn-upgrade-ranking surface-button surface-button--upgrade surface-button--lg">
                    <i data-lucide="crown"></i>
                    <span>Fazer Upgrade para PRO</span>
                </a>
            </div>
        <?php endif; ?>
    </section>

    <div class="gam-customize-overlay" id="gamificationCustomizeModalOverlay" style="display:none;">
        <div class="gam-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="gamificationCustomizeModalTitle">
            <div class="gam-customize-header">
                <h3 class="gam-customize-title" id="gamificationCustomizeModalTitle">Personalizar gamificacao</h3>
                <button class="gam-customize-close" id="btnCloseCustomizeGamification" type="button"
                    aria-label="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="gam-customize-body">
                <p class="gam-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

                <div class="gam-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="gam-customize-preset" id="btnPresetEssencialGamification" type="button">Modo
                        essencial</button>
                    <button class="gam-customize-preset" id="btnPresetCompletoGamification" type="button">Modo
                        completo</button>
                </div>

                <div class="gam-customize-group">
                    <p class="gam-customize-group-title">Blocos da tela</p>
                    <label class="gam-customize-toggle">
                        <span>Cabecalho</span>
                        <input type="checkbox" id="toggleGamHeader" checked>
                    </label>
                    <label class="gam-customize-toggle">
                        <span>Progresso geral</span>
                        <input type="checkbox" id="toggleGamProgress" checked>
                    </label>
                    <label class="gam-customize-toggle">
                        <span>Conquistas</span>
                        <input type="checkbox" id="toggleGamAchievements" checked>
                    </label>
                    <label class="gam-customize-toggle">
                        <span>Historico recente</span>
                        <input type="checkbox" id="toggleGamHistory" checked>
                    </label>
                    <label class="gam-customize-toggle">
                        <span>Ranking</span>
                        <input type="checkbox" id="toggleGamLeaderboard" checked>
                    </label>
                </div>
            </div>

            <div class="gam-customize-footer">
                <button class="gam-customize-save" id="btnSaveCustomizeGamification" type="button">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- JS carregado via Vite (loadPageJs) -->
