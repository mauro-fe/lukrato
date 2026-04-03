<!-- Modal de Nova Conta -->
<div class="lk-modal-overlay" id="modalContaOverlay">
    <div class="modal-container" id="modalConta" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="landmark" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalContaTitulo">Nova Conta</h2>
                    <p class="modal-subtitle">Adicione uma nova conta bancária</p>
                </div>
            </div>
            <button class="modal-close modal-close-btn" type="button" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formConta" autocomplete="off">
                <input type="hidden" id="contaId" name="conta_id">

                <!-- Nome da Conta -->
                <div class="form-group">
                    <label for="nomeConta" class="form-label required">
                        <i data-lucide="tag"></i>
                        Nome da Conta
                    </label>
                    <input type="text" id="nomeConta" name="nome" class="form-input"
                        placeholder="Ex: Nubank Conta, Itaú Poupança" required maxlength="100">
                </div>

                <!-- Instituição Financeira -->
                <div class="form-group">
                    <label for="instituicaoFinanceiraSelect" class="form-label">
                        <i data-lucide="building-2"></i>
                        Instituição Financeira
                    </label>
                    <div class="input-with-action">
                        <select id="instituicaoFinanceiraSelect" name="instituicao_financeira_id" class="form-select">
                            <option value="">Selecione uma instituição</option>
                        </select>
                        <button type="button" class="btn-add-instituicao" id="btnAddInstituicao"
                            title="Adicionar nova instituição">
                            <i data-lucide="plus"></i>
                        </button>
                    </div>
                    <small class="form-help">Escolha o banco ou fintech desta conta</small>
                </div>

                <!-- Grid 2 colunas -->
                <div class="form-row">
                    <!-- Tipo de Conta -->
                    <div class="form-group">
                        <label for="tipoContaSelect" class="form-label required">
                            <i data-lucide="wallet"></i>
                            Tipo de Conta
                        </label>
                        <select id="tipoContaSelect" name="tipo_conta" class="form-select" required>
                            <option value="conta_corrente">Conta Corrente</option>
                            <option value="conta_poupanca">Conta Poupança</option>
                            <option value="carteira_digital">Carteira Digital</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>

                    <!-- Moeda -->
                    <div class="form-group">
                        <label for="moedaSelect" class="form-label required">
                            <i data-lucide="dollar-sign"></i>
                            Moeda
                        </label>
                        <select id="moedaSelect" name="moeda" class="form-select" required>
                            <option value="BRL" selected>Real (BRL)</option>
                            <!-- <option value="USD">Dólar (USD)</option>
                            <option value="EUR">Euro (EUR)</option> -->
                        </select>
                    </div>
                </div>

                <!-- Saldo Inicial -->
                <div class="form-group">
                    <label for="saldoInicial" class="form-label">
                        <i data-lucide="coins"></i>
                        Saldo Inicial
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">R$</span>
                        <input type="text" id="saldoInicial" name="saldo_inicial" class="form-input" value="0,00"
                            placeholder="0,00">
                    </div>
                    <small class="form-help">Quanto tem na conta agora. Lançamentos são calculados a partir deste valor.</small>
                </div>

                <!-- Cor da Conta -->
                <div class="form-group" style="display: none;">
                    <label for="corConta" class="form-label">
                        <i data-lucide="palette"></i>
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
                        <i data-lucide="save"></i>
                        Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
