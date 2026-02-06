<style>
    /* =============================================================================
       MODAL MODERNO - AGENDAMENTOS
       Version: <?= time() ?> 
       ============================================================================= */

    /* SweetAlert na frente do modal */
    .swal2-container {
        z-index: 99999 !important;
    }

    /* Modal Backdrop com Blur Premium */
    .modal-backdrop.show {
        backdrop-filter: blur(12px) saturate(180%);
        background: rgba(0, 0, 0, 0.5);
    }

    /* Modal Container */
    #modalAgendamento.modern-modal .modal-content {
        border: none;
        border-radius: var(--radius-xl);
        background: var(--color-surface);
        box-shadow: var(--shadow-xl), 0 0 0 1px rgba(230, 126, 34, 0.1);
        overflow: hidden;
    }

    /* Modal Header */
    #modalAgendamento .modern-header {
        background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
        color: white;
        padding: var(--spacing-6);
        border-bottom: none;
        position: relative;
    }

    /* Barra decorativa superior */
    #modalAgendamento .modern-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.5) 30%,
                rgba(255, 255, 255, 0.5) 70%,
                transparent);
        opacity: 0.6;
    }

    #modalAgendamento .modal-title-wrapper {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }

    #modalAgendamento .modal-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        backdrop-filter: blur(10px);
    }

    #modalAgendamento .modern-header .modal-title {
        color: white;
        font-size: var(--font-size-xl);
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.02em;
    }

    #modalAgendamento .modal-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: var(--font-size-sm);
        margin: 0;
        margin-top: 4px;
    }

    #modalAgendamento .modern-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
        transition: var(--transition-normal);
        border-radius: 50%;
        width: 36px;
        height: 36px;
    }

    #modalAgendamento .modern-header .btn-close:hover {
        opacity: 1;
        transform: rotate(90deg) scale(1.1);
        background: rgba(255, 255, 255, 0.2);
    }

    /* Modal Body */
    #modalAgendamento .modern-body {
        padding: var(--spacing-6);
        background: var(--color-surface);
    }

    /* Alert Moderno */
    #modalAgendamento #agAlert {
        border-radius: var(--radius-md);
        border: 1px solid var(--color-danger);
        background: rgba(231, 76, 60, 0.1);
        backdrop-filter: blur(10px);
        padding: var(--spacing-3) var(--spacing-4);
        font-size: var(--font-size-sm);
        animation: slideDown 0.3s ease;
        margin-bottom: var(--spacing-4);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form Groups */
    #modalAgendamento .form-group {
        margin-bottom: var(--spacing-4);
    }

    #modalAgendamento .form-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        font-weight: 600;
        color: var(--color-text);
        font-size: var(--font-size-sm);
        margin-bottom: var(--spacing-2);
        letter-spacing: 0.01em;
    }

    #modalAgendamento .form-label i {
        color: var(--color-primary);
        font-size: 1rem;
    }

    #modalAgendamento .optional-badge {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        font-weight: normal;
        margin-left: auto;
    }

    /* Form Controls */
    #modalAgendamento .modern-body input.form-control,
    #modalAgendamento .modern-body select.form-control,
    #modalAgendamento .modern-body textarea.form-control {
        width: 100%;
        padding: var(--spacing-3) var(--spacing-4);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        background: var(--color-surface-muted);
        color: var(--color-text);
        font-size: var(--font-size-sm);
        transition: var(--transition-normal);
        font-family: var(--font-primary);
    }

    #modalAgendamento .modern-body input.form-control::placeholder,
    #modalAgendamento .modern-body textarea.form-control::placeholder {
        color: var(--color-text-muted);
        opacity: 0.6;
    }

    #modalAgendamento .modern-body input.form-control:focus,
    #modalAgendamento .modern-body select.form-control:focus,
    #modalAgendamento .modern-body textarea.form-control:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
        background: var(--color-surface);
        transform: translateY(-1px);
    }

    #modalAgendamento .modern-body input.form-control:hover:not(:focus),
    #modalAgendamento .modern-body select.form-control:hover:not(:focus) {
        border-color: rgba(230, 126, 34, 0.4);
    }

    #modalAgendamento .modern-body textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    /* Select customizado */
    #modalAgendamento .modern-body select.form-control {
        cursor: pointer;
        background-position: right var(--spacing-3) center;
        background-size: 16px;
        padding-right: var(--spacing-6);
    }

    /* Form Rows */
    #modalAgendamento .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-4);
        margin-bottom: var(--spacing-4);
    }

    @media (max-width: 768px) {
        #modalAgendamento .form-row {
            grid-template-columns: 1fr;
        }
    }

    /* Toggle Button Modern */
    #modalAgendamento .toggle-btn-modern {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 12px 16px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    #modalAgendamento .toggle-btn-modern:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: #e67e22 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    #modalAgendamento button.toggle-btn-modern.active,
    #modalAgendamento .toggle-btn-modern.active,
    #modalAgendamento .toggle-btn-modern[data-active="true"] {
        background: linear-gradient(135deg, #e67e22, #d35400) !important;
        border-color: #e67e22 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.4) !important;
    }

    #modalAgendamento .toggle-btn-modern.active span,
    #modalAgendamento .toggle-btn-modern[data-active="true"] span {
        color: #ffffff !important;
    }

    #modalAgendamento .toggle-btn-modern span {
        color: inherit;
        transition: all 0.3s ease;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    /* Checkboxes Modernos */
    #modalAgendamento .form-check {
        padding-left: 0;
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        margin-bottom: var(--spacing-2);
    }

    #modalAgendamento .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid var(--glass-border);
        background: var(--color-surface-muted);
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition-fast);
        margin: 0;
    }

    #modalAgendamento .form-check-input:checked {
        background: var(--color-primary);
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--ring);
    }

    #modalAgendamento .form-check-input:hover {
        border-color: var(--color-primary);
    }

    #modalAgendamento .form-check-label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        cursor: pointer;
        user-select: none;
    }

    /* Campo de Repetições - Input com Texto Integrado */
    #modalAgendamento .form-text.text-muted {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-top: var(--spacing-2);
        font-style: italic;
    }

    #modalAgendamento #agRepeticoes {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--color-card-border);
        border-radius: 12px;
        font-size: 0.9375rem;
        font-weight: 500;
        color: var(--color-text);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--glass-bg);
    }

    #modalAgendamento #repeticoesGroup {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Input Group (para campo de repetições) */
    #modalAgendamento .lk-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    #modalAgendamento .lk-input-group .lk-input {
        padding-right: 70px;
    }

    #modalAgendamento .lk-input {
        width: 100%;
        padding: var(--spacing-3) var(--spacing-4);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        background: var(--color-surface-muted);
        color: var(--color-text);
        font-size: var(--font-size-sm);
        transition: var(--transition-normal);
        font-family: var(--font-primary);
        text-align: left;
    }

    /* Estilizar os botões de número (spinner) */
    #modalAgendamento input[type="number"]::-webkit-inner-spin-button,
    #modalAgendamento input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
        height: 30px;
        margin-right: 360px !important;
        cursor: pointer;
    }

    #modalAgendamento input[type="number"] {
        -moz-appearance: textfield;
    }

    #modalAgendamento input[type="number"]::-webkit-inner-spin-button:hover,
    #modalAgendamento input[type="number"]::-webkit-outer-spin-button:hover {
        background: var(--color-primary-muted);
    }

    #modalAgendamento .lk-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
        background: var(--color-surface);
    }

    #modalAgendamento .lk-input-suffix {
        position: absolute;
        right: var(--spacing-4);
        font-size: var(--font-size-sm);
        color: var(--color-text-muted);
        font-weight: 600;
        pointer-events: none;
    }

    #modalAgendamento .lk-helper-text {
        display: block;
        margin-top: var(--spacing-2);
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        font-style: italic;
    }

    :root[data-theme="dark"] #modalAgendamento .lk-input {
        background: var(--color-background);
        border-color: var(--glass-border);
    }

    /* Modern Buttons */
    #modalAgendamento .btn-modern {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3) var(--spacing-6);
        border: none;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-normal);
        text-decoration: none;
        font-family: var(--font-primary);
        letter-spacing: 0.02em;
        position: relative;
        overflow: hidden;
    }

    #modalAgendamento .btn-modern i {
        font-size: 1rem;
    }

    #modalAgendamento .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    #modalAgendamento .btn-modern:active {
        transform: translateY(0);
    }

    #modalAgendamento .btn-primary-modern {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: white;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
    }

    #modalAgendamento .btn-primary-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    #modalAgendamento .btn-primary-modern:hover::before {
        left: 100%;
    }

    #modalAgendamento .btn-primary-modern:hover {
        color: white;
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }

    #modalAgendamento .btn-secondary-modern {
        background: var(--glass-bg);
        color: var(--color-text);
        border: 2px solid var(--glass-border);
    }

    #modalAgendamento .btn-secondary-modern:hover {
        background: var(--color-surface-muted);
        border-color: var(--color-text-muted);
        color: var(--color-text);
    }

    /* Modal Footer */
    #modalAgendamento .modern-footer {
        padding: var(--spacing-4) var(--spacing-6) var(--spacing-6);
        background: transparent;
        border: 0;
        display: flex;
        gap: var(--spacing-3);
        justify-content: flex-end;
    }

    /* Animação de entrada */
    #modalAgendamento .modal-dialog {
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Dark Mode Support */
    :root[data-theme="dark"] #modalAgendamento.modern-modal .modal-content {
        background: var(--color-surface);
    }

    :root[data-theme="dark"] #modalAgendamento .modern-body input.form-control,
    :root[data-theme="dark"] #modalAgendamento .modern-body select.form-control,
    :root[data-theme="dark"] #modalAgendamento .modern-body textarea.form-control,
    :root[data-theme="dark"] #modalAgendamento .toggle-btn-modern,
    :root[data-theme="dark"] #modalAgendamento .notification-btn {
        background: var(--color-surface-muted);
        border-color: var(--glass-border);
        color: var(--color-text);
    }

    :root[data-theme="dark"] #modalAgendamento .modern-body {
        background: var(--color-surface);
    }

    :root[data-theme="dark"] #modalAgendamento .modern-footer {
        background: transparent;
    }

    /* Responsivo */
    @media (max-width: 576px) {
        #modalAgendamento .modal-content {
            border-radius: var(--radius-lg);
        }

        #modalAgendamento .modern-body,
        #modalAgendamento .modern-header,
        #modalAgendamento .modern-footer {
            padding-left: var(--spacing-4);
            padding-right: var(--spacing-4);
        }
    }
