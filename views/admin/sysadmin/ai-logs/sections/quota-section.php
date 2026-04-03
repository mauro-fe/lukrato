<div class="quota-section" id="quotaSection">
    <h2 class="section-title">
        <i data-lucide="gauge"></i>
        Quota OpenAI
        <span class="quota-status-badge loading" id="quotaStatus">Verificando...</span>
    </h2>
    <div class="quota-grid" id="quotaGrid">
        <div class="quota-item">
            <div class="quota-label">Requisicoes</div>
            <div class="quota-bar-wrap">
                <div class="quota-bar" id="quotaReqBar" style="width:0%;background:var(--blue-600);"></div>
            </div>
            <div class="quota-values"><strong id="quotaReqRemaining">-</strong> / <span id="quotaReqLimit">-</span></div>
            <div class="quota-hint" id="quotaReqHint">Disponivel agora</div>
        </div>
        <div class="quota-item">
            <div class="quota-label">Tokens</div>
            <div class="quota-bar-wrap">
                <div class="quota-bar" id="quotaTokBar" style="width:0%;background:var(--color-success);"></div>
            </div>
            <div class="quota-values"><strong id="quotaTokRemaining">-</strong> / <span id="quotaTokLimit">-</span></div>
            <div class="quota-hint" id="quotaTokHint">Disponivel agora</div>
        </div>
    </div>
    <div class="quota-reset" id="quotaReset" style="display:none;"></div>
    <div class="quota-msg" id="quotaMsg" style="display:none;"></div>
</div>
