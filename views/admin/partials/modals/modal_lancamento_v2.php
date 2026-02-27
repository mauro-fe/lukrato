<!-- Modal de Novo Lançamento -->
<div class="lk-modal-overlay" id="modalLancamentoOverlay">
    <div class="lk-modal-modern lk-modal-lancamento" onclick="event.stopPropagation()" role="dialog"
        aria-labelledby="modalLancamentoTitulo">
        <!-- Header com Gradiente -->
        <div class="lk-modal-header-gradient">
            <div class=" lk-modal-icon-wrapper">
                <i data-lucide="arrow-left-right" style="color: white"></i>
            </div>
            <h2 class="lk-modal-title" id="modalLancamentoTitulo">Nova Movimentação</h2>
            <button class="lk-modal-close-btn" onclick="contasManager.closeLancamentoModal()" type="button"
                aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
            <!-- Conta Selecionada -->
            <div class="lk-conta-info">
                <div class="lk-conta-badge">
                    <i data-lucide="wallet"></i>
                    <span id="lancamentoContaNome">Conta</span>
                </div>
                <div class="lk-conta-saldo">
                    Saldo atual: <strong id="lancamentoContaSaldo">R$ 0,00</strong>
                </div>
            </div>

            <!-- Histórico Recente -->
            <div class="lk-historico-section">
                <h3 class="lk-section-title">
                    <i data-lucide="history"></i>
                    Últimas Movimentações
                </h3>
                <div class="lk-historico-list" id="lancamentoHistorico">
                    <!-- Preenchido via JS -->
                    <div class="lk-historico-empty">
                        <i data-lucide="inbox"></i>
                        <p>Nenhuma movimentação recente</p>
                    </div>
                </div>
            </div>

            <!-- Escolha do Tipo de Lançamento -->
            <div class="lk-tipo-section" id="tipoSection">
                <h3 class="lk-section-title">
                    <i data-lucide="list-checks"></i>
                    Escolha o tipo de movimentação
                </h3>

                <div class="lk-tipo-grid lk-tipo-grid-3">
                    <!-- Receita -->
                    <button type="button" class="lk-tipo-card lk-tipo-receita"
                        onclick="contasManager.mostrarFormularioLancamento('receita')">
                        <div class="lk-tipo-icon">
                            <i data-lucide="arrow-down"></i>
                        </div>
                        <h4>Receita</h4>
                        <p>Dinheiro que entra</p>
                        <div class="lk-tipo-badge">+ Entrada</div>
                    </button>

                    <!-- Despesa -->
                    <button type="button" class="lk-tipo-card lk-tipo-despesa"
                        onclick="contasManager.mostrarFormularioLancamento('despesa')">
                        <div class="lk-tipo-icon">
                            <i data-lucide="arrow-up"></i>
                        </div>
                        <h4>Despesa</h4>
                        <p>Dinheiro que sai</p>
                        <div class="lk-tipo-badge">- Saída</div>
                    </button>

                    <!-- Transferência -->
                    <button type="button" class="lk-tipo-card lk-tipo-transferencia"
                        onclick="contasManager.mostrarFormularioLancamento('transferencia')">
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
            <div class="lk-form-section" id="formSection" style="display: none;">
                <!-- Botão voltar -->
                <button type="button" class="lk-btn-voltar" onclick="contasManager.voltarEscolhaTipo()">
                    <i data-lucide="arrow-left"></i>
                    Voltar
                </button>

                <form id="formLancamento" autocomplete="off">
                    <input type="hidden" id="lancamentoContaId" name="conta_id">
                    <input type="hidden" id="lancamentoTipo" name="tipo">

                    <!-- Descrição -->
                    <div class="lk-form-group">
                        <label for="lancamentoDescricao" class="lk-label required">
                            <i data-lucide="align-left"></i>
                            Descrição
                        </label>
                        <input type="text" id="lancamentoDescricao" name="descricao" class="lk-input"
                            placeholder="Ex: Salário, Aluguel, Compras..." required maxlength="200">
                    </div>

                    <!-- Valor -->
                    <div class="lk-form-group">
                        <label for="lancamentoValor" class="lk-label required">
                            <i data-lucide="dollar-sign"></i>
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
                            <i data-lucide="arrow-left-right"></i>
                            Conta de Destino
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="lancamentoContaDestino" name="conta_destino_id" class="lk-select">
                                <option value="">Selecione a conta de destino</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                        <small class="lk-helper-text">Para onde o dinheiro vai ser transferido</small>
                    </div>

                    <!-- Forma de Pagamento (para despesas) -->
                    <div class="lk-form-group lk-forma-pagamento-section" id="formaPagamentoGroup"
                        style="display: none;">
                        <input type="hidden" id="formaPagamento" name="forma_pagamento" value="">
                        <label class="lk-forma-pagamento-label">
                            <i data-lucide="wallet"></i>
                            Como você vai pagar?
                        </label>
                        <div class="lk-forma-pagamento-grid" id="formaPagamentoGrid">
                            <button type="button" class="lk-forma-btn" data-forma="pix"
                                onclick="contasManager.selecionarFormaPagamento('pix')">
                                <i class="fa-brands fa-pix lk-forma-icon"></i>
                                <span class="lk-forma-label">PIX</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="cartao_credito"
                                onclick="contasManager.selecionarFormaPagamento('cartao_credito')">
                                <i data-lucide="credit-card" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Crédito</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="cartao_debito"
                                onclick="contasManager.selecionarFormaPagamento('cartao_debito')">
                                <i data-lucide="credit-card" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Débito</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="dinheiro"
                                onclick="contasManager.selecionarFormaPagamento('dinheiro')">
                                <i data-lucide="banknote" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Dinheiro</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="boleto"
                                onclick="contasManager.selecionarFormaPagamento('boleto')">
                                <i data-lucide="scan-line" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Boleto</span>
                            </button>
                        </div>
                    </div>

                    <!-- Forma de Recebimento (para receitas) -->
                    <div class="lk-form-group lk-forma-pagamento-section" id="formaRecebimentoGroup"
                        style="display: none;">
                        <input type="hidden" id="formaRecebimento" name="forma_pagamento" value="">
                        <label class="lk-forma-pagamento-label">
                            <i data-lucide="hand-coins"></i>
                            Como você vai receber?
                        </label>
                        <div class="lk-forma-pagamento-grid" id="formaRecebimentoGrid">
                            <button type="button" class="lk-forma-btn" data-forma="pix"
                                onclick="contasManager.selecionarFormaRecebimento('pix')">
                                <i class="fa-brands fa-pix lk-forma-icon"></i>
                                <span class="lk-forma-label">PIX</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="deposito"
                                onclick="contasManager.selecionarFormaRecebimento('deposito')">
                                <i data-lucide="landmark" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Depósito</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="dinheiro"
                                onclick="contasManager.selecionarFormaRecebimento('dinheiro')">
                                <i data-lucide="banknote" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Dinheiro</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="transferencia"
                                onclick="contasManager.selecionarFormaRecebimento('transferencia')">
                                <i data-lucide="arrow-left-right" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Transf.</span>
                            </button>
                            <button type="button" class="lk-forma-btn" data-forma="estorno_cartao"
                                onclick="contasManager.selecionarFormaRecebimento('estorno_cartao')">
                                <i data-lucide="rotate-ccw" class="lk-forma-icon"></i>
                                <span class="lk-forma-label">Estorno</span>
                            </button>
                        </div>
                    </div>

                    <!-- Seleção de Cartão (quando forma é cartão de crédito) -->
                    <div class="lk-form-group lk-forma-cartao-info" id="cartaoCreditoGroup">
                        <label for="lancamentoCartaoCredito" class="lk-label">
                            <i data-lucide="credit-card"></i>
                            Qual cartão?
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="lancamentoCartaoCredito" name="cartao_credito_id" class="lk-select"
                                onchange="contasManager.onCartaoChange()">
                                <option value="">Selecione o cartão</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                        <small class="lk-helper-text">O débito será na data de vencimento da fatura</small>
                    </div>

                    <!-- Seleção de Fatura para Estorno -->
                    <div class="lk-form-group" id="faturaEstornoGroup" style="display: none;">
                        <label for="lancamentoFaturaEstorno" class="lk-label required">
                            <i data-lucide="calendar-days"></i>
                            Em qual fatura aplicar o estorno?
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="lancamentoFaturaEstorno" name="fatura_mes_ano" class="lk-select">
                                <option value="">Selecione a fatura</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
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
                                    <i data-lucide="calendar-days"></i>
                                    Parcelar compra
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text">O valor total será dividido entre as próximas faturas.</small>
                    </div>

                    <!-- Assinatura / Recorrência no Cartão (somente se cartão selecionado) -->
                    <div class="lk-form-group" id="assinaturaCartaoGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="lancamentoAssinaturaCartao" name="recorrente_cartao"
                                    value="1" class="lk-checkbox" onchange="contasManager.toggleAssinaturaCartao()">
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

                        <div id="assinaturaCartaoDetalhes" style="display: none;">
                            <label class="lk-label">
                                <i data-lucide="refresh-cw"></i>
                                Frequência da cobrança
                            </label>
                            <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                                <select id="lancamentoAssinaturaFreq" name="recorrencia_freq_cartao" class="lk-select">
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
                                    <input type="radio" name="assinatura_modo" value="infinito" class="lk-radio" checked
                                        onchange="contasManager.toggleAssinaturaCartaoFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Sem data de fim <small style="opacity:0.7">(cancelo
                                            quando quiser)</small></span>
                                </label>
                                <label class="lk-radio-label">
                                    <input type="radio" name="assinatura_modo" value="data" class="lk-radio"
                                        onchange="contasManager.toggleAssinaturaCartaoFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Até uma data específica</span>
                                </label>
                            </div>

                            <div id="assinaturaCartaoFimGroup" style="display: none;">
                                <input type="date" id="lancamentoAssinaturaFim" name="recorrencia_fim_cartao"
                                    class="lk-input">
                                <small class="lk-helper-text">Data em que a assinatura termina.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Número de Parcelas (quando parcelado) -->
                    <div class="lk-form-group" id="numeroParcelasGroup" style="display: none;">
                        <label for="lancamentoTotalParcelas" class="lk-label required">
                            <i data-lucide="list-ordered"></i>
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
                            <i data-lucide="calendar"></i>
                            <span id="labelDataLancamento">Data</span>
                        </label>
                        <input type="date" id="lancamentoData" name="data" class="lk-input" required>
                    </div>

                    <!-- Tipo de Agendamento (receita/despesa) - oculto por padrão -->
                    <div class="lk-form-group" id="tipoAgendamentoGroup" style="display: none;">
                        <label class="lk-label required">
                            <i data-lucide="tags"></i>
                            Tipo do Lançamento
                        </label>
                        <div class="lk-tipo-agendamento-btns">
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-receita" data-tipo="receita"
                                onclick="contasManager.selecionarTipoAgendamento('receita')">
                                <i data-lucide="arrow-down"></i> Receita
                            </button>
                            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-despesa active" data-tipo="despesa"
                                onclick="contasManager.selecionarTipoAgendamento('despesa')">
                                <i data-lucide="arrow-up"></i> Despesa
                            </button>
                        </div>
                        <input type="hidden" id="lancamentoTipoAgendamento" name="tipo_agendamento" value="despesa">
                    </div>

                    <!-- Recorrência (para receita e despesa) -->
                    <div class="lk-form-group" id="recorrenciaGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="lancamentoRecorrente" name="recorrente" value="1"
                                    class="lk-checkbox" onchange="contasManager.toggleRecorrencia()">
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="refresh-cw"></i>
                                    Repetir este lançamento
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text" style="margin-top: -0.25rem; margin-bottom: 0.5rem;">Cria
                            automaticamente este lançamento nos próximos períodos.</small>

                        <div id="recorrenciaDetalhes" style="display: none;">
                            <label class="lk-label">
                                <i data-lucide="refresh-cw"></i>
                                Frequência
                            </label>
                            <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                                <select id="lancamentoRecorrenciaFreq" name="recorrencia_freq" class="lk-select">
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
                                <label class="lk-radio-label" id="recorrenciaRadioInfinito">
                                    <input type="radio" name="recorrencia_modo" value="infinito" class="lk-radio"
                                        checked onchange="contasManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Sem fim <small style="opacity:0.7">(ex: Spotify,
                                            Netflix)</small></span>
                                </label>
                                <label class="lk-radio-label">
                                    <input type="radio" name="recorrencia_modo" value="quantidade" class="lk-radio"
                                        onchange="contasManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Após um número de vezes</span>
                                </label>
                                <label class="lk-radio-label">
                                    <input type="radio" name="recorrencia_modo" value="data" class="lk-radio"
                                        onchange="contasManager.toggleRecorrenciaFim()">
                                    <span class="lk-radio-custom"></span>
                                    <span class="lk-radio-text">Até uma data específica</span>
                                </label>
                            </div>

                            <div id="recorrenciaTotalGroup" style="display: none;">
                                <div class="lk-input-group">
                                    <input type="number" id="lancamentoRecorrenciaTotal" name="recorrencia_total"
                                        class="lk-input" min="2" max="120" value="12" placeholder="12">
                                    <span class="lk-input-suffix">vezes</span>
                                </div>
                                <small class="lk-helper-text">Total de repetições incluindo a primeira.</small>
                            </div>

                            <div id="recorrenciaFimGroup" style="display: none;">
                                <input type="date" id="lancamentoRecorrenciaFim" name="recorrencia_fim"
                                    class="lk-input">
                                <small class="lk-helper-text">Data em que a repetição termina.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Lembrete (para receita e despesa) -->
                    <div class="lk-form-group" id="lembreteGroup" style="display: none;">
                        <label for="lancamentoTempoAviso" class="lk-label">
                            <i data-lucide="bell"></i>
                            Lembrete
                            <span class="lk-optional-badge">opcional</span>
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="lancamentoTempoAviso" name="lembrar_antes_segundos" class="lk-select">
                                <option value="">Sem lembrete</option>
                                <option value="86400">1 dia antes</option>
                                <option value="172800">2 dias antes</option>
                                <option value="259200">3 dias antes</option>
                                <option value="604800">1 semana antes</option>
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>

                        <div id="canaisNotificacaoInline" style="display: none; margin-top: 0.5rem;">
                            <div class="lk-checkbox-wrapper">
                                <label class="lk-checkbox-label">
                                    <input type="checkbox" id="lancamentoCanalInapp" name="canal_inapp" value="1"
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
                                    <input type="checkbox" id="lancamentoCanalEmail" name="canal_email" value="1"
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
                    <div class="lk-form-group" id="pagoGroup" style="display: none;">
                        <div class="lk-checkbox-wrapper">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="lancamentoPago" name="pago" value="1" class="lk-checkbox"
                                    checked>
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="circle-check"></i>
                                    <span id="pagoLabel">Já foi pago</span>
                                </span>
                            </label>
                        </div>
                        <small class="lk-helper-text" id="pagoHelperText">Pendentes não alteram o saldo até serem
                            confirmados.</small>
                    </div>

                    <!-- Categoria (não obrigatório) -->
                    <div class="lk-form-group" id="categoriaGroup">
                        <label for="lancamentoCategoria" class="lk-label">
                            <i data-lucide="tag"></i>
                            Categoria
                            <button type="button" class="lk-info" data-lk-tooltip-title="Categoria"
                                data-lk-tooltip="Ajuda a organizar e visualizar para onde vai seu dinheiro nos relatórios."
                                aria-label="Ajuda: Categoria">
                                <i data-lucide="info" aria-hidden="true"></i>
                            </button>
                        </label>
                        <div class="lk-select-wrapper">
                            <select id="lancamentoCategoria" name="categoria_id" class="lk-select">
                                <option value="">Selecione (opcional)</option>
                                <!-- Preenchido via JS -->
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="lk-modal-footer">
                        <button type="button" class="lk-btn lk-btn-ghost"
                            onclick="contasManager.closeLancamentoModal()">
                            <i data-lucide="x"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="lk-btn lk-btn-primary" id="btnSalvarLancamento">
                            <i data-lucide="check"></i>
                            Salvar Lançamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Estilos movidos para: public/assets/css/modal-lancamento.css -->