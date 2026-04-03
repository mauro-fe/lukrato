<!-- ==================== MODAL: NOVA/EDITAR META ==================== -->
<div class="fin-modal-overlay" id="modalMeta">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <h3 id="modalMetaTitle">Nova Meta</h3>
            <button class="fin-modal-close" data-close-modal="modalMeta">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formMeta">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="pencil"></i> Título</label>
                    <input type="text" id="metaTitulo" class="fin-input" placeholder="Ex: Reserva de emergência"
                        required maxlength="150">
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="landmark"></i> Vincular a uma conta <span
                            class="fin-badge-optional">opcional</span></label>
                    <select id="metaContaId" class="fin-select">
                        <option value="">— Sem vínculo (aporte manual) —</option>
                    </select>
                    <span class="fin-hint" id="metaContaHint" style="display:none"><i data-lucide="info"></i> O
                        progresso será atualizado automaticamente com o saldo da conta.</span>
                </div>
                <div class="fin-form-row-2">
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="target"></i> Valor da Meta</label>
                        <input type="text" id="metaValorAlvo" class="fin-input" placeholder="R$ 0,00" required>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="coins"></i> Valor Atual</label>
                        <input type="text" id="metaValorAtual" class="fin-input" placeholder="R$ 0,00" value="0">
                    </div>
                </div>
                <div class="fin-form-row-2">
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="tag"></i> Tipo</label>
                        <select id="metaTipo" class="fin-select">
                            <option value="economia">Economia</option>
                            <option value="compra">Compra</option>
                            <option value="quitacao">Quitar Dívida</option>
                            <option value="emergencia">Emergência</option>
                            <option value="viagem">Viagem</option>
                            <option value="educacao">Educação</option>
                            <option value="moradia">Moradia</option>
                            <option value="veiculo">Veículo</option>
                            <option value="saude">Saúde</option>
                            <option value="negocio">Negócio</option>
                            <option value="aposentadoria">Aposentadoria</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="flag"></i> Prioridade</label>
                        <select id="metaPrioridade" class="fin-select">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="calendar"></i> Prazo (opcional)</label>
                    <input type="date" id="metaPrazo" class="fin-input">
                    <span class="fin-hint" id="metaAporteSugerido"></span>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="palette"></i> Cor</label>
                    <div class="color-picker-grid" id="metaCorPicker">
                        <button type="button" class="color-dot active" data-color="#6366f1"
                            style="background:#6366f1"></button>
                        <button type="button" class="color-dot" data-color="#3b82f6"
                            style="background:#3b82f6"></button>
                        <button type="button" class="color-dot" data-color="#10b981"
                            style="background:#10b981"></button>
                        <button type="button" class="color-dot" data-color="#f59e0b"
                            style="background:#f59e0b"></button>
                        <button type="button" class="color-dot" data-color="#ef4444"
                            style="background:#ef4444"></button>
                        <button type="button" class="color-dot" data-color="#8b5cf6"
                            style="background:#8b5cf6"></button>
                        <button type="button" class="color-dot" data-color="#ec4899"
                            style="background:#ec4899"></button>
                        <button type="button" class="color-dot" data-color="#14b8a6"
                            style="background:#14b8a6"></button>
                    </div>
                </div>
                <input type="hidden" id="metaCor" value="#6366f1">
                <input type="hidden" id="metaId" value="">
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalMeta">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="check"></i> Salvar Meta
                </button>
            </div>
        </form>
    </div>
</div>