</style>

<!-- ==================== MODAL AGENDAMENTO (REDESENHADO) ==================== -->
<div class="modal fade modern-modal" id="modalAgendamento" tabindex="-1" aria-labelledby="modalAgendamentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modern-header">
                <div class="modal-title-wrapper">
                    <div class="modal-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="modalAgendamentoLabel">Novo Agendamento</h5>
                        <p class="modal-subtitle">Configure seu lançamento recorrente</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body modern-body">
                <form id="formAgendamento" novalidate>
                    <input type="hidden" id="agId" name="id">

                    <!-- Alerta de erros -->
                    <div id="agAlert" class="alert alert-danger d-none" role="alert"></div>

                    <!-- Linha 1: Tipo e Título -->
                    <div class="form-row">
                        <div class="form-group col-tipo">
                            <label for="agTipo" class="form-label">
                                <i class="fas fa-tag"></i> Tipo
                            </label>
                            <select id="agTipo" name="tipo" class="form-control modern-select" required>
                                <option value="despesa">💰 Despesa</option>
                                <option value="receita">💵 Receita</option>
                            </select>
                        </div>
                        <div class="form-group col-titulo">
                            <label for="agTitulo" class="form-label">
                                <i class="fas fa-align-left"></i> Descrição
                            </label>
                            <input type="text" id="agTitulo" name="titulo" class="form-control"
                                placeholder="Ex: Aluguel, Salário, Netflix..." required maxlength="160">
                        </div>
                    </div>

                    <!-- Linha 2: Categoria e Valor -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="agCategoria" class="form-label">
                                <i class="fas fa-folder"></i> Categoria
                            </label>
                            <select id="agCategoria" name="categoria_id" class="form-control modern-select" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="agValor" class="form-label">
                                <i class="fas fa-dollar-sign"></i> Valor
                            </label>
                            <input type="text" id="agValor" name="valor" class="form-control" placeholder="R$ 0,00"
                                required>
                        </div>
                    </div>

                    <!-- Linha 3: Conta e Data -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="agConta" class="form-label">
                                <i class="fas fa-wallet"></i> Conta
                            </label>
                            <select id="agConta" name="conta_id" class="form-control modern-select">
                                <option value="">Todas as contas (opcional)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="agDataPagamento" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Data de Execução
                            </label>
                            <input type="datetime-local" id="agDataPagamento" name="data_pagamento" class="form-control"
                                required>
                        </div>
                    </div>

                    <!-- Recorrência -->
                    <div class="form-group" id="recorrenciaGroup">
                        <label for="agFrequencia" class="form-label">
                            <i class="fas fa-sync-alt"></i> Recorrência
                        </label>
                        <select id="agFrequencia" name="recorrencia_freq" class="form-control modern-select">
                            <option value="">Não repetir (único)</option>
                            <option value="diario">Diariamente</option>
                            <option value="semanal">Semanalmente</option>
                            <option value="mensal">Mensalmente</option>
                            <option value="anual">Anualmente</option>
                        </select>
                        <small class="lk-helper-text">🔁 Repete para sempre até você cancelar - ideal para contas
                            fixas</small>
                    </div>

                    <!-- Forma de Pagamento -->
                    <div class="form-group" id="agFormaPagamentoGroup">
                        <label for="agFormaPagamento" class="form-label">
                            <i class="fas fa-credit-card"></i> Forma de Pagamento
                            <span class="optional-badge">opcional</span>
                        </label>
                        <select id="agFormaPagamento" name="forma_pagamento" class="form-control modern-select">
                            <option value="">Selecione (opcional)</option>
                            <option value="pix">📱 PIX</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="boleto">📄 Boleto</option>
                            <option value="transferencia">🏦 Transferência</option>
                            <option value="deposito">🏦 Depósito</option>
                        </select>
                    </div>

                    <!-- Parcelamento Moderno -->
                    <div class="form-group" id="parcelamentoGroup">
                        <label class="form-label">
                            <i class="fas fa-credit-card"></i> Parcelamento
                            <span class="optional-badge">opcional</span>
                        </label>

                        <div class="parcelamento-card">
                            <div class="parcelamento-toggle-row">
                                <div class="parcelamento-info">
                                    <span class="parcelamento-icon">💳</span>
                                    <div class="parcelamento-text">
                                        <span class="parcelamento-label">Compra Parcelada</span>
                                        <span class="parcelamento-desc">Divide em várias parcelas mensais</span>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="agEhParcelado" name="eh_parcelado" value="1">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="parcelamento-config" id="parcelasInputGroup" style="display: none;">
                                <div class="parcelas-input-wrapper">
                                    <div class="parcelas-label">
                                        <i class="fas fa-layer-group"></i>
                                        <span>Número de parcelas</span>
                                    </div>
                                    <div class="parcelas-input-group">
                                        <button type="button" class="parcelas-btn parcelas-minus"
                                            onclick="adjustParcelas(-1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" id="agNumeroParcelas"
                                            name="numero_parcelas" class="parcelas-input" value="2" placeholder="2"
                                            style="color: #fff !important; background: transparent !important; font-size: 1.2rem; font-weight: 700;">
                                        <button type="button" class="parcelas-btn parcelas-plus"
                                            onclick="adjustParcelas(1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <span class="parcelas-suffix">x</span>
                                    </div>
                                </div>
                                <div class="parcelas-presets">
                                    <button type="button" class="preset-btn" onclick="setParcelas(2)">2x</button>
                                    <button type="button" class="preset-btn" onclick="setParcelas(3)">3x</button>
                                    <button type="button" class="preset-btn" onclick="setParcelas(6)">6x</button>
                                    <button type="button" class="preset-btn" onclick="setParcelas(10)">10x</button>
                                    <button type="button" class="preset-btn" onclick="setParcelas(12)">12x</button>
                                </div>
                            </div>
                        </div>
                        <small class="lk-helper-text">O lembrete aparece todo mês até a última parcela ser paga</small>
                    </div>

                    <!-- Campo oculto para repetições (sempre indefinido) -->
                    <input type="hidden" id="agRepeticoes" name="recorrencia_repeticoes" value="">

                    <!-- Tempo de Aviso -->
                    <div class="form-group">
                        <label for="agTempoAviso" class="form-label">
                            <i class="fas fa-clock"></i> Avisar com antecedência
                        </label>
                        <select id="agTempoAviso" name="tempo_aviso" class="form-control modern-select">
                            <option value="0">No momento da execução</option>
                            <option value="5">5 minutos antes</option>
                            <option value="15">15 minutos antes</option>
                            <option value="30">30 minutos antes</option>
                            <option value="60" selected>1 hora antes</option>
                            <option value="120">2 horas antes</option>
                            <option value="360">6 horas antes</option>
                            <option value="720">12 horas antes</option>
                            <option value="1440">1 dia antes</option>
                            <option value="2880">2 dias antes</option>
                            <option value="4320">3 dias antes</option>
                            <option value="10080">1 semana antes</option>
                        </select>
                        <small class="lk-helper-text">Quando você será notificado sobre este agendamento</small>
                    </div>

                    <!-- Notificações -->
                    <div class="form-group">
                        <label class="form-label d-block">
                            <i class="fas fa-bell"></i> Canais de Notificação
                        </label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agCanalInapp" name="canal_inapp"
                                    value="1" checked>
                                <label class="form-check-label" for="agCanalInapp">
                                    Aviso no sistema
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agCanalEmail" name="canal_email"
                                    value="1" checked>
                                <label class="form-check-label" for="agCanalEmail">
                                    E-mail
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer modern-footer">
                <button type="button" class="btn-modern btn-secondary-modern" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    <span>Cancelar</span>
                </button>
                <button type="submit" form="formAgendamento" class="btn-modern btn-primary-modern">
                    <i class="fas fa-save"></i>
                    <span>Salvar Agendamento</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL EXECUTAR AGENDAMENTO ==================== -->
