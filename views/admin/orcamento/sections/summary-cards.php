<!-- ==================== CARDS RESUMO ==================== -->
<div class="orc-summary-grid" id="summaryOrcamentos" data-aos="fade-up">
    <div class="orc-summary-card orc-summary-card--saude surface-card surface-card--interactive">
        <div class="orc-saude-content" id="saudeContent">
            <div class="orc-summary-card__icon">
                <div class="orc-saude-ring" id="saudeRing">
                    <svg viewBox="0 0 36 36">
                        <path class="orc-ring-bg"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="orc-ring-fill" id="saudeRingFill" stroke-dasharray="100, 100"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="orc-ring-text" id="saudeScore">--</span>
                </div>
            </div>
            <div class="orc-summary-card__info">
                <span class="orc-summary-card__label">Saúde Financeira</span>
                <span class="orc-summary-card__status" id="saudeLabel">Carregando...</span>
            </div>
        </div>
        <div class="orc-saude-cta" id="saudeCta" style="display:none">
            <div class="orc-saude-cta__icon">
                <i data-lucide="heart-pulse"></i>
            </div>
            <div class="orc-saude-cta__text">
                <span class="orc-summary-card__label">Saúde Financeira</span>
                <span class="orc-saude-cta__msg">Defina orçamentos nas categorias para acompanhar sua saúde
                    financeira</span>
            </div>
        </div>
    </div>

    <div class="orc-summary-card surface-card surface-card--interactive">
        <div class="orc-summary-card__icon orc-summary-card__icon--blue">
            <i data-lucide="wallet"></i>
        </div>
        <div class="orc-summary-card__info">
            <span class="orc-summary-card__label">Orçado</span>
            <span class="orc-summary-card__value" id="totalOrcado">R$ --</span>
        </div>
    </div>

    <div class="orc-summary-card surface-card surface-card--interactive">
        <div class="orc-summary-card__icon orc-summary-card__icon--orange">
            <i data-lucide="receipt"></i>
        </div>
        <div class="orc-summary-card__info">
            <span class="orc-summary-card__label">Gasto</span>
            <span class="orc-summary-card__value" id="totalGasto">R$ --</span>
        </div>
    </div>

    <div class="orc-summary-card surface-card surface-card--interactive">
        <div class="orc-summary-card__icon orc-summary-card__icon--green">
            <i data-lucide="piggy-bank"></i>
        </div>
        <div class="orc-summary-card__info">
            <span class="orc-summary-card__label">Disponível</span>
            <span class="orc-summary-card__value" id="totalDisponivel">R$ --</span>
        </div>
    </div>
</div>