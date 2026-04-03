<!-- ==================== CARDS RESUMO ==================== -->
<div class="met-summary-grid" id="summaryMetas" data-aos="fade-up">
    <div class="met-summary-card surface-card surface-card--interactive">
        <div class="met-summary-card__icon met-summary-card__icon--purple">
            <i data-lucide="target"></i>
        </div>
        <div class="met-summary-card__info">
            <span class="met-summary-card__label">Metas Ativas</span>
            <span class="met-summary-card__value" id="metasAtivas">--</span>
        </div>
    </div>

    <div class="met-summary-card surface-card surface-card--interactive">
        <div class="met-summary-card__icon met-summary-card__icon--green">
            <i data-lucide="coins"></i>
        </div>
        <div class="met-summary-card__info">
            <span class="met-summary-card__label">Acumulado</span>
            <span class="met-summary-card__value" id="metasTotalAtual">R$ --</span>
        </div>
    </div>

    <div class="met-summary-card surface-card surface-card--interactive">
        <div class="met-summary-card__icon met-summary-card__icon--blue">
            <i data-lucide="flag"></i>
        </div>
        <div class="met-summary-card__info">
            <span class="met-summary-card__label">Objetivo Total</span>
            <span class="met-summary-card__value" id="metasTotalAlvo">R$ --</span>
        </div>
    </div>

    <div class="met-summary-card surface-card surface-card--interactive">
        <div class="met-summary-card__icon">
            <div class="met-progress-ring" id="metasProgressRing">
                <svg viewBox="0 0 36 36">
                    <path class="met-ring-bg"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="met-ring-fill met-ring-fill--good" id="metasProgressRingFill" stroke-dasharray="0, 100"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="met-ring-text" id="metasProgressScore">0%</span>
            </div>
        </div>
        <div class="met-summary-card__info">
            <span class="met-summary-card__label">Progresso Geral</span>
            <span class="met-summary-card__status met-status--good" id="metasProgressLabel">--</span>
        </div>
    </div>
</div>