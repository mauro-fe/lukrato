<!-- ==================== MODAL: SUGESTÕES INTELIGENTES ==================== -->
<div class="fin-modal-overlay" id="modalSugestoes">
    <div class="fin-modal large">
        <div class="fin-modal-header">
            <h3><i data-lucide="wand-2" style="color: var(--color-primary)"></i> Sugestão Inteligente</h3>
            <button class="fin-modal-close" data-close-modal="modalSugestoes">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="fin-modal-body">
            <p class="fin-modal-desc">
                Analisamos seus gastos dos últimos 3 meses e sugerimos limites <strong>abaixo da sua média</strong> para
                ajudar você a economizar em cada categoria.
                Você pode ajustar os valores antes de aplicar.
            </p>
            <div class="sugestoes-list" id="sugestoesList">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Analisando seu histórico...</p>
                </div>
            </div>
        </div>
        <div class="fin-modal-footer">
            <button type="button" class="fin-btn secondary" data-close-modal="modalSugestoes">Cancelar</button>
            <button type="button" class="fin-btn primary" id="btnAplicarSugestoes">
                <i data-lucide="check-check"></i> Aplicar Todas
            </button>
        </div>
    </div>
</div>
