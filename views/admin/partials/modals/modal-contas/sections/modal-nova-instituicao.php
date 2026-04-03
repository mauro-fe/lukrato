<!-- Modal de Nova Instituição -->
<div class="lk-modal-overlay" id="modalNovaInstituicaoOverlay" style="z-index: 10001;">
    <div class="modal-container" id="modalNovaInstituicao" onclick="event.stopPropagation()" style="max-width: 480px;">
        <!-- Header -->
        <div class="modal-header" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="circle-plus" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title">Nova Instituição</h2>
                    <p class="modal-subtitle">Adicione um banco que não está na lista</p>
                </div>
            </div>
            <button class="modal-close modal-close-btn" type="button"
                onclick="contasManager.closeNovaInstituicaoModal()" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formNovaInstituicao" autocomplete="off">
                <!-- Nome da Instituição -->
                <div class="form-group">
                    <label for="nomeInstituicao" class="form-label required">
                        <i data-lucide="building-2"></i>
                        Nome da Instituição
                    </label>
                    <input type="text" id="nomeInstituicao" name="nome" class="form-input"
                        placeholder="Ex: Banco XYZ, Cooperativa ABC" required maxlength="100">
                </div>

                <!-- Tipo -->
                <div class="form-group">
                    <label for="tipoInstituicao" class="form-label required">
                        <i data-lucide="tag"></i>
                        Tipo
                    </label>
                    <select id="tipoInstituicao" name="tipo" class="form-select" required>
                        <option value="banco">Banco</option>
                        <option value="fintech">Fintech</option>
                        <option value="carteira_digital">Carteira Digital</option>
                        <option value="corretora">Corretora</option>
                        <option value="cooperativa">Cooperativa de Crédito</option>
                        <option value="outro" selected>Outro</option>
                    </select>
                </div>

                <!-- Cor -->
                <div class="form-group">
                    <label for="corInstituicao" class="form-label">
                        <i data-lucide="palette"></i>
                        Cor de Identificação
                    </label>
                    <div class="color-picker-row">
                        <input type="color" id="corInstituicao" name="cor_primaria" class="form-color" value="#3498db">
                        <span class="color-preview" id="colorPreview" style="background: #3498db;"></span>
                        <span class="color-value" id="colorValue">#3498db</span>
                    </div>
                    <small class="form-help">Cor para identificar esta instituição</small>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="contasManager.closeNovaInstituicaoModal()">
                        <i data-lucide="x"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        Adicionar Instituição
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
