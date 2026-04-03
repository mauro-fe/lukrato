<!-- Tab Panel: Feedback -->
<div class="sysadmin-tab-panel" id="panel-feedback" role="tabpanel" aria-labelledby="tab-feedback">
    <div class="feedback-admin-section" id="feedbackSection">
        <h2 class="section-title">
            <i data-lucide="message-square-heart"></i>
            Feedback dos Usuarios
            <button class="btn-refresh-stats" data-action="loadFeedbackStats" title="Atualizar feedback">
                <i data-lucide="refresh-cw" id="feedbackRefreshIcon"></i>
            </button>
        </h2>

        <!-- NPS Score Card -->
        <div class="feedback-nps-card">
            <div class="nps-score-display">
                <span class="nps-score-value" id="npsScoreValue">--</span>
                <span class="nps-score-label">NPS Score</span>
            </div>
            <div class="nps-breakdown">
                <div class="nps-segment promoters">
                    <span class="nps-segment-value" id="npsPromoters">0</span>
                    <span class="nps-segment-label">Promotores</span>
                </div>
                <div class="nps-segment passives">
                    <span class="nps-segment-value" id="npsPassives">0</span>
                    <span class="nps-segment-label">Neutros</span>
                </div>
                <div class="nps-segment detractors">
                    <span class="nps-segment-value" id="npsDetractors">0</span>
                    <span class="nps-segment-label">Detratores</span>
                </div>
            </div>
        </div>

        <!-- Tipo Cards -->
        <div class="feedback-tipo-cards">
            <div class="feedback-tipo-card tipo-acao">
                <div class="feedback-tipo-icon"><i data-lucide="mouse-pointer-click"></i></div>
                <div class="feedback-tipo-content">
                    <span class="feedback-tipo-value" id="statFbAcao">0</span>
                    <span class="feedback-tipo-label">Micro Feedback</span>
                    <span class="feedback-tipo-avg" id="statFbAcaoAvg">--</span>
                </div>
            </div>
            <div class="feedback-tipo-card tipo-ia">
                <div class="feedback-tipo-icon"><i data-lucide="bot"></i></div>
                <div class="feedback-tipo-content">
                    <span class="feedback-tipo-value" id="statFbIa">0</span>
                    <span class="feedback-tipo-label">Assistente IA</span>
                    <span class="feedback-tipo-avg" id="statFbIaAvg">--</span>
                </div>
            </div>
            <div class="feedback-tipo-card tipo-nps">
                <div class="feedback-tipo-icon"><i data-lucide="gauge"></i></div>
                <div class="feedback-tipo-content">
                    <span class="feedback-tipo-value" id="statFbNps">0</span>
                    <span class="feedback-tipo-label">NPS</span>
                    <span class="feedback-tipo-avg" id="statFbNpsAvg">--</span>
                </div>
            </div>
            <div class="feedback-tipo-card tipo-sugestao">
                <div class="feedback-tipo-icon"><i data-lucide="lightbulb"></i></div>
                <div class="feedback-tipo-content">
                    <span class="feedback-tipo-value" id="statFbSugestao">0</span>
                    <span class="feedback-tipo-label">Sugestoes</span>
                    <span class="feedback-tipo-avg" id="statFbSugestaoAvg">--</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="feedback-filters">
            <form id="feedbackFilters" class="feedback-filters-form">
                <select name="tipo_feedback" class="filter-select" id="feedbackFilterTipo">
                    <option value="">Todos os Tipos</option>
                    <option value="acao">Micro Feedback</option>
                    <option value="assistente_ia">Assistente IA</option>
                    <option value="nps">NPS</option>
                    <option value="sugestao">Sugestao</option>
                </select>
                <select name="per_page" class="filter-select" id="feedbackPerPage">
                    <option value="15">15 por pagina</option>
                    <option value="25">25 por pagina</option>
                    <option value="50">50 por pagina</option>
                </select>
                <button type="submit" class="btn-control primary"><i data-lucide="filter"></i> Filtrar</button>
                <button type="button" class="btn-control success" data-action="exportFeedback" title="Exportar CSV">
                    <i data-lucide="download"></i> Exportar
                </button>
            </form>
        </div>

        <!-- Feedback Table -->
        <div class="feedback-table-wrapper" id="feedbackTableWrapper">
            <div class="feedback-empty">
                <i data-lucide="message-square"></i>
                <p>Selecione a aba para carregar os feedbacks</p>
            </div>
        </div>
    </div>
</div>