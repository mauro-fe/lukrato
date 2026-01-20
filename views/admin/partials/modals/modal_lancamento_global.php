<!-- Modal Global de Lançamento -->
<div class="lk-modal-overlay" id="modalLancamentoGlobalOverlay" onclick="lancamentoGlobalManager.closeModal()">
    <div class="lk-modal-modern lk-modal-lancamento" onclick="event.stopPropagation()" role="dialog"
        aria-labelledby="modalLancamentoGlobalTitulo">
        <!-- Header com Gradiente -->
        <div class="lk-modal-header-gradient">
            <div class="lk-modal-icon-wrapper">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h2 class="lk-modal-title" id="modalLancamentoGlobalTitulo">Nova Movimentação</h2>
            <button class="lk-modal-close-btn" onclick="lancamentoGlobalManager.closeModal()" type="button"
                aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
            <!-- Seleção de Conta -->
            <div class="lk-form-group">
                <label for="globalContaSelect" class="lk-label required">
                    <i class="fas fa-wallet"></i>
                    Selecione a Conta
                </label>
                <div class="lk-select-wrapper">
                    <select id="globalContaSelect" class="lk-select" required
                        onchange="lancamentoGlobalManager.onContaChange()">
                        <option value="">Escolha uma conta...</option>
                        <!-- Preenchido via JS -->
                    </select>
                    <i class="fas fa-chevron-down lk-select-icon"></i>
                </div>
            </div>

            <!-- Saldo da Conta Selecionada -->
            <div class="lk-conta-info" id="globalContaInfo" style="display: none;">
                <div class="lk-conta-badge">
                    <i class="fas fa-wallet"></i>
                    <span id="globalContaNome">Conta</span>
                </div>
                <div class="lk-conta-saldo">
                    Saldo atual: <strong id="globalContaSaldo">R$ 0,00</strong>
                </div>
            </div>

            <!-- Escolha do Tipo de Lançamento -->
            <div class="lk-tipo-section" id="globalTipoSection">
                <h3 class="lk-section-title">
                    <i class="fas fa-tasks"></i>
                    Escolha o tipo de movimentação
                </h3>

                <div class="lk-tipo-grid lk-tipo-grid-4">
                    <!-- Receita -->
                    <button type="button" class="lk-tipo-card lk-tipo-receita"
                        onclick="lancamentoGlobalManager.mostrarFormulario('receita')">
                        <div class="lk-tipo-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <h4>Receita</h4>
                        <p>Dinheiro que entra</p>
                        <div class="lk-tipo-badge">+ Entrada</div>
                    </button>

                    <!-- Despesa -->
                    <button type="button" class="lk-tipo-card lk-tipo-despesa"
                        onclick="lancamentoGlobalManager.mostrarFormulario('despesa')">
                        <div class="lk-tipo-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <h4>Despesa</h4>
                        <p>Dinheiro que sai</p>
                        <div class="lk-tipo-badge">- Saída</div>
                    </button>

                    <!-- Transferência -->
                    <button type="button" class="lk-tipo-card lk-tipo-transferencia"
                        onclick="lancamentoGlobalManager.mostrarFormulario('transferencia')">
                        <div class="lk-tipo-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h4>Transferência</h4>
                        <p>Entre contas</p>
                        <div class="lk-tipo-badge">⇄ Mover</div>
                    </button>

                    <!-- Agendamento -->
                    <!-- <button type="button" class="lk-tipo-card lk-tipo-agendamento"
                        onclick="lancamentoGlobalManager.mostrarFormulario('agendamento')">
                        <div class="lk-tipo-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h4>Agendamento</h4>
                        <p>Programar para depois</p>
                        <div class="lk-tipo-badge">📅 Agendar</div>
                    </button> -->
                </div>
            </div>

            <!-- Formulário de Lançamento (oculto inicialmente) -->
            <div class="lk-form-section" id="globalFormSection" style="display: none;">
                <!-- Botão voltar -->
                <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.voltarEscolhaTipo()">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </button>

                <form id="globalFormLancamento" autocomplete="off">
                    <input type="hidden" id="globalLancamentoContaId" name="conta_id">
                    <input type="hidden" id="globalLancamentoTipo" name="tipo">
                    <input type="hidden" id="globalLancamentoTipoAgendamento" name="tipo_agendamento" value="despesa">

                    Tipo de Agendamento (somente para agendamento)
                    <div class="lk-form-group" id="globalTipoAgendamentoGroup" style="display: none;">
                        <label class="lk-label required">
                            <i class="fas fa-tag"></i>
                            Tipo de Agendamento
                        </label>
                        <div class="lk-tipo-agendamento-btns">
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-despesa active"
                                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('despesa')">
                                <i class="fas fa-arrow-up"></i> Despesa
                            </button>
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-receita"
                                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('receita')">
                                <i class="fas fa-arrow-down"></i> Receita
                            </button>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="lk-form-group">
                        <label for="globalLancamentoDescricao" class="lk-label required">
                            <i class="fas fa-align-left"></i>
                            Descrição
                        </label>
                        <input type="text" id="globalLancamentoDescricao" name="descricao" class="lk-input"
                            placeholder="Ex: Salário, Aluguel, Compras..." required maxlength="200">
                    </div>

                    <!-- Valor -->
                    <div class="lk-form-group">
                        <label for="globalLancamentoValor" class="lk-label required">
                            <i class="fas fa-dollar-sign"></i>
                            Valor
                        </label>
                        <div class="lk-input-money">
                            <span class="lk-currency-symbol">R$</span>
                            <input type="text" id="globalLancamentoValor" name="valor"
                                class="lk-input lk-input-with-prefix" value="0,00" placeholder="0,00" autocomplete="off"
                                required>
                        </div>
                    </div>

                    <!-- Conta Destino (somente para transferência) -->
                    <div class="lk-form-group" id="globalContaDestinoGroup" style="display: none;">
                        <label for="globalLancamentoContaDestino" class="lk-label required">
                            <i class="fas fa-exchange-alt"></i>
                            Conta de Destino
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoContaDestino" name="conta_destino_id" class="lk-select">
                                <option value="">Selecione a conta de destino</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                        <small class="lk-helper-text">Para onde o dinheiro vai ser transferido</small>
                    </div>

                    <!-- Cartão de Crédito (somente para despesa) -->
                    <div class="lk-form-group" id="globalCartaoCreditoGroup" style="display: none;">
                        <label for="globalLancamentoCartaoCredito" class="lk-label">
                            <i class="fas fa-credit-card"></i>
                            Pagar com Cartão de Crédito
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoCartaoCredito" name="cartao_credito_id" class="lk-select">
                                <option value="">Não usar cartão (débito na conta)</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                        <small class="lk-helper-text">Se usar cartão, o débito será na data de vencimento da
                            fatura</small>
                    </div>

                    <!-- Parcelamento (somente se cartão selecionado) -->
                    <div class="lk-form-group" id="globalParcelamentoGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="globalLancamentoParcelado" name="eh_parcelado"
                                    class="lk-checkbox">
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i class="fas fa-calendar-alt"></i>
                                    Parcelar compra
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Número de Parcelas (quando parcelado) -->
                    <div class="lk-form-group" id="globalNumeroParcelasGroup" style="display: none;">
                        <label for="globalLancamentoTotalParcelas" class="lk-label required">
                            <i class="fas fa-list-ol"></i>
                            Número de Parcelas
                        </label>
                        <div class="lk-input-group">
                            <input type="number" id="globalLancamentoTotalParcelas" name="total_parcelas"
                                class="lk-input" min="2" max="48" value="2" placeholder="12">
                            <span class="lk-input-suffix">vezes</span>
                        </div>
                        <div id="globalParcelamentoPreview" class="lk-parcelamento-preview" style="display: none;">
                            <!-- Preview preenchido via JS -->
                        </div>
                    </div>

                    <!-- Data -->
                    <div class="lk-form-group">
                        <label for="globalLancamentoData" class="lk-label required">
                            <i class="fas fa-calendar"></i>
                            Data
                        </label>
                        <input type="date" id="globalLancamentoData" name="data" class="lk-input" required>
                    </div>

                    <!-- Categoria -->
                    <div class="lk-form-group" id="globalCategoriaGroup">
                        <label for="globalLancamentoCategoria" class="lk-label">
                            <i class="fas fa-tag"></i>
                            Categoria
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoCategoria" name="categoria_id" class="lk-select">
                                <option value="">Sem categoria</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i class="fas fa-chevron-down lk-select-icon"></i>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="lk-form-actions">
                        <button type="button" class="lk-btn lk-btn-secondary"
                            onclick="lancamentoGlobalManager.closeModal()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="lk-btn lk-btn-primary" id="globalBtnSalvar">
                            <i class="fas fa-save"></i>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Estilos movidos para: public/assets/css/modal-lancamento.css -->