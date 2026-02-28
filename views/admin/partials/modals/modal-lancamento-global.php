<!-- Modal Global de Lançamento -->
<div class="lk-modal-overlay" id="modalLancamentoGlobalOverlay">
    <div class="lk-modal-modern lk-modal-lancamento" onclick="event.stopPropagation()" role="dialog"
        aria-labelledby="modalLancamentoGlobalTitulo">
        <!-- Header com Gradiente -->
        <div class="lk-modal-header-gradient">
            <div class="lk-modal-icon-wrapper">
                <i data-lucide="arrow-left-right" style="color: white"></i>
            </div>
            <h2 class="lk-modal-title" id="modalLancamentoGlobalTitulo">Nova Movimentação</h2>
            <button class="lk-modal-close-btn" onclick="lancamentoGlobalManager.closeModal()" type="button"
                aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
            <!-- Seleção de Conta -->
            <div class="lk-form-group">
                <label for="globalContaSelect" class="lk-label required">
                    <i data-lucide="wallet"></i>
                    Selecione a Conta
                </label>
                <div class="lk-select-wrapper">
                    <select id="globalContaSelect" class="lk-select" required
                        onchange="lancamentoGlobalManager.onContaChange()">
                        <option value="">Escolha uma conta...</option>
                        <!-- Preenchido via JS -->
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-icon"></i>
                </div>
            </div>

            <!-- Saldo da Conta Selecionada -->
            <div class="lk-conta-info" id="globalContaInfo" style="display: none;">
                <div class="lk-conta-badge">
                    <i data-lucide="wallet"></i>
                    <span id="globalContaNome">Conta</span>
                </div>
                <div class="lk-conta-saldo">
                    Saldo atual: <strong id="globalContaSaldo">R$ 0,00</strong>
                </div>
            </div>

            <!-- Escolha do Tipo de Lançamento -->
            <div class="lk-tipo-section" id="globalTipoSection">
                <h3 class="lk-section-title">
                    <i data-lucide="list-checks"></i>
                    Escolha o tipo de movimentação
                </h3>

                <div class="lk-tipo-grid lk-tipo-grid-3">
                    <!-- Receita -->
                    <button type="button" class="lk-tipo-card lk-tipo-receita"
                        onclick="lancamentoGlobalManager.mostrarFormulario('receita')">
                        <div class="lk-tipo-icon">
                            <i data-lucide="arrow-down"></i>
                        </div>
                        <h4>Receita</h4>
                        <p>Dinheiro que entra</p>
                        <div class="lk-tipo-badge">+ Entrada</div>
                    </button>

                    <!-- Despesa -->
                    <button type="button" class="lk-tipo-card lk-tipo-despesa"
                        onclick="lancamentoGlobalManager.mostrarFormulario('despesa')">
                        <div class="lk-tipo-icon">
                            <i data-lucide="arrow-up"></i>
                        </div>
                        <h4>Despesa</h4>
                        <p>Dinheiro que sai</p>
                        <div class="lk-tipo-badge">- Saída</div>
                    </button>

                    <!-- Transferência -->
                    <button type="button" class="lk-tipo-card lk-tipo-transferencia"
                        onclick="lancamentoGlobalManager.mostrarFormulario('transferencia')">
                        <div class="lk-tipo-icon">
                            <i data-lucide="arrow-left-right"></i>
                        </div>
                        <h4>Transferência</h4>
                        <p>Entre contas</p>
                        <div class="lk-tipo-badge">⇄ Mover</div>
                    </button>
                </div>
            </div>

            <!-- Formulário de Lançamento (oculto inicialmente) -->
            <div class="lk-form-section" id="globalFormSection" style="display: none;">
                <!-- Botão voltar -->
                <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.voltarEscolhaTipo()">
                    <i data-lucide="arrow-left"></i>
                    Voltar
                </button>

                <form id="globalFormLancamento" autocomplete="off">
                    <input type="hidden" id="globalLancamentoContaId" name="conta_id">
                    <input type="hidden" id="globalLancamentoTipo" name="tipo">
                    <input type="hidden" id="globalLancamentoTipoAgendamento" name="tipo_agendamento" value="despesa">

                    <!-- Tipo de Agendamento (somente para agendamento) - LEGACY hidden -->
                    <div class="lk-form-group" id="globalTipoAgendamentoGroup" style="display: none;">
                        <label class="lk-label required">
                            <i data-lucide="tag"></i>
                            Tipo de Agendamento
                        </label>
                        <div class="lk-tipo-agendamento-btns">
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-receita"
                                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('receita')">
                                <i data-lucide="arrow-down"></i> Receita
                            </button>
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-despesa active"
                                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('despesa')">
                                <i data-lucide="arrow-up"></i> Despesa
                            </button>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="lk-form-group">
                        <label for="globalLancamentoDescricao" class="lk-label required">
                            <i data-lucide="align-left"></i>
                            Descrição
                        </label>
                        <input type="text" id="globalLancamentoDescricao" name="descricao" class="lk-input"
                            placeholder="Ex: Salário, Aluguel, Compras..." required maxlength="200">
                    </div>

                    <!-- Valor -->
                    <div class="lk-form-group">
                        <label for="globalLancamentoValor" class="lk-label required">
                            <i data-lucide="dollar-sign"></i>
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
                            <i data-lucide="arrow-left-right"></i>
                            Conta de Destino
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoContaDestino" name="conta_destino_id" class="lk-select">
                                <option value="">Selecione a conta de destino</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                        <small class="lk-helper-text">Para onde o dinheiro vai ser transferido</small>
                    </div>


                    <!-- Forma de Pagamento (para despesas) -->
                    <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaPagamentoGroup"
                        style="display: none;">
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
                    <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaRecebimentoGroup"
                        style="display: none;">
                        <input type="hidden" id="globalFormaRecebimento" name="forma_pagamento" value="">
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
                                onchange="typeof lancamentoGlobalManager !== 'undefined' && lancamentoGlobalManager.onCartaoEstornoChange && lancamentoGlobalManager.onCartaoEstornoChange()">
                                <option value="">Selecione o cartão</option>
                                <!-- Preenchido via JS -->
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
                            <select id="globalLancamentoFaturaEstorno" name="fatura_mes_ano" class="lk-select">
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
                                <input type="checkbox" id="globalLancamentoParcelado" name="eh_parcelado"
                                    class="lk-checkbox">
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="calendar-days"></i>
                                    Parcelar compra
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text">O valor total será dividido entre as próximas faturas.</small>
                    </div>

                    <!-- Número de Parcelas (quando parcelado) -->
                    <div class="lk-form-group" id="globalNumeroParcelasGroup" style="display: none;">
                        <label for="globalLancamentoTotalParcelas" class="lk-label required">
                            <i data-lucide="list-ordered"></i>
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

                    <!-- Data e Hora -->
                    <div class="lk-form-group">
                        <label class="lk-label required">
                            <i data-lucide="calendar-clock"></i>
                            Quando?
                        </label>
                        <div class="lk-datetime-inline">
                            <div class="lk-datetime-date">
                                <i data-lucide="calendar" class="lk-datetime-icon"></i>
                                <input type="date" id="globalLancamentoData" name="data" class="lk-input lk-input-date" required>
                            </div>
                            <div class="lk-datetime-sep">
                                <span>às</span>
                            </div>
                            <div class="lk-datetime-time">
                                <i data-lucide="clock" class="lk-datetime-icon"></i>
                                <input type="time" id="globalLancamentoHora" name="hora_lancamento" class="lk-input lk-input-time" placeholder="--:--">
                            </div>
                        </div>
                        <small class="lk-helper-text">Horário é opcional — útil para organizar e lembretes.</small>
                    </div>

                    <!-- Hora (removido - agendamentos foram unificados nos lançamentos) -->

                    <!-- Categoria -->
                    <div class="lk-form-group" id="globalCategoriaGroup">
                        <label for="globalLancamentoCategoria" class="lk-label">
                            <i data-lucide="tag"></i>
                            Categoria
                            <button type="button" class="lk-info" data-lk-tooltip-title="Categoria"
                                data-lk-tooltip="Ajuda a organizar e visualizar para onde vai seu dinheiro nos relatórios."
                                aria-label="Ajuda: Categoria">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoCategoria" name="categoria_id" class="lk-select">
                                <option value="">Sem categoria</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                    </div>

                    <!-- Recorrência (para receita e despesa) -->
                    <div class="lk-form-group" id="globalRecorrenciaGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="globalLancamentoRecorrente" name="recorrente" value="1"
                                    class="lk-checkbox" onchange="lancamentoGlobalManager.toggleRecorrencia()">
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="refresh-cw"></i>
                                    Repetir este lançamento
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text" style="margin-top: -0.25rem; margin-bottom: 0.5rem;">Cria automaticamente este lançamento nos próximos períodos.</small>

                        <div id="globalRecorrenciaDetalhes" style="display: none;">
                            <label class="lk-label">
                                <i data-lucide="refresh-cw"></i>
                                Frequência
                            </label>
                            <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                                <select id="globalLancamentoRecorrenciaFreq" name="recorrencia_freq" class="lk-select">
                                    <option value="semanal">Semanalmente</option>
                                    <option value="quinzenal">Quinzenalmente</option>
                                    <option value="mensal" selected>Mensalmente</option>
                                    <option value="bimestral">Bimestralmente</option>
                                    <option value="trimestral">Trimestralmente</option>
                                    <option value="semestral">Semestralmente</option>
                                    <option value="anual">Anualmente</option>
                                </select>
                                <i data-lucide="chevron-down" class="lk-select-icon"></i>
                            </div>

                            <label class="lk-label">
                                <i data-lucide="flag"></i>
                                Quando termina?
                            </label>
                            <div class="lk-radio-group" style="margin-bottom: 0.5rem;">
                                <label class="lk-radio-label" id="globalRecorrenciaRadioInfinito">
                                    <input type="radio" name="global_recorrencia_modo" value="infinito" class="lk-radio" checked
                                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Sem fim <small style="opacity:0.7">(ex: Spotify, Netflix)</small></span>
                                </label>
                                <label class="lk-radio-label">
                                    <input type="radio" name="global_recorrencia_modo" value="quantidade" class="lk-radio"
                                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Após um número de vezes</span>
                                </label>
                                <label class="lk-radio-label">
                                    <input type="radio" name="global_recorrencia_modo" value="data" class="lk-radio"
                                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Até uma data específica</span>
                                </label>
                            </div>

                            <div id="globalRecorrenciaTotalGroup" style="display: none;">
                                <div class="lk-input-group">
                                    <input type="number" id="globalLancamentoRecorrenciaTotal" name="recorrencia_total"
                                        class="lk-input" min="2" max="120" value="12" placeholder="12">
                                    <span class="lk-input-suffix">vezes</span>
                                </div>
                                <small class="lk-helper-text">Total de repetições incluindo a primeira.</small>
                            </div>

                            <div id="globalRecorrenciaFimGroup" style="display: none;">
                                <input type="date" id="globalLancamentoRecorrenciaFim" name="recorrencia_fim" class="lk-input">
                                <small class="lk-helper-text">Data em que a repetição termina.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Lembrete (para receita e despesa) -->
                    <div class="lk-form-group" id="globalLembreteGroup" style="display: none;">
                        <label for="globalLancamentoTempoAviso" class="lk-label">
                            <i data-lucide="bell"></i>
                            Lembrete
                            <span class="lk-optional-badge">opcional</span>
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="globalLancamentoTempoAviso" name="lembrar_antes_segundos" class="lk-select">
                                <option value="">Sem lembrete</option>
                                <option value="86400">1 dia antes</option>
                                <option value="172800">2 dias antes</option>
                                <option value="259200">3 dias antes</option>
                                <option value="604800">1 semana antes</option>
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>

                        <div id="globalCanaisNotificacaoInline" style="display: none; margin-top: 0.5rem;">
                            <div class="lk-checkbox-wrapper">
                                <label class="lk-checkbox-label">
                                    <input type="checkbox" id="globalCanalInapp" name="canal_inapp" value="1"
                                        class="lk-checkbox" checked>
                                    <span class="lk-checkbox-custom"></span>
                                    <span class="lk-checkbox-text">
                                        <i data-lucide="monitor"></i>
                                        Aviso no sistema
                                    </span>
                                </label>
                            </div>
                            <div class="lk-checkbox-wrapper">
                                <label class="lk-checkbox-label">
                                    <input type="checkbox" id="globalCanalEmail" name="canal_email" value="1"
                                        class="lk-checkbox" checked>
                                    <span class="lk-checkbox-custom"></span>
                                    <span class="lk-checkbox-text">
                                        <i data-lucide="mail"></i>
                                        E-mail
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Status de Pagamento -->
                    <div class="lk-form-group" id="globalPagoGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="globalLancamentoPago" name="pago" value="1"
                                    class="lk-checkbox" checked>
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="circle-check"></i>
                                    <span id="globalPagoLabel">Já foi pago</span>
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text" id="globalPagoHelperText">Pendentes não alteram o saldo até serem confirmados.</small>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="lk-form-actions">
                        <button type="button" class="lk-btn lk-btn-secondary"
                            onclick="lancamentoGlobalManager.closeModal()">
                            <i data-lucide="x"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="lk-btn lk-btn-primary" id="globalBtnSalvar">
                            <i data-lucide="save"></i>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Estilos movidos para: public/assets/css/modal-lancamento.css -->