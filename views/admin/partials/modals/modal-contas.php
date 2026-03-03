<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-contas.css?v=<?= time() ?>">

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
                            <option value="conta_investimento">Investimento</option>
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

<!-- Modal de Nova Instituição -->
<div class="lk-modal-overlay" id="modalNovaInstituicaoOverlay" style="z-index: 10001;">
    <div class="modal-container" id="modalNovaInstituicao" onclick="event.stopPropagation()" style="max-width: 480px;">
        <!-- Header -->
        <div class="modal-header" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="circle-plus" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title">Nova Instituição</h2>
                    <p class="modal-subtitle">Adicione um banco que não está na lista</p>
                </div>
            </div>
            <button class="modal-close modal-close-btn" type="button"
                onclick="contasManager.closeNovaInstituicaoModal()" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form id="formNovaInstituicao" autocomplete="off">
                <!-- Nome da Instituição -->
                <div class="form-group">
                    <label for="nomeInstituicao" class="form-label required">
                        <i data-lucide="building-2"></i>
                        Nome da Instituição
                    </label>
                    <input type="text" id="nomeInstituicao" name="nome" class="form-input"
                        placeholder="Ex: Banco XYZ, Cooperativa ABC" required maxlength="100">
                </div>

                <!-- Tipo -->
                <div class="form-group">
                    <label for="tipoInstituicao" class="form-label required">
                        <i data-lucide="tag"></i>
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
                        <i data-lucide="palette"></i>
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
                        <i data-lucide="x"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        Adicionar Instituição
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
