<div id="finSummarySection">
    <!-- ==================== CARDS RESUMO: ORÇAMENTOS ==================== -->
    <div class="fin-summary-grid" id="summaryOrcamentos" data-aos="fade-up">
        <!-- Saúde Financeira -->
        <div class="summary-card saude-card">
            <div class="saude-content" id="saudeContent">
                <div class="summary-icon">
                    <div class="saude-ring" id="saudeRing">
                        <svg viewBox="0 0 36 36">
                            <path class="ring-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="ring-fill" id="saudeRingFill" stroke-dasharray="100, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="ring-text" id="saudeScore">--</span>
                    </div>
                </div>
                <div class="summary-info">
                    <span class="summary-label">Saúde Financeira</span>
                    <span class="summary-status" id="saudeLabel">Carregando...</span>
                </div>
            </div>
            <div class="saude-cta" id="saudeCta" style="display:none">
                <div class="saude-cta-icon">
                    <i data-lucide="heart-pulse"></i>
                </div>
                <div class="saude-cta-text">
                    <span class="summary-label">Saúde Financeira</span>
                    <span class="saude-cta-msg">Defina orçamentos nas categorias para acompanhar sua saúde
                        financeira</span>
                </div>
            </div>
        </div>

        <!-- Total Orçado -->
        <div class="summary-card">
            <div class="summary-icon blue">
                <i data-lucide="wallet"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Orçado</span>
                <span class="summary-value" id="totalOrcado">R$ --</span>
            </div>
        </div>

        <!-- Total Gasto -->
        <div class="summary-card">
            <div class="summary-icon orange">
                <i data-lucide="receipt"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Gasto</span>
                <span class="summary-value" id="totalGasto">R$ --</span>
            </div>
        </div>

        <!-- Disponível -->
        <div class="summary-card">
            <div class="summary-icon green">
                <i data-lucide="piggy-bank" style="color: white"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Disponível</span>
                <span class="summary-value" id="totalDisponivel">R$ --</span>
            </div>
        </div>
    </div>

    <!-- ==================== CARDS RESUMO: METAS ==================== -->
    <div class="fin-summary-grid" id="summaryMetas" data-aos="fade-up" style="display:none;">
        <!-- Metas Ativas -->
        <div class="summary-card">
            <div class="summary-icon purple">
                <i data-lucide="target"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Metas Ativas</span>
                <span class="summary-value" id="metasAtivas">--</span>
            </div>
        </div>

        <!-- Total Acumulado -->
        <div class="summary-card">
            <div class="summary-icon green">
                <i data-lucide="coins"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Acumulado</span>
                <span class="summary-value" id="metasTotalAtual">R$ --</span>
            </div>
        </div>

        <!-- Objetivo Total -->
        <div class="summary-card">
            <div class="summary-icon blue">
                <i data-lucide="flag" style="color: var(--color-primary)"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Objetivo Total</span>
                <span class="summary-value" id="metasTotalAlvo">R$ --</span>
            </div>
        </div>

        <!-- Progresso Geral -->
        <div class="summary-card">
            <div class="summary-icon">
                <div class="saude-ring" id="metasProgressRing">
                    <svg viewBox="0 0 36 36">
                        <path class="ring-bg"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="ring-fill score-good" id="metasProgressRingFill" stroke-dasharray="0, 100"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="ring-text" id="metasProgressScore">0%</span>
                </div>
            </div>
            <div class="summary-info">
                <span class="summary-label">Progresso Geral</span>
                <span class="summary-status status-good" id="metasProgressLabel">--</span>
            </div>
        </div>
    </div>
</div>