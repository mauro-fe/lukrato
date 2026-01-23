<!-- Modal de Nova Conta -->
<div class="modal-overlay" id="modalContaOverlay">
    <div class="modal-container" id="modalConta" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalContaTitulo">Nova Conta</h2>
                    <p class="modal-subtitle">Adicione uma nova conta bancária</p>
                </div>
            </div>
            <button class="modal-close modal-close-btn" type="button" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formConta" autocomplete="off">
                <input type="hidden" id="contaId" name="conta_id">

                <!-- Nome da Conta -->
                <div class="form-group">
                    <label for="nomeConta" class="form-label required">
                        <i class="fas fa-tag"></i>
                        Nome da Conta
                    </label>
                    <input type="text" id="nomeConta" name="nome" class="form-input"
                        placeholder="Ex: Nubank Conta, Itaú Poupança" required maxlength="100">
                </div>

                <!-- Instituição Financeira -->
                <div class="form-group">
                    <label for="instituicaoFinanceiraSelect" class="form-label">
                        <i class="fas fa-building"></i>
                        Instituição Financeira
                    </label>
                    <div class="input-with-action">
                        <select id="instituicaoFinanceiraSelect" name="instituicao_financeira_id" class="form-select">
                            <option value="">Selecione uma instituição</option>
                        </select>
                        <button type="button" class="btn-add-instituicao" id="btnAddInstituicao" title="Adicionar nova instituição">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="form-help">Escolha o banco ou fintech desta conta</small>
                </div>

                <!-- Grid 2 colunas -->
                <div class="form-row">
                    <!-- Tipo de Conta -->
                    <div class="form-group">
                        <label for="tipoContaSelect" class="form-label required">
                            <i class="fas fa-wallet"></i>
                            Tipo de Conta
                        </label>
                        <select id="tipoContaSelect" name="tipo_conta" class="form-select" required>
                            <option value="conta_corrente">Conta Corrente</option>
                            <option value="conta_poupanca">Conta Poupança</option>
                            <option value="conta_investimento">Investimento</option>
                            <option value="carteira_digital">Carteira Digital</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>

                    <!-- Moeda -->
                    <div class="form-group">
                        <label for="moedaSelect" class="form-label required">
                            <i class="fas fa-dollar-sign"></i>
                            Moeda
                        </label>
                        <select id="moedaSelect" name="moeda" class="form-select" required>
                            <option value="BRL" selected>Real (BRL)</option>
                            <option value="USD">Dólar (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>
                </div>

                <!-- Saldo Inicial -->
                <div class="form-group">
                    <label for="saldoInicial" class="form-label">
                        <i class="fas fa-coins"></i>
                        Saldo Inicial
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">R$</span>
                        <input type="text" id="saldoInicial" name="saldo_inicial" class="form-input" value="0,00"
                            placeholder="0,00">
                    </div>
                    <small class="form-help">Saldo atual disponível na conta</small>
                </div>

                <!-- Cor da Conta -->
                <div class="form-group" style="display: none;">
                    <label for="corConta" class="form-label">
                        <i class="fas fa-palette"></i>
                        Cor de Identificação
                    </label>
                    <input type="color" id="corConta" name="cor" class="form-input" value="#e67e22">
                    <small class="form-help">Escolha uma cor para identificar esta conta</small>
                </div>

                <!-- Checkbox Incluir no Saldo Total -->
                <!-- <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" id="incluirSaldoTotal" name="incluir_saldo_total" checked>
                        <span class="form-checkbox-label">
                            Incluir no saldo total
                        </span>
                    </label>
                    <small class="form-help">Marque para considerar esta conta no saldo geral</small>
                </div> -->

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Modal usando variáveis do sistema */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: var(--spacing-4);
        animation: fadeIn 0.3s ease;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-container {
        background: var(--color-surface);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Header */
    #modalConta .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--color-primary) !important;
        color: white;
        position: relative;
    }

    .modal-header-content {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }

    .modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0;
        color: white;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .modal-subtitle {
        font-size: 0.75rem;
        opacity: 0.9;
        margin: 2px 0 0;
        color: white;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 14px;
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: rotate(90deg);
    }

    /* Body */
    .modal-body {
        padding: var(--spacing-6);
        overflow-y: auto;
        flex: 1;
    }

    /* Form */
    .form-group {
        margin-bottom: var(--spacing-5);
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        font-size: var(--font-size-sm);
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: var(--spacing-2);
    }

    .form-label.required::after {
        content: '*';
        color: var(--color-danger);
        margin-left: var(--spacing-1);
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: var(--spacing-3) var(--spacing-4);
        border: 2px solid var(--glass-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-base);
        color: var(--color-text);
        background: var(--color-bg) !important;
        transition: all var(--transition-normal);
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
    }

    .form-help {
        display: block;
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-top: var(--spacing-2);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-4);
    }

    .input-with-prefix {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-prefix {
        position: absolute;
        left: var(--spacing-4);
        font-weight: 600;
        color: var(--color-text-muted);
        pointer-events: none;
    }

    .input-with-prefix .form-input {
        padding-left: calc(var(--spacing-4) * 2 + 1rem);
    }

    /* Checkbox */
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        padding: var(--spacing-4);
        border-radius: var(--radius-md);
        background: var(--color-surface-muted);
        border: 2px solid var(--glass-border);
        transition: all 0.2s ease;
        position: relative;
    }

    .form-checkbox:hover {
        border-color: var(--color-primary);
        background: color-mix(in srgb, var(--color-primary) 8%, var(--color-surface-muted));
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.15);
    }

    .form-checkbox input[type="checkbox"] {
        width: 22px;
        height: 22px;
        cursor: pointer;
        accent-color: var(--color-primary);
        flex-shrink: 0;
        border-radius: 4px;
    }

    .form-checkbox-label {
        font-size: var(--font-size-base);
        font-weight: 600;
        color: var(--color-text);
        user-select: none;
        padding: 10px;
    }

    .form-checkbox:has(input:checked) {
        border-color: var(--color-primary);
        background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
    }

    .form-checkbox:has(input:checked) .form-checkbox-label {
        color: var(--color-primary);
    }

    /* Footer */
    .modal-footer {
        padding: var(--spacing-6);
        border-top: 1px solid var(--glass-border);
        display: flex;
        gap: var(--spacing-3);
        justify-content: center;
        background: var(--color-bg);
    }

    .btn {
        padding: var(--spacing-3) var(--spacing-6);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        transition: all var(--transition-normal);
        min-width: 160px;
        justify-content: center;
    }

    .btn-secondary {
        background: var(--glass-bg);
        color: var(--color-text);
        border: 1px solid var(--glass-border);
    }

    .btn-secondary:hover {
        background: var(--color-surface-muted);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--color-primary) 0%, #c0392b 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }

    /* Input with action button */
    .input-with-action {
        display: flex;
        gap: 0.5rem;
        align-items: stretch;
    }

    .input-with-action .form-select {
        flex: 1;
    }

    .btn-add-instituicao {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        min-width: 42px;
        background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-add-instituicao:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-overlay {
            align-items: flex-start;
            padding: 0;
            overflow-y: auto;
        }

        .modal-container {
            max-width: 100%;
            min-height: 100vh;
            max-height: none;
            border-radius: 0;
            margin: 0;
        }

        .modal-body {
            padding-bottom: calc(var(--spacing-6) + env(safe-area-inset-bottom, 20px));
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }

    /* Para telas muito pequenas */
    @media (max-width: 480px) {
        .modal-header {
            padding: 0.875rem 1rem;
        }

        .modal-body {
            padding: var(--spacing-4);
        }

        .modal-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }

        .modal-title {
            font-size: 1.1rem;
        }

        .modal-subtitle {
            font-size: 0.8rem;
        }

        .form-label {
            font-size: 0.85rem;
        }

        .form-input,
        .form-select {
            padding: 0.75rem 1rem;
            font-size: 16px;
            /* Previne zoom no iOS */
        }
    }
