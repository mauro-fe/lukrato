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

            <div class="lk-tipo-grid lk-tipo-grid-4">
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

                <!-- Agendamento -->
                <button type="button" class="lk-tipo-card lk-tipo-agendamento"
                    onclick="contasManager.mostrarFormularioLancamento('agendamento')">
                    <div class="lk-tipo-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h4>Agendamento</h4>
                    <p>Programar para depois</p>
                    <div class="lk-tipo-badge">📅 Agendar</div>
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

                <!-- Forma de Pagamento (para despesas) -->
                <div class="lk-form-group lk-forma-pagamento-section" id="formaPagamentoGroup" style="display: none;">
                    <input type="hidden" id="formaPagamento" name="forma_pagamento" value="">
                    <label class="lk-forma-pagamento-label">
                        <i class="fas fa-wallet"></i>
                        Como você vai pagar?
                    </label>
                    <div class="lk-forma-pagamento-grid" id="formaPagamentoGrid">
                        <button type="button" class="lk-forma-btn" data-forma="pix" onclick="contasManager.selecionarFormaPagamento('pix')">
                            <i class="fa-brands fa-pix lk-forma-icon"></i>
                            <span class="lk-forma-label">PIX</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="cartao_credito" onclick="contasManager.selecionarFormaPagamento('cartao_credito')">
                            <i class="fa-solid fa-credit-card lk-forma-icon"></i>
                            <span class="lk-forma-label">Crédito</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="cartao_debito" onclick="contasManager.selecionarFormaPagamento('cartao_debito')">
                            <i class="fa-solid fa-credit-card lk-forma-icon"></i>
                            <span class="lk-forma-label">Débito</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="dinheiro" onclick="contasManager.selecionarFormaPagamento('dinheiro')">
                            <i class="fa-solid fa-money-bill-wave lk-forma-icon"></i>
                            <span class="lk-forma-label">Dinheiro</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="boleto" onclick="contasManager.selecionarFormaPagamento('boleto')">
                            <i class="fa-solid fa-barcode lk-forma-icon"></i>
                            <span class="lk-forma-label">Boleto</span>
                        </button>
                    </div>
                </div>

                <!-- Forma de Recebimento (para receitas) -->
                <div class="lk-form-group lk-forma-pagamento-section" id="formaRecebimentoGroup" style="display: none;">
                    <input type="hidden" id="formaRecebimento" name="forma_pagamento" value="">
                    <label class="lk-forma-pagamento-label">
                        <i class="fas fa-hand-holding-usd"></i>
                        Como você vai receber?
                    </label>
                    <div class="lk-forma-pagamento-grid" id="formaRecebimentoGrid">
                        <button type="button" class="lk-forma-btn" data-forma="pix" onclick="contasManager.selecionarFormaRecebimento('pix')">
                            <i class="fa-brands fa-pix lk-forma-icon"></i>
                            <span class="lk-forma-label">PIX</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="deposito" onclick="contasManager.selecionarFormaRecebimento('deposito')">
                            <i class="fa-solid fa-building-columns lk-forma-icon"></i>
                            <span class="lk-forma-label">Depósito</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="dinheiro" onclick="contasManager.selecionarFormaRecebimento('dinheiro')">
                            <i class="fa-solid fa-money-bill-wave lk-forma-icon"></i>
                            <span class="lk-forma-label">Dinheiro</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="transferencia" onclick="contasManager.selecionarFormaRecebimento('transferencia')">
                            <i class="fa-solid fa-arrow-right-arrow-left lk-forma-icon"></i>
                            <span class="lk-forma-label">Transf.</span>
                        </button>
                        <button type="button" class="lk-forma-btn" data-forma="estorno_cartao" onclick="contasManager.selecionarFormaRecebimento('estorno_cartao')">
                            <i class="fa-solid fa-rotate-left lk-forma-icon"></i>
                            <span class="lk-forma-label">Estorno</span>
                        </button>
                    </div>
                </div>

                <!-- Seleção de Cartão (quando forma é cartão de crédito) -->
                <div class="lk-form-group lk-forma-cartao-info" id="cartaoCreditoGroup">
                    <label for="lancamentoCartaoCredito" class="lk-label">
                        <i class="fas fa-credit-card"></i>
                        Qual cartão?
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoCartaoCredito" name="cartao_credito_id" class="lk-select" onchange="contasManager.onCartaoChange()">
                            <option value="">Selecione o cartão</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">O débito será na data de vencimento da fatura</small>
                </div>

                <!-- Seleção de Fatura para Estorno -->
                <div class="lk-form-group" id="faturaEstornoGroup" style="display: none;">
                    <label for="lancamentoFaturaEstorno" class="lk-label required">
                        <i class="fas fa-calendar-alt"></i>
                        Em qual fatura aplicar o estorno?
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoFaturaEstorno" name="fatura_mes_ano" class="lk-select">
                            <option value="">Selecione a fatura</option>
                            <!-- Preenchido via JS -->
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">O estorno será creditado na fatura do mês selecionado</small>
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
                        <input type="number" id="lancamentoTotalParcelas" name="total_parcelas" class="lk-input" min="2"
                            max="48" value="2" placeholder="12">
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
                        <span id="labelDataLancamento">Data</span>
                    </label>
                    <input type="date" id="lancamentoData" name="data" class="lk-input" required>
                </div>

                <!-- Tipo de Agendamento (receita/despesa) - oculto por padrão -->
                <div class="lk-form-group" id="tipoAgendamentoGroup" style="display: none;">
                    <label class="lk-label required">
                        <i class="fas fa-tags"></i>
                        Tipo do Lançamento
                    </label>
                    <div class="lk-tipo-agendamento-btns">
                        <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-receita" data-tipo="receita"
                            onclick="contasManager.selecionarTipoAgendamento('receita')">
                            <i class="fas fa-arrow-down"></i> Receita
                        </button>
                        <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-despesa active" data-tipo="despesa"
                            onclick="contasManager.selecionarTipoAgendamento('despesa')">
                            <i class="fas fa-arrow-up"></i> Despesa
                        </button>
                    </div>
                    <input type="hidden" id="lancamentoTipoAgendamento" name="tipo_agendamento" value="despesa">
                </div>

                <!-- Hora (somente para agendamento) -->
                <div class="lk-form-group" id="horaAgendamentoGroup" style="display: none;">
                    <label for="lancamentoHora" class="lk-label">
                        <i class="fas fa-clock"></i>
                        Hora
                    </label>
                    <input type="time" id="lancamentoHora" name="hora" class="lk-input" value="12:00">
                    <small class="lk-helper-text">Horário de execução do agendamento</small>
                </div>

                <!-- Recorrência (somente para agendamento) -->
                <div class="lk-form-group" id="recorrenciaGroup" style="display: none;">
                    <label class="lk-label">
                        <i class="fas fa-sync-alt"></i>
                        Recorrência
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoRecorrencia" name="recorrencia" class="lk-select">
                            <option value="">Não repetir</option>
                            <option value="diario">Diariamente</option>
                            <option value="semanal">Semanalmente</option>
                            <option value="mensal">Mensalmente</option>
                            <option value="anual">Anualmente</option>
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">🔁 Repete para sempre até você cancelar</small>
                </div>

                <!-- Campo oculto para número de repetições (sempre indefinido) -->
                <input type="hidden" id="lancamentoNumeroRepeticoes" name="numero_repeticoes" value="">

                <!-- Tempo de Aviso (somente para agendamento) -->
                <div class="lk-form-group" id="tempoAvisoGroup" style="display: none;">
                    <label for="lancamentoTempoAviso" class="lk-label">
                        <i class="fas fa-bell"></i>
                        Avisar com antecedência
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="lancamentoTempoAviso" name="tempo_aviso" class="lk-select">
                            <option value="0">No momento da execução</option>
                            <option value="5">5 minutos antes</option>
                            <option value="15">15 minutos antes</option>
                            <option value="30">30 minutos antes</option>
                            <option value="60" selected>1 hora antes</option>
                            <option value="120">2 horas antes</option>
                            <option value="360">6 horas antes</option>
                            <option value="720">12 horas antes</option>
                            <option value="1440">1 dia antes</option>
                            <option value="2880">2 dias antes</option>
                            <option value="4320">3 dias antes</option>
                            <option value="10080">1 semana antes</option>
                        </select>
                        <i class="fas fa-chevron-down lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text">Quando você será notificado sobre este agendamento</small>
                </div>

                <!-- Canais de Notificação (somente para agendamento) -->
                <div class="lk-form-group" id="canaisNotificacaoGroup" style="display: none;">
                    <label class="lk-label">
                        <i class="fas fa-envelope"></i>
                        Canais de Notificação
                    </label>
                    <div class="lk-checkbox-wrapper">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="lancamentoCanalInapp" name="canal_inapp" value="1"
                                class="lk-checkbox" checked>
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i class="fas fa-desktop"></i>
                                Aviso no sistema
                            </span>
                        </label>
                    </div>
                    <div class="lk-checkbox-wrapper">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="lancamentoCanalEmail" name="canal_email" value="1"
                                class="lk-checkbox" checked>
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i class="fas fa-envelope"></i>
                                E-mail
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Categoria (não obrigatório) -->
                <div class="lk-form-group" id="categoriaGroup">
                    <label for="lancamentoCategoria" class="lk-label">
                        <i class="fas fa-tag"></i>
                        Categoria
                        <button type="button" class="lk-info"
                            data-lk-tooltip="A categoria ajuda a organizar seus gastos. Escolha a que melhor representa essa despesa."
                            aria-label="Ajuda: Categoria">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        </button>
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
<!-- Estilos movidos para: public/assets/css/modal-lancamento.css -->