<!-- ==================== MODAL: APORTE ==================== -->
<div class="fin-modal-overlay" id="modalAporte">
    <div class="fin-modal small">
        <div class="fin-modal-header">
            <h3><i data-lucide="plus-circle"></i> Guardar mais</h3>
            <button class="fin-modal-close" data-close-modal="modalAporte">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formAporte">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <p class="aporte-meta-info" id="aporteMetaInfo"></p>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="dollar-sign"></i> Valor do Aporte</label>
                    <input type="text" id="aporteValor" class="fin-input" placeholder="R$ 0,00" required>
                </div>
                <input type="hidden" id="aporteMetaId" value="">
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalAporte">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="plus"></i> Adicionar
                </button>
            </div>
        </form>
    </div>
</div>
