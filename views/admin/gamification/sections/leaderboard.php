<!-- Ranking -->
<section class="leaderboard-section surface-card surface-card--interactive" id="gamLeaderboardSection">
    <div class="leaderboard-header">
        <div class="leaderboard-heading">
            <h2><i data-lucide="trophy"></i> Ranking</h2>
            <p class="leaderboard-subtitle">Veja quem esta puxando a frente no ranking global e quanto falta para voce subir de colocacao.</p>
        </div>
        <?php if ($isPro ?? false): ?>
            <span class="leaderboard-chip surface-chip surface-chip--soft surface-chip--compact">Top 10 global</span>
        <?php endif; ?>
    </div>

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