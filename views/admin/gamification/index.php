<div class="gamification-page">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon">🏆</div>
            <div>
                <h1>Sua Jornada de Gamificação</h1>
                <p>Acompanhe seu progresso, conquistas e ranking</p>
            </div>
        </div>
        <div class="level-badge-large" id="userLevelLarge">
            <i class="fas fa-star"></i>
            <span>Nível 1</span>
        </div>
    </div>

    <!-- Progresso Geral -->
    <section class="progress-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <div class="stat-value" id="totalPointsCard">0</div>
                    <div class="stat-label">Pontos Totais</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-value" id="currentLevelCard">1</div>
                    <div class="stat-label">Nível Atual</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-content">
                    <div class="stat-value" id="currentStreakCard">0</div>
                    <div class="stat-label">Dias Consecutivos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
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
            <div class="progress-bar-large">
                <div class="progress-fill" id="progressFillLarge" style="width: 0%"></div>
            </div>
        </div>
    </section>

    <!-- Conquistas -->
    <section class="achievements-section">
        <h2><i class="fas fa-medal"></i> Conquistas</h2>

        <div class="achievements-filter">
            <button class="filter-btn active" data-filter="all">Todas</button>
            <button class="filter-btn" data-filter="unlocked">Desbloqueadas</button>
            <button class="filter-btn" data-filter="locked">Bloqueadas</button>
        </div>

        <div class="achievements-grid" id="achievementsGridPage">
            <!-- Preenchido via JavaScript -->
        </div>
    </section>

    <!-- Histórico de Pontos -->
    <section class="history-section">
        <h2><i class="fas fa-history"></i> Histórico Recente</h2>
        <div class="history-list" id="pointsHistory">
            <!-- Preenchido via JavaScript -->
        </div>
    </section>

    <!-- Ranking -->
    <section class="leaderboard-section">
        <h2><i class="fas fa-trophy"></i> Ranking</h2>
        <div class="leaderboard-container" id="leaderboardContainer">
            <!-- Preenchido via JavaScript -->
        </div>
    </section>
</div>

<script src="<?= BASE_URL ?>assets/js/gamification-page.js?v=<?= time() ?>"></script>