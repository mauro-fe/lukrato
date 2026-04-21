<!-- Conquistas -->
<section class="achievements-section surface-card surface-card--interactive" id="gamAchievementsSection">
    <div class="achievements-header">
        <div class="achievements-heading">
            <h2><i data-lucide="medal"></i> Conquistas</h2>
            <p class="achievements-subtitle">Acompanhe o que ja foi desbloqueado e o que ainda falta para completar sua colecao.</p>
        </div>

        <div class="achievements-filter">
            <button class="filter-btn surface-filter surface-filter--soft active" data-filter="all">Todas</button>
            <button class="filter-btn surface-filter surface-filter--soft" data-filter="unlocked">Desbloqueadas</button>
            <button class="filter-btn surface-filter surface-filter--soft" data-filter="locked">Bloqueadas</button>
        </div>
    </div>

    <div class="achievements-grid" id="achievementsGridPage">
        <!-- Loading state -->
        <div class="lk-loading-state" id="achievementsLoading" style="grid-column:1/-1;">
            <i data-lucide="loader-2"></i>
            <p>Carregando conquistas...</p>
        </div>
    </div>
</section>