<style>
    #modalExecutarAgendamento .modal-content {
        border: none;
        border-radius: var(--radius-xl);
        background: var(--color-surface);
        box-shadow: var(--shadow-xl), 0 0 0 1px rgba(16, 185, 129, 0.1);
        overflow: hidden;
    }

    #modalExecutarAgendamento .modern-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: var(--spacing-5);
        border-bottom: none;
    }

    #modalExecutarAgendamento .modal-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    #modalExecutarAgendamento .modal-title {
        color: white;
        font-size: var(--font-size-lg);
        font-weight: 700;
        margin: 0;
    }

    #modalExecutarAgendamento .modal-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: var(--font-size-sm);
        margin: 0;
        margin-top: 4px;
    }

    #modalExecutarAgendamento .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    #modalExecutarAgendamento .agendamento-resumo {
        background: var(--color-surface-muted);
        border-radius: var(--radius-md);
        padding: var(--spacing-4);
        margin-bottom: var(--spacing-4);
        border: 1px solid var(--glass-border);
    }

    #modalExecutarAgendamento .resumo-titulo {
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: var(--spacing-2);
    }

    #modalExecutarAgendamento .resumo-valor {
        font-size: var(--font-size-xl);
        font-weight: 700;
        color: #10b981;
    }

    #modalExecutarAgendamento .resumo-parcela {
        font-size: var(--font-size-sm);
        color: var(--color-text-muted);
        margin-top: var(--spacing-1);
    }

    #modalExecutarAgendamento .form-group {
        margin-bottom: var(--spacing-4);
    }

    #modalExecutarAgendamento .form-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        font-weight: 600;
        color: var(--color-text);
        font-size: var(--font-size-sm);
        margin-bottom: var(--spacing-2);
    }

    #modalExecutarAgendamento .form-label i {
        color: #10b981;
    }

    #modalExecutarAgendamento .form-control {
        width: 100%;
        padding: var(--spacing-3) var(--spacing-4);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        background: var(--color-surface-muted);
        color: var(--color-text);
        font-size: var(--font-size-sm);
    }

    #modalExecutarAgendamento .form-control:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }

    #modalExecutarAgendamento .btn-executar {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: var(--spacing-3) var(--spacing-6);
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    #modalExecutarAgendamento .btn-executar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
</style>

