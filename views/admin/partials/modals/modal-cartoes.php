<!-- Modal de Cartao de Credito -->
<div class="lk-modal-overlay" id="modalCartaoOverlay">
    <div class="modal-container" id="modalCartao" role="dialog" aria-modal="true" aria-labelledby="modalCartaoTitulo"
        onclick="event.stopPropagation()">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalCartaoTitulo">Novo Cartao de Credito</h2>
                    <p class="modal-subtitle" id="modalCartaoSubtitle">Preencha os dados do seu cartao.</p>
                </div>
            </div>
            <button class="modal-close" type="button" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-body">
            <form id="formCartao" autocomplete="off">
                <input type="hidden" id="cartaoId" name="cartao_id">

                <div class="form-group">
                    <label for="nomeCartao" class="form-label required">
                        <i data-lucide="tag"></i>
                        Nome do cartao
                    </label>
                    <input type="text" id="nomeCartao" name="nome_cartao" class="form-input"
                        placeholder="Ex: Nubank Platinum, Itau Gold" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="contaVinculada" class="form-label required">
                        <i data-lucide="link"></i>
                        Conta vinculada
                    </label>
                    <select id="contaVinculada" name="conta_id" class="form-select" required>
                        <option value="">Selecione a conta</option>
                    </select>
                    <small class="form-help" id="contaVinculadaHelp">Conta onde o pagamento da fatura sera debitado.</small>
                    <p class="form-inline-warning" id="cartaoContaEmptyHint" hidden>
                        você precisa criar uma conta antes de cadastrar um cartao.
                        <a href="<?= BASE_URL ?>contas">Ir para contas</a>
                    </p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bandeira" class="form-label required">
                            <i data-lucide="star"></i>
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

                    <div class="form-group">
                        <label for="ultimosDigitos" class="form-label required">
                            <i data-lucide="hash"></i>
                            Ultimos 4 digitos
                        </label>
                        <input type="text" id="ultimosDigitos" name="ultimos_digitos" class="form-input"
                            placeholder="1234" required maxlength="4" pattern="\d{4}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="limiteTotal" class="form-label">
                        <i data-lucide="banknote"></i>
                        Limite total
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">R$</span>
                        <input type="text" id="limiteTotal" name="limite_total" class="form-input" placeholder="0,00"
                            required>
                    </div>
                    <small class="form-help">Limite total disponivel no cartao.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="diaFechamento" class="form-label lk-label">
                            <i data-lucide="calendar-check" aria-hidden="true"></i>
                            <span>Dia de fechamento</span>

                            <button type="button" class="lk-info" data-lk-tooltip-title="Dia de fechamento"
                                data-lk-tooltip="Compras feitas a partir desse dia entram na fatura seguinte."
                                aria-label="Ajuda: Dia de fechamento">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>

                        <input type="number" id="diaFechamento" name="dia_fechamento" class="form-input" min="1"
                            max="31" placeholder="Ex: 10" required>
                        <small class="form-help">Dia em que a fatura fecha.</small>
                    </div>

                    <div class="form-group">
                        <label for="diaVencimento" class="form-label lk-label">
                            <i data-lucide="calendar-days" aria-hidden="true"></i>
                            <span>Dia de vencimento</span>

                            <button type="button" class="lk-info" data-lk-tooltip-title="Dia de vencimento"
                                data-lk-tooltip="Ultimo dia para pagar a fatura sem juros."
                                aria-label="Ajuda: Dia de vencimento">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>
                        <input type="number" id="diaVencimento" name="dia_vencimento" class="form-input" min="1"
                            max="31" placeholder="Ex: 15" required>
                        <small class="form-help">Data limite para pagar sem juros.</small>
                    </div>
                </div>

                <div class="form-group" style="margin-top: var(--spacing-4);">
                    <label class="form-label lk-label">
                        <i data-lucide="bell" aria-hidden="true"></i>
                        <span>Lembrete de vencimento</span>
                    </label>
                    <select id="cartaoLembreteAviso" name="lembrar_fatura_antes_segundos" class="form-select">
                        <option value="">Sem lembrete</option>
                        <option value="3600">1 hora antes</option>
                        <option value="21600">6 horas antes</option>
                        <option value="43200">12 horas antes</option>
                        <option value="86400">1 dia antes</option>
                        <option value="172800">2 dias antes</option>
                        <option value="259200">3 dias antes</option>
                        <option value="604800">1 semana antes</option>
                    </select>
                    <small class="form-help">Receba um aviso antes do vencimento da fatura.</small>

                    <div id="cartaoCanaisLembrete" style="display: none; margin-top: 8px;">
                        <div class="reminder-channels">
                            <label class="reminder-channel">
                                <input type="checkbox" id="cartaoCanalInapp" name="fatura_canal_inapp" checked
                                    style="width: auto;">
                                <i data-lucide="bell" style="width: 14px; height: 14px;"></i>
                                Notificacao
                            </label>
                            <label class="reminder-channel">
                                <input type="checkbox" id="cartaoCanalEmail" name="fatura_canal_email"
                                    style="width: auto;">
                                <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                                E-mail
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close-btn">
                        <i data-lucide="x"></i>
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary" id="btnSalvarCartao">
                        <i data-lucide="save"></i>
                        <span id="cartaoSubmitLabel">Salvar cartao</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>