</style>

<!-- Modal de Nova Instituição -->
<div class="modal-overlay" id="modalNovaInstituicaoOverlay" style="z-index: 10001;">
    <div class="modal-container" id="modalNovaInstituicao" onclick="event.stopPropagation()" style="max-width: 480px;">
        <!-- Header -->
        <div class="modal-header" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <h2 class="modal-title">Nova Instituição</h2>
                    <p class="modal-subtitle">Adicione um banco que não está na lista</p>
                </div>
            </div>
            <button class="modal-close modal-close-btn" type="button" onclick="contasManager.closeNovaInstituicaoModal()" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formNovaInstituicao" autocomplete="off">
                <!-- Nome da Instituição -->
                <div class="form-group">
                    <label for="nomeInstituicao" class="form-label required">
                        <i class="fas fa-building"></i>
                        Nome da Instituição
                    </label>
                    <input type="text" id="nomeInstituicao" name="nome" class="form-input"
                        placeholder="Ex: Banco XYZ, Cooperativa ABC" required maxlength="100">
                </div>

                <!-- Tipo -->
                <div class="form-group">
                    <label for="tipoInstituicao" class="form-label required">
                        <i class="fas fa-tag"></i>
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
                        <i class="fas fa-palette"></i>
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
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Adicionar Instituição
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Modal Nova Instituição - Estilos específicos */
    #modalNovaInstituicaoOverlay {
        display: none;
    }

    #modalNovaInstituicaoOverlay.active {
        display: flex;
    }

    .color-picker-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .form-color {
        width: 60px;
        height: 42px;
        padding: 0;
        border: 2px solid var(--glass-border);
        border-radius: 8px;
        cursor: pointer;
        background: transparent;
    }

    .form-color::-webkit-color-swatch-wrapper {
        padding: 2px;
    }

    .form-color::-webkit-color-swatch {
        border-radius: 6px;
        border: none;
    }

    .color-preview {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid var(--glass-border);
    }

    .color-value {
        font-family: monospace;
        font-size: 0.9rem;
        color: var(--color-text-muted);
    }
</style>