<div class="modal fade" id="modalExecutarAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modern-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title">Registrar Pagamento</h5>
                        <p class="modal-subtitle">Informe onde foi pago para gerar o lançamento</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body" style="padding: var(--spacing-5);">
                <input type="hidden" id="execAgendamentoId">

                <!-- Resumo do Agendamento -->
                <div class="agendamento-resumo">
                    <div class="resumo-titulo" id="execResumoTitulo">-</div>
                    <div class="resumo-valor" id="execResumoValor">R$ 0,00</div>
                    <div class="resumo-parcela" id="execResumoParcela"></div>
                </div>

                <!-- Conta -->
                <div class="form-group">
                    <label for="execConta" class="form-label">
                        <i class="fas fa-wallet"></i> Conta
                    </label>
                    <select id="execConta" name="conta_id" class="form-control">
                        <option value="">Selecione a conta</option>
                    </select>
                    <small class="text-muted">Em qual conta esse valor será debitado/creditado?</small>
                </div>

                <!-- Forma de Pagamento -->
                <div class="form-group">
                    <label for="execFormaPagamento" class="form-label">
                        <i class="fas fa-credit-card"></i> Forma de Pagamento
                    </label>
                    <select id="execFormaPagamento" name="forma_pagamento" class="form-control">
                        <option value="">Selecione (opcional)</option>
                        <option value="dinheiro">💵 Dinheiro</option>
                        <option value="pix">📱 PIX</option>
                        <option value="cartao_debito">💳 Cartão de Débito</option>
                        <option value="cartao_credito">💳 Cartão de Crédito</option>
                        <option value="boleto">📄 Boleto</option>
                        <option value="transferencia">🏦 Transferência</option>
                        <option value="outro">📋 Outro</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer" style="border: 0; padding: var(--spacing-4) var(--spacing-5) var(--spacing-5);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn-executar" id="btnConfirmarExecucao">
                    <i class="fas fa-check me-2"></i>Confirmar Pagamento
                </button>
            </div>
        </div>
    </div>
</div>