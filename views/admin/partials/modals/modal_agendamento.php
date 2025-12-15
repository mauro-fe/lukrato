<style>
    /* Modal Backdrop com Blur Premium */
    .modal-backdrop.show {
        backdrop-filter: blur(12px) saturate(180%);
        background: rgba(0, 0, 0, 0.5);
    }


    /* Modal Content - Design Moderno */
    #modalAgendamento .modal-content {
        background: var(--color-surface) !important;
        color: var(--color-text);
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-xl), 0 0 0 1px rgba(230, 126, 34, 0.1);
        overflow: hidden;
        position: relative;
        font-family: var(--font-primary);
    }

    /* Barra decorativa superior com gradiente */
    #modalAgendamento .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg,
                transparent,
                var(--color-primary) 30%,
                var(--color-primary) 70%,
                transparent);
        opacity: 0.8;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {

        0%,
        100% {
            opacity: 0.6;
        }

        50% {
            opacity: 1;
        }
    }

    /* Header Premium */
    #modalAgendamento .modal-header {
        border: 0;
        background: transparent;
        padding: var(--spacing-6) var(--spacing-6) var(--spacing-4);
        position: relative;
    }

    #modalAgendamento .modal-title {
        font-size: var(--font-size-xl);
        font-weight: 700;
        color: var(--color-primary);
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }

    #modalAgendamento .modal-title::before {
        content: '📅';
        font-size: var(--font-size-2xl);
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    /* Botão Close Moderno */
#modalAgendamento .btn-close {
    background: var(--glass-bg);
    background-image: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    opacity: 0.8;
    transition: var(--transition-normal);
    position: relative;
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

#modalAgendamento .btn-close::before,
#modalAgendamento .btn-close::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 14px;
    height: 2px;
    background: var(--color-text);
    transform-origin: center;
    transition: inherit;
}

#modalAgendamento .btn-close::before {
    transform: translate(-50%, -50%) rotate(45deg);
}

#modalAgendamento .btn-close::after {
    transform: translate(-50%, -50%) rotate(-45deg);
}

#modalAgendamento .btn-close:hover {
    opacity: 1;
    background: var(--color-danger);
    transform: rotate(90deg) scale(1.1);
}

#modalAgendamento .btn-close:hover::before,
#modalAgendamento .btn-close:hover::after {
    background: #fff;
}

    /* Body */
    #modalAgendamento .modal-body {
        padding: 0 var(--spacing-6) var(--spacing-5);
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

    /* Labels Modernos */
    #modalAgendamento .form-label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        font-weight: 600;
        margin-bottom: var(--spacing-2);
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        letter-spacing: 0.01em;
    }

    /* Inputs e Selects Premium */
    #modalAgendamento .form-control,
    #modalAgendamento .form-select {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        padding: var(--spacing-3) var(--spacing-4);
        transition: var(--transition-normal);
        font-family: var(--font-primary);
    }

    #modalAgendamento .form-control::placeholder {
        color: var(--color-text-muted);
        opacity: 0.6;
    }

    #modalAgendamento .form-control:focus,
    #modalAgendamento .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
        background: var(--color-surface);
        transform: translateY(-1px);
    }

    #modalAgendamento .form-control:hover:not(:focus),
    #modalAgendamento .form-select:hover:not(:focus) {
        border-color: rgba(230, 126, 34, 0.4);
    }

    /* Select customizado */
    #modalAgendamento .form-select {
        cursor: pointer;

        background-position: right var(--spacing-3) center;
        background-size: 16px;
        padding-right: var(--spacing-6);
    }

    /* Botão Toggle Recorrência Premium */
    #modalAgendamento #agRecorrenteToggle {
        background: var(--glass-bg);
        border: 2px solid var(--glass-border);
        color: var(--color-text);
        border-radius: var(--radius-md);
        padding: var(--spacing-3) var(--spacing-4);
        font-weight: 600;
        font-size: var(--font-size-sm);
        transition: var(--transition-normal);
        position: relative;
        overflow: hidden;
    }

    #modalAgendamento #agRecorrenteToggle::before {
        content: '🔄';
        margin-right: var(--spacing-2);
        font-size: var(--font-size-base);
    }

    #modalAgendamento #agRecorrenteToggle:hover {
        background: var(--color-surface-muted);
        border-color: var(--color-primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    #modalAgendamento #agRecorrenteToggle[data-recorrente="1"] {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        border-color: var(--color-primary);
        color: var(--branco);
    }

    /* Checkboxes Modernos */
    #modalAgendamento .form-check {
        padding-left: 0;
    }

    #modalAgendamento .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid var(--glass-border);
        background: var(--color-surface-muted);
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition-fast);
        margin-right: var(--spacing-2);
        margin: 0 10px;
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

    /* Footer Moderno */
    #modalAgendamento .modal-footer {
        border: 0;
        padding: var(--spacing-4) var(--spacing-6) var(--spacing-6);
        background: transparent;
        gap: var(--spacing-3);
    }

    /* Botões Premium */
    #modalAgendamento .btn {
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        padding: var(--spacing-3) var(--spacing-6);
        transition: var(--transition-normal);
        border: none;
        font-family: var(--font-primary);
        letter-spacing: 0.02em;
    }

    #modalAgendamento .btn-outline-secondary {
        background: var(--glass-bg);
        border: 2px solid var(--glass-border);
        color: var(--color-text);
    }

    #modalAgendamento .btn-outline-secondary:hover {
        background: var(--color-surface-muted);
        border-color: var(--color-text-muted);
        color: var(--color-text);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    #modalAgendamento .btn-primary {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: var(--branco);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        position: relative;
        overflow: hidden;
    }

    #modalAgendamento .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    #modalAgendamento .btn-primary:hover::before {
        left: 100%;
    }

    #modalAgendamento .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }

    #modalAgendamento .btn-primary:active {
        transform: translateY(0);
    }

    /* Grid responsivo melhorado */
    #modalAgendamento .row {
        --bs-gutter-x: var(--spacing-4);
        --bs-gutter-y: var(--spacing-4);
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

    /* Responsivo */
    @media (max-width: 576px) {
        #modalAgendamento .modal-content {
            border-radius: var(--radius-lg);
        }

        #modalAgendamento .modal-body,
        #modalAgendamento .modal-header,
        #modalAgendamento .modal-footer {
            padding-left: var(--spacing-4);
            padding-right: var(--spacing-4);
        }
    }
