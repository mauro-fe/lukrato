<style>
    #modalAgendamento .modal-content {
        background: var(--color-surface) !important;
        color: var(--color-text);
        border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-lg);
        font-family: var(--font-primary);
    }

    #modalAgendamento .modal-header,
    #modalAgendamento .modal-footer {
        border: 0;
        background: transparent;
        color: var(--color-text);
    }

    #modalAgendamento .modal-title {
        font-size: var(--font-size-lg);
        font-weight: 600;
        color: var(--color-primary);
    }

    /* Botões */
    #modalAgendamento .btn {
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        transition: var(--transition-fast);
    }

    #modalAgendamento .btn-outline-light,
    #modalAgendamento .btn-secondary {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--color-text);
    }

    #modalAgendamento .btn-outline-light:hover,
    #modalAgendamento .btn-secondary:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    /* Grade de meses */
    #modalAgendamento #mpGrid .btn {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--color-text);
    }

    #modalAgendamento #mpGrid .btn:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    #modalAgendamento #mpGrid .btn.active {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: var(--color-primary) !important;
        box-shadow: 0 0 0 2px var(--ring);
    }

    /* Input month */
    #modalAgendamento input[type="month"] {
        background: var(--color-surface-muted);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-sm);
        color: var(--color-text);
    }

    #modalAgendamento input[type="month"]:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--ring);
        color: var(--color-primary) !important;
    }

    #modalAgendamento .modal-content {
        background: var(--color-surface) !important;
        color: var(--color-text);
        border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-lg);
        font-family: var(--font-primary);
    }

    #modalAgendamento .modal-header,
    #modalAgendamento .modal-footer {
        border: 0;
        background: transparent;
        color: var(--color-text);
    }

    #modalAgendamento .modal-title {
        font-size: var(--font-size-lg);
        font-weight: 600;
        color: var(--color-primary);
    }

    /* Botões */
    #modalAgendamento .btn {
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        transition: var(--transition-fast);
    }

    #modalAgendamento .btn-outline-secondary,
    #modalAgendamento .btn-outline-light {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--color-text);
    }

    #modalAgendamento .btn-outline-secondary:hover,
    #modalAgendamento .btn-outline-light:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    #modalAgendamento .btn-primary {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: var(--branco);
    }

    #modalAgendamento .btn-primary:hover {
        filter: brightness(1.1);
    }

    /* Inputs e selects */
    #modalAgendamento .form-label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        margin-bottom: 0.25rem;
    }

    #modalAgendamento .form-control,
    #modalAgendamento .form-select {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        padding: var(--spacing-3);
    }

    #modalAgendamento .form-control::placeholder {
        color: color-mix(in srgb, var(--color-text) 60%, transparent);
    }

    #modalAgendamento .form-control:focus,
    #modalAgendamento .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--ring);
        color: var(--color-text) !important;
    }
</style>

<!-- Modal: Agendar pagamento -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-labelledby="modalAgendamentoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalAgendamentoTitle">Agendar pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-0">
                <div id="agAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formAgendamento" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="agTitulo" class="form-label text-light small mb-1">Titulo</label>
                            <input type="text" id="agTitulo" name="titulo"
                                class="form-control form-control-sm bg-dark text-light border-secondary"
                                placeholder="Ex.: Fatura Nubank" required maxlength="160">
                        </div>

                        <div class="col-md-6">
                            <label for="agDataHora" class="form-label text-light small mb-1">Data/Hora do
                                pagamento</label>
                            <input type="datetime-local" id="agDataHora" name="data_pagamento"
                                class="form-control form-control-sm bg-dark text-light border-secondary" required>
                        </div>

                        <div class="col-md-6">
                            <label for="agLembrar" class="form-label text-light small mb-1">Lembrar antes</label>
                            <select id="agLembrar" name="lembrar_antes_segundos"
                                class="form-select form-select-sm bg-dark text-light border-secondary">
                                <option value="0">No horario</option>
                                <option value="3600">1 hora</option>
                                <option value="21600">6 horas</option>
                                <option value="86400">1 dia</option>
                                <option value="172800">2 dias</option>
                                <option value="604800">1 semana</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agTipo" class="form-label text-light small mb-1">Tipo</label>
                            <select id="agTipo" name="tipo" class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agCategoria" class="form-label text-light small mb-1">Categoria</label>
                            <select id="agCategoria" name="categoria_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agConta" class="form-label text-light small mb-1">Conta</label>
                            <select id="agConta" name="conta_id" class="form-select form-select-sm bg-dark text-light border-secondary">
                                <option value="">Todas as contas (opcional)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="agValor" class="form-label text-light small mb-1">Valor</label>
                            <input type="text" id="agValor" name="valor"
                                class="form-control form-control-sm bg-dark text-light border-secondary money-mask"
                                placeholder="R$ 0,00">
                        </div>

                        <div class="col-12">
                            <label for="agDescricao" class="form-label text-light small mb-1">Descricao</label>
                            <input type="text" id="agDescricao" name="descricao"
                                class="form-control form-control-sm bg-dark text-light border-secondary"
                                placeholder="Opcional">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small mb-1 d-block">Recorrencia</label>
                            <button type="button" id="agRecorrenteToggle" class="btn btn-outline-secondary btn-sm w-100"
                                data-recorrente="0">
                                Não, agendamento unico
                            </button>
                            <input type="hidden" id="agRecorrente" name="recorrente" value="0">
                        </div>

                        <div class="col-md-6">
                            <span class="form-label text-light small mb-1 d-block">Canais</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="agCanalInapp" name="canal_inapp" value="1" checked>
                                <label class="form-check-label" for="agCanalInapp">Aviso no sistema</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="agCanalEmail" name="canal_email" value="1" checked>
                                <label class="form-check-label" for="agCanalEmail">E-mail</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formAgendamento" class="btn btn-primary btn-sm">Salvar</button>
            </div>
        </div>
    </div>
</div>
