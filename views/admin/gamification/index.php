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
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon"><i data-lucide="trophy"></i></div>
            <div>
                <h1 id="pageHeaderTitle"><?= $firstName ? "{$firstName}, você está no Nível 1" : "Sua Jornada de Gamificação" ?></h1>
                <p id="pageHeaderSubtitle">Acompanhe seu progresso, conquistas e ranking</p>
            </div>
        </div>
        <div class="level-badge-large" id="userLevelLarge">
            <i data-lucide="star"></i>
            <span>Nível 1</span>
        </div>
    </div>

    <!-- Insight Banner -->
    <div class="insight-banner" id="insightBanner" style="display:none;">
        <div class="insight-icon"><i data-lucide="zap"></i></div>
        <div class="insight-text" id="insightText"></div>
        <button class="insight-dismiss" id="insightDismiss" aria-label="Fechar">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- Progresso Geral -->
    <section class="progress-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="star"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="totalPointsCard" data-animate="true">0</div>
                    <div class="stat-label">Pontos Totais</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="bar-chart-3"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="currentLevelCard" data-animate="true">1</div>
                    <div class="stat-label">Nível Atual</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="flame"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="currentStreakCard" data-animate="true">0</div>
                    <div class="stat-label">Dias Ativos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="target"></i></div>
                <div class="stat-content">
                    <div class="stat-value" id="achievementsCountCard">0</div>
                    <div class="stat-label">Conquistas</div>
                </div>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="level-progress-large">
            <div class="progress-header">
                <span>Progresso para o Nível <span id="nextLevel">2</span></span>
                <span class="progress-points" id="progressPointsLarge">0 / 300</span>
            </div>
            <div class="progress-bar-large" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                aria-label="Progresso para o próximo nível">
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
    <section class="missions-section" id="missionsSection" style="display:none;">
        <h2><i data-lucide="target"></i> Missões do Dia</h2>
        <div class="missions-grid" id="missionsGrid">
            <div class="lk-loading-state" style="grid-column:1/-1;">
                <i data-lucide="loader-2"></i>
                <p>Carregando missões...</p>
            </div>
        </div>
    </section>

    <!-- Conquistas -->
    <section class="achievements-section">
        <h2><i data-lucide="medal"></i> Conquistas</h2>

        <div class="achievements-filter">
            <button class="filter-btn active" data-filter="all">Todas</button>
            <button class="filter-btn" data-filter="unlocked">Desbloqueadas</button>
            <button class="filter-btn" data-filter="locked">Bloqueadas</button>
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
    <section class="history-section">
        <h2><i data-lucide="history"></i> Histórico Recente</h2>
        <div class="history-list" id="pointsHistory">
            <!-- Loading state -->
            <div class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Carregando histórico...</p>
            </div>
        </div>
    </section>

    <!-- Ranking -->
    <section class="leaderboard-section">
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
            <div class="leaderboard-locked">
                <div class="locked-icon">
                    <i data-lucide="crown"></i>
                </div>
                <h3><i data-lucide="trophy" style="width:20px;height:20px;display:inline-block;vertical-align:middle;"></i>
                    Ranking Exclusivo PRO</h3>
                <p>Compare seu progresso com outros usuários e veja sua posição no ranking global!</p>
                <div class="locked-features">
                    <div class="locked-feature">
                        <i data-lucide="medal"></i>
                        <span>Top 10 usuários</span>
                    </div>
                    <div class="locked-feature">
                        <i data-lucide="line-chart"></i>
                        <span>Sua posição no ranking</span>
                    </div>
                    <div class="locked-feature">
                        <i data-lucide="trophy"></i>
                        <span>Pontuação global</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>billing" class="btn-upgrade-ranking">
                    <i data-lucide="crown"></i>
                    <span>Fazer Upgrade para PRO</span>
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- JS carregado via Vite (loadPageJs) -->