</style>

<!-- Modal: Agendar pagamento -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-labelledby="modalAgendamentoTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgendamentoTitle">Agendar pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div id="agAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formAgendamento" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="agTitulo" class="form-label">📝 Título</label>
                            <input type="text" id="agTitulo" name="titulo" class="form-control"
                                placeholder="Ex.: Fatura Nubank" required maxlength="160">
                        </div>

                        <div class="col-md-6">
                            <label for="agDataHora" class="form-label">🗓️ Data/Hora</label>
                            <input type="datetime-local" id="agDataHora" name="data_pagamento" class="form-control"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="agLembrar" class="form-label">⏰ Lembrar antes</label>
                            <select id="agLembrar" name="lembrar_antes_segundos" class="form-select">
                                <option value="0">No horário</option>
                                <option value="3600">1 hora</option>
                                <option value="21600">6 horas</option>
                                <option value="86400">1 dia</option>
                                <option value="172800">2 dias</option>
                                <option value="604800">1 semana</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agTipo" class="form-label">💼 Tipo</label>
                            <select id="agTipo" name="tipo" class="form-select" required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agCategoria" class="form-label">🏷️ Categoria</label>
                            <select id="agCategoria" name="categoria_id" class="form-select" required>
                                <option value="">Selecione uma categoria</option>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agConta" class="form-label">🏦 Conta</label>
                            <select id="agConta" name="conta_id" class="form-select">
                                <option value="">Todas as contas (opcional)</option>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agValor" class="form-label">💰 Valor</label>
                            <input type="text" id="agValor" name="valor" class="form-control money-mask"
                                placeholder="R$ 0,00">
                        </div>

                        <div class="col-12">
                            <label for="agDescricao" class="form-label">📄 Descrição</label>
                            <input type="text" id="agDescricao" name="descricao" class="form-control"
                                placeholder="Informações adicionais (opcional)">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">🔄 Recorrência</label>
                            <button type="button" id="agRecorrenteToggle" class="btn w-100" data-recorrente="0">
                                Não, agendamento único
                            </button>
                            <input type="hidden" id="agRecorrente" name="recorrente" value="0">
                        </div>

                        <div class="col-md-6 teste">
                            <label class="form-label d-block">📢 Canais de notificação</label>
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
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" form="formAgendamento" class="btn btn-primary">
                    Salvar Agendamento
                </button>
            </div>
        </div>
    </div>
</div>
