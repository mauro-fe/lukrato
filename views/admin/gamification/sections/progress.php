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