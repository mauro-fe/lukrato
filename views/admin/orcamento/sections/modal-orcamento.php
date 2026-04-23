<!-- ==================== MODAL: NOVO/EDITAR ORCAMENTO ==================== -->
<div class="fin-modal-overlay" id="modalOrcamento">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-header__content">
                <div class="fin-modal-icon" aria-hidden="true">
                    <i data-lucide="wallet-cards"></i>
                </div>
                <div class="fin-modal-title-group">
                    <h3 id="modalOrcamentoTitle">Novo Orcamento</h3>
                    <p class="fin-modal-subtitle" id="modalOrcamentoSubtitle">Defina o limite mensal e os alertas da categoria.</p>
                </div>
            </div>
            <button class="fin-modal-close" data-close-modal="modalOrcamento">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formOrcamento">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <div class="fin-form-group">
                    <label class="fin-label">
                        <i data-lucide="tag"></i> Categoria
                    </label>
                    <select id="orcCategoria" class="fin-select" required>
                        <option value="">Selecione uma categoria</option>
                    </select>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label">
                        <i data-lucide="dollar-sign"></i> Limite Mensal
                    </label>
                    <input type="text" id="orcValor" class="fin-input" placeholder="R$ 0,00" required>
                    <span class="fin-hint" id="orcSugestao"></span>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcRollover">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Acumular sobra do mes anterior</span>
                    </label>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcAlerta80" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Alertar ao atingir 80%</span>
                    </label>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcAlerta100" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Alertar ao estourar</span>
                    </label>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalOrcamento">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="check"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>