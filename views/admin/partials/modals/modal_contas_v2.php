<!-- Modal de Conta -->
<div class="lk-modal-overlay" id="modalContaOverlay" onclick="contasManager.closeModal()">
    <div class="lk-modal-modern" id="modalConta" onclick="event.stopPropagation()" role="dialog" aria-labelledby="modalContaTitulo">
        <!-- Header com Gradiente Lukrato -->
        <div class="lk-modal-header-gradient">
            <div class="lk-modal-icon-wrapper">
                <i class="fas fa-university"></i>
            </div>
            <h2 class="lk-modal-title" id="modalContaTitulo">Nova Conta</h2>
            <button class="lk-modal-close-btn" onclick="contasManager.closeModal()" type="button" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
            <form id="formConta" autocomplete="off">
                <input type="hidden" id="contaId" name="conta_id">

                <!-- Nome da Conta com ícone -->
                <div class="lk-form-group">
                    <label for="nomeConta" class="lk-label required">
                        <i class="fas fa-tag"></i>
                        Nome da Conta
                    </label>
                    <input type="text" 
                           id="nomeConta" 
                           name="nome" 
                           class="lk-input" 
                           placeholder="Ex: Nubank Conta, Itaú Poupança" 
                           required 
                           maxlength="100">
                </div>

                <!-- Instituição Financeira com preview -->
                <div class="lk-form-group">
                    <label for="instituicaoFinanceiraSelect" class="lk-label">
                        <i class="fas fa-building"></i>
                        Instituição Financeira
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="instituicaoFinanceiraSelect" name="instituicao_financeira_id" class="lk-select">
                            <option value="">Selecione uma instituição</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">Escolha o banco ou fintech desta conta</small>
                </div>

                <!-- Grid de 2 colunas -->
                <div class="lk-form-row">
                    <!-- Tipo de Conta -->
                    <div class="lk-form-group">
                        <label for="tipoContaSelect" class="lk-label required">
                            <i class="fas fa-wallet"></i>
                            Tipo de Conta
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="tipoContaSelect" name="tipo_conta" class="lk-select" required>
                                <option value="conta_corrente">💳 Conta Corrente</option>
                                <option value="conta_poupanca">🐷 Conta Poupança</option>
                                <option value="conta_investimento">📈 Investimento</option>
                                <option value="carteira_digital">📱 Carteira Digital</option>
                                <option value="dinheiro">💰 Dinheiro</option>
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                    </div>

                    <!-- Moeda -->
                    <div class="lk-form-group">
                        <label for="moedaSelect" class="lk-label required">
                            <i class="fas fa-dollar-sign"></i>
                            Moeda
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="moedaSelect" name="moeda" class="lk-select" required>
                                <option value="BRL" selected>🇧🇷 Real (BRL)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                                <option value="EUR">🇪🇺 Euro (EUR)</option>
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Saldo Inicial com visual premium -->
                <div class="lk-form-group">
                    <label for="saldoInicial" class="lk-label">
                        <i class="fas fa-coins"></i>
                        Saldo Inicial
                    </label>
                    <div class="lk-input-money">
                        <span class="lk-currency-symbol">R$</span>
                        <input type="text" 
                               id="saldoInicial" 
                               name="saldo_inicial" 
                               class="lk-input lk-input-with-prefix" 
                               value="0,00"
                               placeholder="0,00"
                               autocomplete="off">
                    </div>
                    <small class="lk-helper-text">💡 Digite o saldo atual da conta (positivo ou negativo)</small>
                </div>

                <!-- Footer com botões modernos -->
                <div class="lk-modal-footer">
                    <button type="button" class="lk-btn lk-btn-ghost" onclick="contasManager.closeModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="lk-btn lk-btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="lk-confirm-overlay" id="confirmDeleteOverlay" style="display: none;">
    <div class="lk-confirm-modal" onclick="event.stopPropagation()">
        <div class="lk-confirm-icon-wrapper">
            <div class="lk-confirm-icon-circle">
                <i class="fas fa-trash-alt"></i>
            </div>
        </div>
        
        <h3 class="lk-confirm-title">Excluir Conta?</h3>
        <p class="lk-confirm-message" id="confirmDeleteMessage">
            Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.
        </p>
        
        <div class="lk-confirm-buttons">
            <button type="button" class="lk-btn-cancel" id="btnCancelDelete">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button type="button" class="lk-btn-delete" id="btnConfirmDelete">
                <i class="fas fa-trash"></i>
                Excluir Conta
            </button>
        </div>
    </div>
</div>

<style>
/* Modal de Confirmação */
.lk-confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: lk-fade-in 0.2s ease-out;
}

.lk-confirm-modal {
    background: white;
    border-radius: 20px;
    padding: 40px 30px 30px;
    max-width: 420px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: lk-scale-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    text-align: center;
}

.lk-confirm-icon-wrapper {
    margin-bottom: 20px;
}

.lk-confirm-icon-circle {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: lk-pulse 0.5s ease-out;
}

.lk-confirm-icon-circle i {
    font-size: 32px;
    color: white;
}

.lk-confirm-title {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 12px;
}

.lk-confirm-message {
    font-size: 15px;
    color: #7f8c8d;
    line-height: 1.6;
    margin: 0 0 30px;
}

.lk-confirm-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.lk-btn-cancel,
.lk-btn-delete {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.lk-btn-cancel {
    background: #ecf0f1;
    color: #7f8c8d;
}

.lk-btn-cancel:hover {
    background: #dfe6e9;
    transform: translateY(-1px);
}

.lk-btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.lk-btn-delete:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.lk-btn-delete:active {
    transform: scale(0.98);
}

@keyframes lk-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes lk-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes lk-scale-in {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@media (max-width: 480px) {
    .lk-confirm-buttons {
        flex-direction: column;
    }
}
</style>
