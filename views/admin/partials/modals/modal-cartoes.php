<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-cartoes.css?v=<?= time() ?>">

<!-- Modal de Cartão de Crédito -->
<div class="modal-overlay" id="modalCartaoOverlay">
    <div class="modal-container" id="modalCartao" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalCartaoTitulo">Novo Cartão de Crédito</h2>
                    <p class="modal-subtitle">Preencha os dados do seu cartão</p>
                </div>
            </div>
            <button class="modal-close" type="button" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formCartao" autocomplete="off">
                <input type="hidden" id="cartaoId" name="cartao_id">

                <!-- Nome do Cartão -->
                <div class="form-group">
                    <label for="nomeCartao" class="form-label required">
                        <i data-lucide="tag"></i>
                        Nome do Cartão
                    </label>
                    <input type="text" id="nomeCartao" name="nome_cartao" class="form-input"
                        placeholder="Ex: Nubank Platinum, Itaú Gold" required maxlength="100">
                </div>

                <!-- Conta Vinculada -->
                <div class="form-group">
                    <label for="contaVinculada" class="form-label required">
                        <i data-lucide="link"></i>
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

                    <!-- Últimos dígitos -->
                    <div class="form-group">
                        <label for="ultimosDigitos" class="form-label required">
                            <i data-lucide="hash"></i>
                            Últimos 4 dígitos
                        </label>
                        <input type="text" id="ultimosDigitos" name="ultimos_digitos" class="form-input"
                            placeholder="1234" required maxlength="4" pattern="\d{4}">
                    </div>
                </div>

                <!-- Limite Total -->
                <div class="form-group">
                    <label for="limiteTotal" class="form-label">
                        <i data-lucide="banknote"></i>
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
                        <label for="diaFechamento" class="form-label lk-label">
                            <i data-lucide="calendar-check" aria-hidden="true"></i>
                            <span>Dia de fechamento</span>

                            <button type="button" class="lk-info" data-lk-tooltip-title="Dia de fechamento"
                                data-lk-tooltip="Compras realizadas a partir deste dia serão lançadas na fatura do mês seguinte. Use esta data conforme a regra do seu cartão."
                                aria-label="Ajuda: Dia de fechamento">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>

                        <input type="number" id="diaFechamento" name="dia_fechamento" class="form-input" min="1"
                            max="31" placeholder="Ex: 10" required>
                        <small class="form-help">Dia que a fatura fecha</small>
                    </div>

                    <!-- Dia Vencimento -->
                    <div class="form-group">
                        <label for="diaVencimento" class="form-label lk-label">
                            <i data-lucide="calendar-days" aria-hidden="true"></i>
                            <span>Dia de vencimento</span>

                            <button type="button" class="lk-info" data-lk-tooltip-title="Dia de vencimento"
                                data-lk-tooltip="Data limite para pagar a fatura sem juros. Normalmente fica alguns dias após o dia de fechamento."
                                aria-label="Ajuda: Dia de vencimento">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>
                        <input type="number" id="diaVencimento" name="dia_vencimento" class="form-input" min="1"
                            max="31" placeholder="Ex: 15" required>
                        <small class="form-help">Data limite para pagar sem juros</small>
                    </div>
                </div>

                <!-- Lembrete de Fatura -->
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
                    <small class="form-help">Receba um aviso antes do vencimento da fatura</small>

                    <div id="cartaoCanaisLembrete" style="display: none; margin-top: 8px;">
                        <div style="display: flex; gap: 16px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" id="cartaoCanalInapp" name="fatura_canal_inapp" checked
                                    style="width: auto;">
                                <i data-lucide="bell" style="width: 14px; height: 14px;"></i>
                                Notificação
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" id="cartaoCanalEmail" name="fatura_canal_email"
                                    style="width: auto;">
                                <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                                E-mail
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        Salvar Cartão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
