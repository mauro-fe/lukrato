<!-- Histórico de Pontos -->
<section class="history-section surface-card surface-card--interactive" id="gamHistorySection" <?= !$showGamHistory ? ' style="display:none;"' : '' ?>>
    <h2><i data-lucide="history"></i> Histórico Recente</h2>
    <div class="history-list" id="pointsHistory">
        <!-- Loading state -->
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <p>Carregando histórico...</p>
        </div>
    </div>
</section>