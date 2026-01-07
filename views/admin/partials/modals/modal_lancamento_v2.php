<!-- Modal de Novo Lançamento -->
<div class="lk-modal-overlay" id="modalLancamentoOverlay" onclick="contasManager.closeLancamentoModal()">
    <div class="lk-modal-modern lk-modal-lancamento" onclick="event.stopPropagation()" role="dialog"
        aria-labelledby="modalLancamentoTitulo">
        <!-- Header com Gradiente -->
        <div class="lk-modal-header-gradient"">
            <div class=" lk-modal-icon-wrapper">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <h2 class="lk-modal-title" id="modalLancamentoTitulo">Nova Movimentação</h2>
        <button class="lk-modal-close-btn" onclick="contasManager.closeLancamentoModal()" type="button"
            aria-label="Fechar modal">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Body do Modal -->
    <div class="lk-modal-body-modern">
        <!-- Conta Selecionada -->
        <div class="lk-conta-info">
            <div class="lk-conta-badge">
                <i class="fas fa-wallet"></i>
                <span id="lancamentoContaNome">Conta</span>
            </div>
            <div class="lk-conta-saldo">
                Saldo atual: <strong id="lancamentoContaSaldo">R$ 0,00</strong>
            </div>
        </div>

        <!-- Histórico Recente -->
        <div class="lk-historico-section">
            <h3 class="lk-section-title">
                <i class="fas fa-history"></i>
                Últimas Movimentações
            </h3>
            <div class="lk-historico-list" id="lancamentoHistorico">
                <!-- Preenchido via JS -->
                <div class="lk-historico-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma movimentação recente</p>
                </div>
            </div>
        </div>

        <!-- Escolha do Tipo de Lançamento -->
        <div class="lk-tipo-section" id="tipoSection">
            <h3 class="lk-section-title">
                <i class="fas fa-tasks"></i>
                Escolha o tipo de movimentação
            </h3>

            <div class="lk-tipo-grid">
                <!-- Receita -->
                <button type="button" class="lk-tipo-card lk-tipo-receita"
                    onclick="contasManager.mostrarFormularioLancamento('receita')">
                    <div class="lk-tipo-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <h4>Receita</h4>
                    <p>Dinheiro que entra</p>
                    <div class="lk-tipo-badge">+ Entrada</div>
                </button>

                <!-- Despesa -->
                <button type="button" class="lk-tipo-card lk-tipo-despesa"
                    onclick="contasManager.mostrarFormularioLancamento('despesa')">
                    <div class="lk-tipo-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <h4>Despesa</h4>
                    <p>Dinheiro que sai</p>
                    <div class="lk-tipo-badge">- Saída</div>
                </button>

                <!-- Transferência -->
                <button type="button" class="lk-tipo-card lk-tipo-transferencia"
                    onclick="contasManager.mostrarFormularioLancamento('transferencia')">
                    <div class="lk-tipo-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h4>Transferência</h4>
                    <p>Entre contas</p>
                    <div class="lk-tipo-badge">⇄ Mover</div>
                </button>
            </div>
        </div>

        <!-- Formulário de Lançamento (oculto inicialmente) -->
        <div class="lk-form-section" id="formSection" style="display: none;">
            <!-- Botão voltar -->
            <button type="button" class="lk-btn-voltar" onclick="contasManager.voltarEscolhaTipo()">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </button>

            <form id="formLancamento" autocomplete="off">
                <input type="hidden" id="lancamentoContaId" name="conta_id">
                <input type="hidden" id="lancamentoTipo" name="tipo">

                <!-- Descrição -->
                <div class="lk-form-group">
                    <label for="lancamentoDescricao" class="lk-label required">
                        <i class="fas fa-align-left"></i>
                        Descrição
                    </label>
                    <input type="text" id="lancamentoDescricao" name="descricao" class="lk-input"
                        placeholder="Ex: Salário, Aluguel, Compras..." required maxlength="200">
                </div>

                <!-- Valor -->
                <div class="lk-form-group">
                    <label for="lancamentoValor" class="lk-label required">
                        <i class="fas fa-dollar-sign"></i>
                        Valor
                    </label>
                    <div class="lk-input-money">
                        <span class="lk-currency-symbol">R$</span>
                        <input type="text" id="lancamentoValor" name="valor" class="lk-input lk-input-with-prefix"
                            value="0,00" placeholder="0,00" autocomplete="off" required>
                    </div>
                </div>

                <!-- Conta Destino (somente para transferência) -->
                <div class="lk-form-group" id="contaDestinoGroup" style="display: none;">
                    <label for="lancamentoContaDestino" class="lk-label required">
                        <i class="fas fa-exchange-alt"></i>
                        Conta de Destino
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoContaDestino" name="conta_destino_id" class="lk-select">
                            <option value="">Selecione a conta de destino</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">Para onde o dinheiro vai ser transferido</small>
                </div>

                <!-- Cartão de Crédito (somente para despesa) -->
                <div class="lk-form-group" id="cartaoCreditoGroup" style="display: none;">
                    <label for="lancamentoCartaoCredito" class="lk-label">
                        <i class="fas fa-credit-card"></i>
                        Pagar com Cartão de Crédito
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoCartaoCredito" name="cartao_credito_id" class="lk-select">
                            <option value="">Não usar cartão (débito na conta)</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">Se usar cartão, o débito será na data de vencimento da fatura</small>
                </div>

                <!-- Parcelamento (somente se cartão selecionado) -->
                <div class="lk-form-group" id="parcelamentoGroup" style="display: none;">
                    <div class="lk-checkbox-wrapper">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="lancamentoParcelado" name="eh_parcelado" class="lk-checkbox">
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i class="fas fa-calendar-alt"></i>
                                Parcelar compra
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Número de Parcelas (quando parcelado) -->
                <div class="lk-form-group" id="numeroParcelasGroup" style="display: none;">
                    <label for="lancamentoTotalParcelas" class="lk-label required">
                        <i class="fas fa-list-ol"></i>
                        Número de Parcelas
                    </label>
                    <div class="lk-input-group">
                        <input type="number" id="lancamentoTotalParcelas" name="total_parcelas" class="lk-input"
                            min="2" max="48" value="2" placeholder="12">
                        <span class="lk-input-suffix">vezes</span>
                    </div>
                    <div id="parcelamentoPreview" class="lk-parcelamento-preview" style="display: none;">
                        <!-- Preview preenchido via JS -->
                    </div>
                </div>

                <!-- Data -->
                <div class="lk-form-group">
                    <label for="lancamentoData" class="lk-label required">
                        <i class="fas fa-calendar"></i>
                        Data
                    </label>
                    <input type="date" id="lancamentoData" name="data" class="lk-input" required>
                </div>

                <!-- Categoria (não obrigatório) -->
                <div class="lk-form-group" id="categoriaGroup">
                    <label for="lancamentoCategoria" class="lk-label">
                        <i class="fas fa-tag"></i>
                        Categoria
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoCategoria" name="categoria_id" class="lk-select">
                            <option value="">Selecione (opcional)</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                </div>


                <!-- Footer -->
                <div class="lk-modal-footer">
                    <button type="button" class="lk-btn lk-btn-ghost" onclick="contasManager.closeLancamentoModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="lk-btn lk-btn-primary" id="btnSalvarLancamento">
                        <i class="fas fa-check"></i>
                        Salvar Lançamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<style>
    /* Modal de Lançamento */
    .lk-modal-lancamento {
        max-width: 720px !important;
        max-height: 90vh !important;
        display: flex;
        flex-direction: column;
        position: relative;
        align-self: flex-start;
        margin-top: 5vh;
    }

    .lk-modal-lancamento .lk-modal-body-modern {
        overflow-y: auto;
        max-height: calc(90vh - 100px);
        min-height: 400px;
        flex: 1;
        padding: 2rem;
        padding-bottom: 3rem;
        scroll-behavior: smooth;
        position: relative;
    }

    /* Conta Info */
    .lk-conta-info {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid #dee2e6;
    }

    .lk-conta-badge {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        font-size: 1.125rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .lk-conta-badge i {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
    }

    .lk-conta-saldo {
        font-size: 0.9375rem;
        color: #6c757d;
    }

    .lk-conta-saldo strong {
        font-size: 1.25rem;
        color: #2ecc71;
        font-weight: 700;
    }

    /* Histórico */
    .lk-historico-section {
        margin-bottom: 1.5rem;
    }

    .lk-section-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #495057;
        margin: 0 0 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .lk-section-title i {
        color: #6c757d;
    }

    .lk-historico-list {
        border-radius: 12px;
        padding: 1rem;
        max-height: 180px;
        overflow-y: auto;
    }

    .lk-historico-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
    }

    .lk-historico-item:last-child {
        margin-bottom: 0;
    }

    .lk-historico-item:hover {
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .lk-historico-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .lk-historico-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }

    .lk-historico-icon.receita {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }

    .lk-historico-icon.despesa {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }

    .lk-historico-icon.transferencia {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        color: #0c5460;
    }

    .lk-historico-desc h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-text);
        margin: 0;
    }

    .lk-historico-desc p {
        font-size: 0.75rem;
        margin: 0;
    }

    .lk-historico-valor {
        font-size: 1rem;
        font-weight: 700;
    }

    .lk-historico-valor.positivo {
        color: #28a745;
    }

    .lk-historico-valor.negativo {
        color: #dc3545;
    }

    .lk-historico-empty {
        text-align: center;
        padding: 2rem 1rem;
        color: #adb5bd;
    }

    .lk-historico-empty i {
        font-size: 3rem;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    .lk-historico-empty p {
        margin: 0;
        font-size: 0.875rem;
    }

    /* Tipo de Lançamento */
    .lk-tipo-section {
        margin-top: 2rem;
    }

    .lk-tipo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .lk-tipo-card {
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 1.5rem 1rem;
        text-align: center;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .lk-tipo-receita {
        border-color: #28a745;
    }

    .lk-tipo-despesa {
        border-color: #dc3545;
    }

    .lk-tipo-transferencia {
        border-color: #17a2b8;
    }

    .lk-tipo-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }

    .lk-tipo-receita .lk-tipo-icon {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #28a745;
    }

    .lk-tipo-despesa .lk-tipo-icon {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #dc3545;
    }

    .lk-tipo-transferencia .lk-tipo-icon {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        color: #17a2b8;
    }

    .lk-tipo-card h4 {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0 0 0.375rem;
    }


    .lk-tipo-card p {
        font-size: 0.8125rem;
        margin: 0 0 1rem;
        color: var(--color-text) !important;
    }



    .lk-tipo-badge {
        display: inline-block;
        padding: 0.375rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .lk-tipo-receita .lk-tipo-badge {
        background: #d4edda;
        color: #155724;
    }

    .lk-tipo-despesa .lk-tipo-badge {
        background: #f8d7da;
        color: #721c24;
    }

    .lk-tipo-transferencia .lk-tipo-badge {
        background: #d1ecf1;
        color: #0c5460;
    }

    /* Scrollbar customizada para histórico */
    .lk-historico-list::-webkit-scrollbar {
        width: 6px;
    }

    .lk-historico-list::-webkit-scrollbar-track {
        background: #e9ecef;
        border-radius: 3px;
    }

    .lk-historico-list::-webkit-scrollbar-thumb {
        background: #adb5bd;
        border-radius: 3px;
    }

    .lk-historico-list::-webkit-scrollbar-thumb:hover {
        background: #6c757d;
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .lk-modal-lancamento {
            max-width: calc(100vw - 16px) !important;
            max-height: calc(100vh - 16px) !important;
            margin: 8px;
            border-radius: 12px;
        }

        .lk-modal-lancamento .lk-modal-body-modern {
            max-height: calc(100vh - 180px);
            min-height: auto;
            padding: 1rem;
        }

        .lk-modal-modern {
            max-width: calc(100vw - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px;
            border-radius: 12px;
        }

        .lk-modal-header-gradient {
            padding: 1rem;
            min-height: auto;
        }

        .lk-modal-icon-wrapper {
            width: 48px;
            height: 48px;
            font-size: 1.25rem;
        }

        .lk-modal-title {
            font-size: 1.25rem;
        }

        .lk-modal-close-btn {
            width: 32px;
            height: 32px;
            font-size: 1.125rem;
        }

        .lk-modal-body-modern {
            padding: 1rem;
        }

        .lk-conta-info {
            flex-direction: column;
            gap: 0.75rem;
            text-align: center;
            padding: 0.875rem;
        }

        .lk-conta-badge {
            font-size: 0.9375rem;
        }

        .lk-conta-badge i {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }

        .lk-conta-saldo {
            font-size: 0.875rem;
        }

        .lk-conta-saldo strong {
            font-size: 1.125rem;
        }

        .lk-historico-section {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .lk-section-title {
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }

        .lk-historico-list {
            max-height: 200px;
            padding: 0.75rem;
        }

        .lk-historico-item {
            padding: 0.75rem;
            gap: 0.75rem;
            flex-direction: column;
            align-items: flex-start;
        }

        .lk-historico-info {
            width: 100%;
        }

        .lk-historico-valor {
            font-size: 0.9375rem;
            align-self: flex-end;
        }

        .lk-tipo-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .lk-tipo-card {
            padding: 1rem;
        }

        .lk-tipo-icon {
            width: 48px;
            height: 48px;
            font-size: 1.25rem;
        }

        .lk-tipo-card h4 {
            font-size: 1.125rem;
        }

        .lk-tipo-card p {
            font-size: 0.8125rem;
        }

        .lk-tipo-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.625rem;
        }

        .lk-form-group {
            margin-bottom: 1rem;
        }

        .lk-form-group label {
            font-size: 0.875rem;
        }

        .lk-input,
        .lk-select,
        .lk-textarea {
            font-size: 0.9375rem;
            padding: 0.625rem 0.875rem;
        }

        .lk-label {
            font-size: 0.875rem;
            margin-bottom: 0.4375rem;
        }

        .lk-input,
        .lk-select,
        .lk-textarea {
            padding: 0.625rem 0.875rem;
            font-size: 0.9375rem;
        }

        .lk-input-money {
            font-size: 0.9375rem;
        }

        .lk-currency-symbol {
            font-size: 0.9375rem;
            left: 0.875rem;
        }

        .lk-input-money .lk-input-with-prefix,
        input.lk-input.lk-input-with-prefix {
            padding-left: 3.25rem !important;
            font-size: 1rem;
        }

        .lk-select {
            padding-right: 2.25rem;
        }

        .lk-select-icon {
            right: 0.875rem;
        }

        .lk-helper-text {
            font-size: 0.75rem;
        }

        .lk-checkbox-label {
            font-size: 0.875rem;
        }

        .lk-btn-voltar {
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .lk-modal-footer {
            padding: 1rem;
            gap: 0.75rem;
            flex-direction: column-reverse;
        }

        .lk-btn-primary,
        .lk-btn-ghost,
        .lk-btn-secondary {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
        }

        .lk-parcelamento-preview {
            font-size: 0.8125rem;
            padding: 0.75rem;
        }
    }

    @media (max-width: 480px) {
        .lk-modal-lancamento {
            max-width: 100vw !important;
            max-height: 100vh !important;
            margin: 0;
            border-radius: 0;
        }

        .lk-modal-lancamento .lk-modal-body-modern {
            max-height: calc(100vh - 160px);
            padding: 0.875rem;
        }

        .lk-modal-modern {
            max-width: 100vw;
            max-height: 100vh;
            margin: 0;
            border-radius: 0;
        }

        .lk-modal-header-gradient {
            padding: 0.875rem;
        }

        .lk-modal-icon-wrapper {
            width: 44px;
            height: 44px;
            font-size: 1.125rem;
        }

        .lk-modal-title {
            font-size: 1.125rem;
        }

        .lk-modal-close-btn {
            width: 30px;
            height: 30px;
            font-size: 1rem;
        }

        .lk-modal-body-modern {
            padding: 0.875rem;
        }

        .lk-conta-info {
            padding: 0.75rem;
        }

        .lk-conta-badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem;
        }

        .lk-conta-badge i {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }

        .lk-conta-saldo {
            font-size: 0.8125rem;
        }

        .lk-conta-saldo strong {
            font-size: 1rem;
        }

        .lk-historico-section,
        .lk-tipo-section,
        .lk-form-section {
            padding: 0.875rem;
            margin-bottom: 0.875rem;
        }

        .lk-section-title {
            font-size: 0.9375rem;
        }

        .lk-historico-list {
            padding: 0.5rem;
        }

        .lk-historico-item {
            padding: 0.625rem;
        }

        .lk-historico-icon {
            width: 32px;
            height: 32px;
            font-size: 0.75rem;
        }

        .lk-historico-desc h5 {
            font-size: 0.8125rem;
        }

        .lk-historico-desc p {
            font-size: 0.6875rem;
        }

        .lk-historico-valor {
            font-size: 0.875rem;
        }

        .lk-tipo-card {
            padding: 0.875rem;
        }

        .lk-tipo-icon {
            width: 44px;
            height: 44px;
            font-size: 1.125rem;
        }

        .lk-tipo-card h4 {
            font-size: 1rem;
        }

        .lk-tipo-card p {
            font-size: 0.75rem;
        }

        .lk-form-group {
            margin-bottom: 0.875rem;
        }

        .lk-label {
            font-size: 0.8125rem;
            margin-bottom: 0.375rem;
        }

        .lk-input,
        .lk-select,
        .lk-textarea {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .lk-input-money {
            font-size: 0.875rem;
        }

        .lk-currency-symbol {
            font-size: 0.875rem;
            left: 0.75rem;
        }

        .lk-input-money .lk-input-with-prefix,
        input.lk-input.lk-input-with-prefix {
            padding-left: 3rem !important;
            font-size: 0.9375rem;
        }

        .lk-select {
            padding-right: 2rem;
        }

        .lk-select-icon {
            right: 0.75rem;
            font-size: 0.8125rem;
        }

        .lk-helper-text {
            font-size: 0.6875rem;
        }

        .lk-checkbox-label {
            font-size: 0.8125rem;
        }

        .lk-checkbox-custom {
            width: 18px;
            height: 18px;
        }

        .lk-btn-voltar {
            padding: 0.5rem 0.875rem;
            font-size: 0.8125rem;
        }

        .lk-modal-footer {
            padding: 0.875rem;
        }

        .lk-btn-primary,
        .lk-btn-ghost,
        .lk-btn-secondary {
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
        }

        .lk-input-group .lk-input-suffix {
            font-size: 0.8125rem;
            padding: 0.5rem 0.75rem;
        }

        .lk-parcelamento-preview {
            font-size: 0.75rem;
            padding: 0.625rem;
        }
    }


    /* ========================================
   HISTÓRICO RECENTE
   ======================================== */
    .lk-historico-section {
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .lk-historico-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: 300px;
        overflow-y: auto;
    }

    .lk-historico-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem;
        border-radius: 8px;
        border-left: 3px solid #dee2e6;
        transition: all 0.2s ease;
    }

    .lk-historico-item:hover {
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .lk-historico-receita {
        border-left-color: #28a745;
    }

    .lk-historico-despesa {
        border-left-color: #dc3545;
    }

    .lk-historico-transferencia {
        border-left-color: #17a2b8;
    }

    .lk-historico-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lk-historico-receita .lk-historico-icon {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .lk-historico-despesa .lk-historico-icon {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .lk-historico-transferencia .lk-historico-icon {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }

    .lk-historico-info {
        flex: 1;
        min-width: 0;
    }

    .lk-historico-desc {
        font-size: 0.9375rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lk-historico-cat {
        font-size: 0.8125rem;
        margin-top: 2px;
    }

    .lk-historico-right {
        text-align: right;
        flex-shrink: 0;
    }

    .lk-historico-valor {
        font-size: 0.9375rem;
        font-weight: 700;
    }

    .lk-historico-receita .lk-historico-valor {
        color: #28a745;
    }

    .lk-historico-despesa .lk-historico-valor {
        color: #dc3545;
    }

    .lk-historico-transferencia .lk-historico-valor {
        color: #17a2b8;
    }

    .lk-historico-data {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 2px;
        text-transform: capitalize;
    }

    .lk-historico-empty {
        padding: 2rem;
        text-align: center;
        color: #6c757d;
    }

    .lk-historico-empty i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        opacity: 0.3;
    }

    .lk-historico-empty p {
        margin: 0;
        font-size: 0.9375rem;
    }

    /* ========================================
   FIM DO HISTÓRICO
   ======================================== */

    /* Checkbox Custom */
    .lk-checkbox-wrapper {
        margin: 0.75rem 0;
    }

    .lk-checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        user-select: none;
    }

    .lk-checkbox {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .lk-checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #ced4da;
        border-radius: 6px;
        transition: all 0.2s ease;
        position: relative;
        background: white;
    }

    .lk-checkbox:checked~.lk-checkbox-custom {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-color: #667eea;
    }

    .lk-checkbox:checked~.lk-checkbox-custom::after {
        content: "✓";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 14px;
        font-weight: bold;
    }

    .lk-checkbox-text {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .lk-checkbox-text i {
        color: #667eea;
    }

    /* Input Group */
    .lk-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .lk-input-group .lk-input {
        padding-right: 70px;
    }

    .lk-input-suffix {
        position: absolute;
        right: 1rem;
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 600;
        pointer-events: none;
    }

    /* Preview de Parcelamento */
    .lk-parcelamento-preview {
        margin-top: 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
        border-radius: 12px;
        border-left: 4px solid #667eea;
        animation: lk-slide-in 0.3s ease-out;
    }

    .lk-parcelamento-preview-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #667eea;
        margin: 0 0 0.5rem;
        letter-spacing: 0.5px;
    }

    .lk-parcelamento-valor {
        font-size: 1.5rem;
        font-weight: 800;
        color: #2c3e50;
        margin: 0.25rem 0;
    }

    .lk-parcelamento-detalhes {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .lk-parcelamento-detalhes strong {
        color: #495057;
    }

    /* Formulário de Lançamento */
    .lk-form-section {
        animation: lk-slide-in 0.3s ease-out;
    }

    .lk-form-group {
        margin-bottom: 1.5rem;
    }

    .lk-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9375rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .lk-label.required::after {
        content: '*';
        color: #dc3545;
        margin-left: 0.25rem;
    }

    .lk-label i {
        color: #667eea;
        font-size: 0.875rem;
    }

    .lk-input,
    .lk-select,
    .lk-textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        color: #2c3e50;
        background: #fff;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .lk-input:focus,
    .lk-select:focus,
    .lk-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .lk-input-money {
        position: relative;
        display: flex;
        align-items: center;
    }

    .lk-currency-symbol {
        position: absolute;
        left: 1rem;
        font-size: 1rem;
        font-weight: 700;
        color: #2ecc71;
        pointer-events: none;
        z-index: 1;
    }

    .lk-input-money .lk-input-with-prefix,
    input.lk-input.lk-input-with-prefix {
        padding-left: 3.5rem !important;
        font-size: 1.125rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .lk-select-wrapper {
        position: relative;
        width: 100%;
    }

    .lk-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 2.5rem !important;
        cursor: pointer;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        line-height: 1.5 !important;
        height: auto !important;
        min-height: 2.75rem;
    }

    .lk-select-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.875rem;
        color: #6c757d;
        pointer-events: none;
    }

    .lk-helper-text {
        display: block;
        margin-top: 0.375rem;
        font-size: 0.8125rem;
        color: #6c757d;
    }

    /* Campos dinâmicos com animação suave */
    #parcelamentoGroup,
    #numeroParcelasGroup,
    #cartaoCreditoGroup,
    #contaDestinoGroup {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out, opacity 0.3s ease-out, margin 0.3s ease-out;
        margin-bottom: 0;
    }

    #parcelamentoGroup[style*="display: block"],
    #numeroParcelasGroup[style*="display: block"],
    #cartaoCreditoGroup[style*="display: block"],
    #contaDestinoGroup[style*="display: block"] {
        opacity: 1;
        max-height: 500px;
        margin-bottom: 1.5rem;
    }

    .lk-btn-voltar {
        background: none;
        border: none;
        color: #6c757d;
        font-size: 0.9375rem;
        font-weight: 600;
        cursor: pointer;
        padding: 0.5rem 0;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .lk-btn-voltar:hover {
        color: #2c3e50;
        gap: 0.75rem;
    }

    .lk-btn-voltar i {
        transition: transform 0.2s ease;
    }

    .lk-btn-voltar:hover i {
        transform: translateX(-2px);
    }

    .lk-textarea {
        resize: vertical;
        font-family: inherit;
        line-height: 1.5;
    }

    @keyframes lk-slide-in {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>