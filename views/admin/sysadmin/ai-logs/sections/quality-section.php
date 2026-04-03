<div class="quality-section" id="qualitySection">
    <h2 class="section-title">
        <i data-lucide="activity"></i>
        Qualidade da IA
    </h2>
    <div class="metrics-grid">
        <div class="metric-card warning">
            <div class="metric-value" id="metricLowConf">-</div>
            <div class="metric-label">Baixa confianca</div>
        </div>
        <div class="metric-card warning">
            <div class="metric-value" id="metricFallback">-</div>
            <div class="metric-label">Fallback para chat</div>
        </div>
    </div>
    <div class="quality-panels">
        <div class="metric-card quality-panel">
            <div class="metric-label quality-panel-title">Distribuicao de intents</div>
            <div id="intentDistribution" class="quality-panel-list">
                <span class="panel-loading">Carregando...</span>
            </div>
        </div>
        <div class="metric-card quality-panel">
            <div class="metric-label quality-panel-title">Erros por handler</div>
            <div id="errorsByHandler" class="quality-panel-list">
                <span class="panel-loading">Carregando...</span>
            </div>
        </div>
        <div class="metric-card quality-panel">
            <div class="metric-label quality-panel-title">Origem da decisao</div>
            <div id="sourceDistribution" class="quality-panel-list">
                <span class="panel-loading">Carregando...</span>
            </div>
        </div>
        <div class="metric-card quality-panel">
            <div class="metric-label quality-panel-title">Latencia por tipo</div>
            <div id="avgTimeByType" class="quality-panel-list">
                <span class="panel-loading">Carregando...</span>
            </div>
        </div>
    </div>
</div>
