<!-- ====== STEP 3: Forma de Pagamento / Conta Destino ====== -->
<div class="lk-wizard-step" data-step="3" id="globalStep3">
    <div class="lk-wizard-question" id="globalStep3Question">
        <h3>
            <i data-lucide="wallet"></i>
            <span id="globalStep3Title">Como você pagou?</span>
        </h3>
        <p id="globalStep3Subtitle">Escolha a forma de pagamento</p>
    </div>

    <!-- Conta Destino (somente para transferência) -->
    <div class="lk-form-group" id="globalContaDestinoGroup" style="display: none;">
        <label for="globalLancamentoContaDestino" class="lk-label required">
            <i data-lucide="arrow-left-right"></i>
            Conta de Destino
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoContaDestino" name="conta_destino_id" class="lk-select"
                data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                data-lk-select-search-placeholder="Buscar conta de destino...">
                <option value="">Selecione a conta de destino</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
        <small class="lk-helper-text">Para onde o dinheiro vai ser transferido</small>
    </div>

    <!-- Forma de Pagamento (para despesas) -->
    <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaPagamentoGroup" style="display: none;">
        <input type="hidden" id="globalFormaPagamento" name="forma_pagamento" value="">
        <label class="lk-forma-pagamento-label">
            <i data-lucide="wallet"></i>
            Como você vai pagar?
        </label>
        <div class="lk-forma-pagamento-grid" id="globalFormaPagamentoGrid">
            <button type="button" class="lk-forma-btn" data-forma="pix"
                onclick="lancamentoGlobalManager.selecionarFormaPagamento('pix')">
                <i class="fa-brands fa-pix lk-forma-icon"></i>
                <span class="lk-forma-label">PIX</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="cartao_credito"
                onclick="lancamentoGlobalManager.selecionarFormaPagamento('cartao_credito')">
                <i data-lucide="credit-card" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Crédito</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="cartao_debito"
                onclick="lancamentoGlobalManager.selecionarFormaPagamento('cartao_debito')">
                <i data-lucide="credit-card" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Débito</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="dinheiro"
                onclick="lancamentoGlobalManager.selecionarFormaPagamento('dinheiro')">
                <i data-lucide="banknote" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Dinheiro</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="boleto"
                onclick="lancamentoGlobalManager.selecionarFormaPagamento('boleto')">
                <i data-lucide="scan-line" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Boleto</span>
            </button>
        </div>
    </div>

    <!-- Forma de Recebimento (para receitas) -->
    <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaRecebimentoGroup" style="display: none;">
        <input type="hidden" id="globalFormaRecebimento" name="forma_recebimento" value="">
        <label class="lk-forma-pagamento-label">
            <i data-lucide="hand-coins"></i>
            Como você vai receber?
        </label>
        <div class="lk-forma-pagamento-grid" id="globalFormaRecebimentoGrid">
            <button type="button" class="lk-forma-btn" data-forma="pix"
                onclick="lancamentoGlobalManager.selecionarFormaRecebimento('pix')">
                <i class="fa-brands fa-pix lk-forma-icon"></i>
                <span class="lk-forma-label">PIX</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="deposito"
                onclick="lancamentoGlobalManager.selecionarFormaRecebimento('deposito')">
                <i data-lucide="landmark" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Depósito</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="dinheiro"
                onclick="lancamentoGlobalManager.selecionarFormaRecebimento('dinheiro')">
                <i data-lucide="banknote" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Dinheiro</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="transferencia"
                onclick="lancamentoGlobalManager.selecionarFormaRecebimento('transferencia')">
                <i data-lucide="arrow-left-right" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Transf.</span>
            </button>
            <button type="button" class="lk-forma-btn" data-forma="estorno_cartao"
                onclick="lancamentoGlobalManager.selecionarFormaRecebimento('estorno_cartao')">
                <i data-lucide="rotate-ccw" class="lk-forma-icon"></i>
                <span class="lk-forma-label">Estorno</span>
            </button>
        </div>
    </div>

    <!-- Seleção de Cartão (quando forma é cartão de crédito) -->
    <div class="lk-form-group lk-forma-cartao-info" id="globalCartaoCreditoGroup">
        <label for="globalLancamentoCartaoCredito" class="lk-label">
            <i data-lucide="credit-card"></i>
            Qual cartão?
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoCartaoCredito" name="cartao_credito_id" class="lk-select"
                data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                data-lk-select-search-placeholder="Buscar cartao..."
                onchange="typeof lancamentoGlobalManager !== 'undefined' && lancamentoGlobalManager.onCartaoEstornoChange && lancamentoGlobalManager.onCartaoEstornoChange()">
                <option value="">Selecione o cartão</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
        <small class="lk-helper-text">O débito será na data de vencimento da fatura</small>
    </div>

    <!-- Seleção de Fatura para Estorno -->
    <div class="lk-form-group" id="globalFaturaEstornoGroup" style="display: none;">
        <label for="globalLancamentoFaturaEstorno" class="lk-label">
            <i data-lucide="receipt"></i>
            Em qual fatura aplicar o estorno?
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoFaturaEstorno" name="fatura_mes_ano" class="lk-select"
                data-lk-custom-select="modal" data-lk-select-search="true"
                data-lk-select-search-placeholder="Buscar fatura...">
                <option value="">Carregando faturas...</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
        <small class="lk-helper-text">O estorno será creditado na fatura selecionada</small>
    </div>

    <!-- Parcelamento (somente se cartão selecionado) -->
    <div class="lk-form-group" id="globalParcelamentoGroup" style="display: none;">
        <div class="lk-checkbox-wrapper">
            <label class="lk-checkbox-label">
                <input type="checkbox" id="globalLancamentoParcelado" name="eh_parcelado" class="lk-checkbox">
                <span class="lk-checkbox-custom"></span>
                <span class="lk-checkbox-text">
                    <i data-lucide="calendar-days"></i>
                    <span class="lk-parcel-texto" id="globalParcelamentoTexto">Parcelar compra no
                        cartão</span>
                </span>
            </label>
        </div>
        <small class="lk-helper-text" id="globalParcelamentoHelperText">O valor total será dividido
            entre as próximas faturas.</small>
    </div>

    <!-- Assinatura / Recorrência no Cartão (somente se cartão selecionado) -->
    <div class="lk-form-group" id="globalAssinaturaCartaoGroup" style="display: none;">
        <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
            <label class="lk-checkbox-label">
                <input type="checkbox" id="globalLancamentoAssinaturaCartao" name="recorrente_cartao" value="1"
                    class="lk-checkbox" onchange="lancamentoGlobalManager.toggleAssinaturaCartao()">
                <span class="lk-checkbox-custom"></span>
                <span class="lk-checkbox-text">
                    <i data-lucide="refresh-cw"></i>
                    Assinatura / Recorrente
                </span>
            </label>
        </div>
        <small class="lk-helper-text" style="margin-top: -0.25rem; margin-bottom: 0.5rem;">
            Ex: Spotify, ChatGPT, Netflix — cobra todo mês automaticamente
        </small>

        <div id="globalAssinaturaCartaoDetalhes" style="display: none;">
            <label class="lk-label">
                <i data-lucide="refresh-cw"></i>
                Frequência da cobrança
            </label>
            <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                <select id="globalLancamentoAssinaturaFreq" name="recorrencia_freq_cartao" class="lk-select"
                    data-lk-custom-select="modal" data-lk-select-search="true"
                    data-lk-select-search-placeholder="Buscar frequencia...">
                    <option value="mensal" selected>Mensal</option>
                    <option value="bimestral">Bimestral</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="semestral">Semestral</option>
                    <option value="anual">Anual</option>
                </select>
                <i data-lucide="chevron-down" class="lk-select-icon"></i>
            </div>

            <label class="lk-label">
                <i data-lucide="flag"></i>
                Até quando?
            </label>
            <div class="lk-radio-group" style="margin-bottom: 0.5rem;">
                <label class="lk-radio-label">
                    <input type="radio" name="global_assinatura_modo" value="infinito" class="lk-radio" checked
                        onchange="lancamentoGlobalManager.toggleAssinaturaCartaoFim()">
                    <span class="lk-radio-custom"></span>
                    <span class="lk-radio-text">Sem data de fim <small style="opacity:0.7">(cancelo
                            quando quiser)</small></span>
                </label>
                <label class="lk-radio-label">
                    <input type="radio" name="global_assinatura_modo" value="data" class="lk-radio"
                        onchange="lancamentoGlobalManager.toggleAssinaturaCartaoFim()">
                    <span class="lk-radio-custom"></span>
                    <span class="lk-radio-text">Até uma data específica</span>
                </label>
            </div>

            <div id="globalAssinaturaCartaoFimGroup" style="display: none;">
                <input type="date" id="globalLancamentoAssinaturaFim" name="recorrencia_fim_cartao" class="lk-input">
                <small class="lk-helper-text">Data em que a assinatura termina.</small>
            </div>
        </div>
    </div>

    <!-- Número de Parcelas (quando parcelado) -->
    <div class="lk-form-group" id="globalNumeroParcelasGroup" style="display: none;">
        <label for="globalLancamentoTotalParcelas" class="lk-label required">
            <i data-lucide="list-ordered"></i>
            <span id="globalNumeroParcelasLabelTexto">Número de parcelas</span>
        </label>
        <div class="lk-input-group">
            <input type="number" id="globalLancamentoTotalParcelas" name="total_parcelas" class="lk-input" min="2"
                max="48" value="2" placeholder="12">
            <span class="lk-input-suffix" id="globalNumeroParcelasSuffixTexto">parcelas</span>
        </div>
        <div id="globalParcelamentoPreview" class="lk-parcelamento-preview" style="display: none;">
        </div>
    </div>

    <!-- Nav -->
    <div class="lk-wizard-nav">
        <div class="lk-wizard-nav-left">
            <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.prevStep()">
                <i data-lucide="arrow-left"></i>
                Voltar
            </button>
        </div>
        <div class="lk-wizard-nav-right">
            <button type="button" class="lk-btn lk-btn-primary" onclick="lancamentoGlobalManager.nextStep()">
                Próximo
                <i data-lucide="arrow-right"></i>
            </button>
        </div>
    </div>
</div>