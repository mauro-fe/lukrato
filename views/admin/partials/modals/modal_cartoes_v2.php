<!-- Modal de Cartão de Crédito MODERNO -->
<div class="lk-modal-overlay" id="modalCartaoOverlay" onclick="contasManager.closeCartaoModal()">
    <div class="lk-modal-modern" id="modalCartao" onclick="event.stopPropagation()" role="dialog" aria-labelledby="modalCartaoTitulo">
        <!-- Header com Gradiente Roxo -->
        <div class="lk-modal-header-gradient" style="background: linear-gradient(135deg, #8A05BE 0%, #5B047B 100%);">
            <div class="lk-modal-icon-wrapper">
                <i class="fas fa-credit-card"></i>
            </div>
            <h2 class="lk-modal-title" id="modalCartaoTitulo">Novo Cartão de Crédito</h2>
            <button class="lk-modal-close-btn" onclick="contasManager.closeCartaoModal()" type="button" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
            <form id="formCartao" autocomplete="off">
                <input type="hidden" id="cartaoId" name="cartao_id">

                <!-- Nome do Cartão -->
                <div class="lk-form-group">
                    <label for="nomeCartao" class="lk-label required">
                        <i class="fas fa-tag"></i>
                        Nome do Cartão
                    </label>
                    <input type="text" 
                           id="nomeCartao" 
                           name="nome_cartao" 
                           class="lk-input" 
                           placeholder="Ex: Nubank Platinum, Itaú Gold" 
                           required 
                           maxlength="100">
                </div>

                <!-- Conta Vinculada -->
                <div class="lk-form-group">
                    <label for="contaVinculada" class="lk-label required">
                        <i class="fas fa-link"></i>
                        Conta Vinculada
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="contaVinculada" name="conta_id" class="lk-select" required>
                            <option value="">Selecione a conta</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">Conta onde os pagamentos serão debitados</small>
                </div>

                <!-- Grid de 2 colunas -->
                <div class="lk-form-row">
                    <!-- Bandeira -->
                    <div class="lk-form-group">
                        <label for="bandeira" class="lk-label required">
                            <i class="fas fa-star"></i>
                            Bandeira
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="bandeira" name="bandeira" class="lk-select" required>
                                <option value="">Selecione</option>
                                <option value="visa">💳 Visa</option>
                                <option value="mastercard">💳 Mastercard</option>
                                <option value="elo">💳 Elo</option>
                                <option value="amex">💳 American Express</option>
                                <option value="hipercard">💳 Hipercard</option>
                                <option value="diners">💳 Diners Club</option>
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                    </div>

                    <!-- Últimos dígitos -->
                    <div class="lk-form-group">
                        <label for="ultimosDigitos" class="lk-label required">
                            <i class="fas fa-hashtag"></i>
                            Últimos 4 dígitos
                        </label>
                        <input type="text" 
                               id="ultimosDigitos" 
                               name="ultimos_digitos" 
                               class="lk-input" 
                               placeholder="1234" 
                               required 
                               maxlength="4" 
                               pattern="\d{4}">
                    </div>
                </div>

                <!-- Limite Total com visual premium -->
                <div class="lk-form-group">
                    <label for="limiteTotal" class="lk-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Limite Total
                    </label>
                    <div class="lk-input-money">
                        <span class="lk-currency-symbol">R$</span>
                        <input type="text" 
                               id="limiteTotal" 
                               name="limite_total" 
                               class="lk-input lk-input-with-prefix" 
                               value="0,00"
                               placeholder="0,00"
                               autocomplete="off">
                    </div>
                    <small class="lk-helper-text">💡 Limite total disponível no cartão</small>
                </div>

                <!-- Grid de 2 colunas para datas -->
                <div class="lk-form-row">
                    <!-- Dia de Fechamento -->
                    <div class="lk-form-group">
                        <label for="diaFechamento" class="lk-label">
                            <i class="fas fa-calendar-check"></i>
                            Dia de Fechamento
                        </label>
                        <input type="number" 
                               id="diaFechamento" 
                               name="dia_fechamento" 
                               class="lk-input" 
                               min="1" 
                               max="31" 
                               placeholder="Ex: 10">
                        <small class="lk-helper-text">Dia que a fatura fecha</small>
                    </div>

                    <!-- Dia de Vencimento -->
                    <div class="lk-form-group">
                        <label for="diaVencimento" class="lk-label">
                            <i class="fas fa-calendar-alt"></i>
                            Dia de Vencimento
                        </label>
                        <input type="number" 
                               id="diaVencimento" 
                               name="dia_vencimento" 
                               class="lk-input" 
                               min="1" 
                               max="31" 
                               placeholder="Ex: 15">
                        <small class="lk-helper-text">Dia do vencimento da fatura</small>
                    </div>
                </div>

                <!-- Footer com botões modernos -->
                <div class="lk-modal-footer">
                    <button type="button" class="lk-btn lk-btn-ghost" onclick="contasManager.closeCartaoModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="lk-btn lk-btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Cartão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
