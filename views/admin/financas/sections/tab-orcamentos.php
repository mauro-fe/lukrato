<!-- ==================== TAB: ORÇAMENTOS ==================== -->
<div class="fin-tab-content active" id="tab-orcamentos" role="tabpanel" aria-labelledby="fin-tab-orcamentos">

    <!-- Ações rápidas -->
    <div class="fin-actions-bar" id="finOrcActionsSection" data-aos="fade-up" data-aos-delay="150">
        <div class="actions-left">
            <button class="fin-action-btn primary" id="btnAutoSugerir"
                title="A IA analisa seus últimos 3 meses e sugere orçamentos automaticamente">
                <i data-lucide="wand-2"></i>
                <span>Sugestão Inteligente</span>
            </button>
            <button class="fin-action-btn" id="btnCopiarMes" title="Copiar orçamentos do mês anterior">
                <i data-lucide="copy"></i>
                <span>Copiar Mês Anterior</span>
            </button>
        </div>
        <button class="fin-action-btn success" id="btnNovoOrcamento">
            <i data-lucide="plus"></i>
            <span>Novo Orçamento</span>
        </button>
    </div>

    <!-- Grid de orçamentos -->
    <div class="orcamentos-grid" id="orcamentosGrid">
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <p>Carregando orçamentos...</p>
        </div>
    </div>

    <!-- Estado vazio -->
    <div class="fin-empty-state" id="orcamentosEmpty" style="display: none;">
        <div class="empty-icon">
            <i data-lucide="pie-chart"></i>
        </div>
        <h3>Nenhum orçamento configurado</h3>
        <p>Configure orçamentos por categoria para controlar seus gastos.<br>
            Clique em <strong>"Sugestão Inteligente"</strong> para configurar automaticamente!</p>
        <button class="fin-action-btn primary" id="btnAutoSugerirEmpty">
            <i data-lucide="wand-2"></i>
            <span>Configurar Automaticamente</span>
        </button>
    </div>

    <!-- Insights (dentro da tab orçamentos) -->
    <div class="fin-insights-section" id="insightsSection" style="display:none;">
        <div class="fin-section-label">
            <i data-lucide="lightbulb"></i>
            <span>Insights</span>
        </div>
        <div class="insights-grid" id="insightsGrid"></div>
    </div>
</div>