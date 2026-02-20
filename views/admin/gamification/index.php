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
            <i data-lucide="star"></i>
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
            <div class="progress-bar-large" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso para o próximo nível">
                <div class="progress-fill" id="progressFillLarge" style="width: 0%"></div>
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
            <div class="lk-loading-state" id="achievementsLoading" style="grid-column:1/-1;text-align:center;padding:2rem;">
                <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">Carregando conquistas...</p>
            </div>
        </div>
    </section>

    <!-- Histórico de Pontos -->
    <section class="history-section">
        <h2><i data-lucide="history"></i> Histórico Recente</h2>
        <div class="history-list" id="pointsHistory">
            <!-- Loading state -->
            <div class="lk-loading-state" style="text-align:center;padding:2rem;">
                <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">Carregando histórico...</p>
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
                <div class="lk-loading-state" style="text-align:center;padding:2rem;">
                    <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">Carregando ranking...</p>
                </div>
            </div>
        <?php else: ?>
            <!-- CTA de Upgrade para acessar o Ranking -->
            <div class="leaderboard-locked">
                <div class="locked-icon">
                    <i data-lucide="crown"></i>
                </div>
                <h3>🏆 Ranking Exclusivo PRO</h3>
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

<script src="<?= BASE_URL ?>assets/js/gamification-page.js?v=<?= time() ?>"></script>