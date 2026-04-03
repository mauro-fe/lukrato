<!-- ==================== TAB: METAS ==================== -->
<div class="fin-tab-content" id="tab-metas" role="tabpanel" aria-labelledby="fin-tab-metas">

    <!-- Ações -->
    <div class="fin-actions-bar" id="finMetasActionsSection" data-aos="fade-up">
        <div class="actions-left">
            <button class="fin-action-btn" id="btnTemplates">
                <i data-lucide="wand-sparkles"></i>
                <span>Usar Template</span>
            </button>
        </div>
        <button class="fin-action-btn success" id="btnNovaMeta">
            <i data-lucide="plus"></i>
            <span>Nova Meta</span>
        </button>
    </div>

    <!-- Grid de metas -->
    <div class="metas-grid" id="metasGrid">
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <p>Carregando metas...</p>
        </div>
    </div>

    <!-- Estado vazio -->
    <div class="fin-empty-state" id="metasEmpty" style="display: none;">
        <div class="empty-icon">
            <i data-lucide="target" style="color: var(--color-primary)"></i>
        </div>
        <h3>Nenhuma meta financeira</h3>
        <p>Crie metas para acompanhar seus objetivos financeiros.<br>
            Use um <strong>template pronto</strong> para começar rapidamente!</p>
        <button class="fin-action-btn primary" id="btnTemplatesEmpty">
            <i data-lucide="wand-sparkles"></i>
            <span>Escolher Template</span>
        </button>
    </div>
</div>