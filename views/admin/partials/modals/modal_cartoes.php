<!-- Modal de Cartão de Crédito -->
<div class="modal-overlay" id="modalCartaoOverlay">
    <div class="modal-container" id="modalCartao" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalCartaoTitulo">Novo Cartão de Crédito</h2>
                    <p class="modal-subtitle">Preencha os dados do seu cartão</p>
                </div>
            </div>
            <button class="modal-close" type="button" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formCartao" autocomplete="off">
                <input type="hidden" id="cartaoId" name="cartao_id">

                <!-- Nome do Cartão -->
                <div class="form-group">
                    <label for="nomeCartao" class="form-label required">
                        <i class="fas fa-tag"></i>
                        Nome do Cartão
                    </label>
                    <input type="text" id="nomeCartao" name="nome_cartao" class="form-input"
                        placeholder="Ex: Nubank Platinum, Itaú Gold" required maxlength="100">
                </div>

                <!-- Conta Vinculada -->
                <div class="form-group">
                    <label for="contaVinculada" class="form-label required">
                        <i class="fas fa-link"></i>
                        Conta Vinculada
                    </label>
                    <select id="contaVinculada" name="conta_id" class="form-select" required>
                        <option value="">Selecione a conta</option>
                    </select>
                    <small class="form-help">Conta onde os pagamentos serão debitados</small>
                </div>

                <!-- Grid 2 colunas -->
                <div class="form-row">
                    <!-- Bandeira -->
                    <div class="form-group">
                        <label for="bandeira" class="form-label required">
                            <i class="fas fa-star"></i>
                            Bandeira
                        </label>
                        <select id="bandeira" name="bandeira" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="elo">Elo</option>
                            <option value="amex">American Express</option>
                            <option value="hipercard">Hipercard</option>
                            <option value="diners">Diners Club</option>
                        </select>
                    </div>

                    <!-- Últimos dígitos -->
                    <div class="form-group">
                        <label for="ultimosDigitos" class="form-label required">
                            <i class="fas fa-hashtag"></i>
                            Últimos 4 dígitos
                        </label>
                        <input type="text" id="ultimosDigitos" name="ultimos_digitos" class="form-input"
                            placeholder="1234" required maxlength="4" pattern="\d{4}">
                    </div>
                </div>

                <!-- Limite Total -->
                <div class="form-group">
                    <label for="limiteTotal" class="form-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Limite Total
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">R$</span>
                        <input type="text" id="limiteTotal" name="limite_total" class="form-input" placeholder="0,00"
                            required>
                    </div>
                    <small class="form-help">Limite total disponível no cartão</small>
                </div>

                <!-- Grid 2 colunas - Datas -->
                <div class="form-row">
                    <!-- Dia Fechamento -->
                    <div class="form-group">
                        <label for="diaFechamento" class="form-label">
                            <i class="fas fa-calendar-check"></i>
                            Dia Fechamento
                        </label>
                        <input type="number" id="diaFechamento" name="dia_fechamento" class="form-input" min="1"
                            max="31" placeholder="Ex: 10" required>
                        <small class="form-help">Dia que a fatura fecha</small>
                    </div>

                    <!-- Dia Vencimento -->
                    <div class="form-group">
                        <label for="diaVencimento" class="form-label">
                            <i class="fas fa-calendar-alt"></i>
                            Dia Vencimento
                        </label>
                        <input type="number" id="diaVencimento" name="dia_vencimento" class="form-input" min="1"
                            max="31" placeholder="Ex: 15" required>
                        <small class="form-help">Dia do vencimento</small>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close-btn">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Cartão
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
    .modal-header {
        padding: var(--spacing-6);
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, var(--color-primary) 0%, #c0392b 100%);
        color: white;
    }

    .modal-header-content {
        display: flex;
        align-items: center;
        gap: var(--spacing-4);
    }

    .modal-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .modal-title {
        font-size: var(--font-size-xl);
        font-weight: 700;
        margin: 0;
        color: white;
    }

    .modal-subtitle {
        font-size: var(--font-size-sm);
        opacity: 0.9;
        margin: var(--spacing-1) 0 0;
        color: white;
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition-normal);
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
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
        background: var(--color-bg);
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

    /* Footer */
    .modal-footer {
        padding: var(--spacing-6);
        border-top: 1px solid var(--glass-border);
        display: flex;
        gap: var(--spacing-3);
        justify-content: flex-end;
        background: var(--color-bg);
    }

    .btn {
        padding: var(--spacing-3) var(--spacing-5);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        transition: all var(--transition-normal);
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

    /* Responsive */
    @media (max-width: 768px) {
        .modal-container {
            max-width: 100%;
            max-height: 100vh;
            border-radius: 0;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>