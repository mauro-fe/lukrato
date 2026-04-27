<!-- ====== PAGE MODE: formulário rápido ====== -->
<div class="lk-wizard-step lk-page-quick-step" data-step="2" id="globalStep2">
    <div class="lk-page-quick-head">
        <button type="button" class="lk-btn-voltar lk-page-quick-back"
            onclick="lancamentoGlobalManager.voltarEscolhaTipo()">
            <i data-lucide="arrow-left"></i>
            Trocar tipo
        </button>
        <div class="lk-page-quick-title">
            <h3 id="modalLancamentoGlobalTituloInline">Nova transação</h3>
        </div>
    </div>

    <div class="lk-page-quick-card surface-card">
        <?php include __DIR__ . '/account-context.php'; ?>

        <div class="lk-page-quick-grid">
            <div class="lk-form-group lk-page-step-panel lk-page-step-panel--value">
                <label for="globalLancamentoValor" class="lk-label required">
                    <i data-lucide="dollar-sign"></i>
                    Valor
                </label>
                <div class="lk-input-money">
                    <span class="lk-currency-symbol">R$</span>
                    <input type="text" id="globalLancamentoValor" name="valor" class="lk-input lk-input-with-prefix"
                        value="0,00" placeholder="0,00" autocomplete="off" required>
                </div>
            </div>

            <div class="lk-form-group lk-page-step-panel lk-page-step-panel--description">
                <label for="globalLancamentoDescricao" class="lk-label">
                    <i data-lucide="align-left"></i>
                    Descrição
                    <span class="lk-optional-badge">opcional</span>
                </label>
                <input type="text" id="globalLancamentoDescricao" name="descricao" class="lk-input"
                    placeholder="Ex: Almoço, Uber, Mercado..." maxlength="190">
            </div>

            <div class="lk-form-group lk-page-step-panel lk-page-step-panel--date">
                <label for="globalLancamentoData" class="lk-label required">
                    <i data-lucide="calendar"></i>
                    Data
                </label>
                <input type="date" id="globalLancamentoData" name="data" class="lk-input lk-input-date" required>
                <input type="hidden" id="globalLancamentoHora" name="hora_lancamento" value="">
            </div>

            <div class="lk-form-group lk-page-step-panel lk-page-step-panel--destination" id="globalContaDestinoGroup"
                style="display: none;">
                <label for="globalLancamentoContaDestino" class="lk-label required">
                    <i data-lucide="arrow-left-right"></i>
                    Conta de destino
                </label>
                <div class="lk-select-wrapper">
                    <select id="globalLancamentoContaDestino" name="conta_destino_id" class="lk-select"
                        data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                        data-lk-select-search-placeholder="Buscar conta de destino...">
                        <option value="">Selecione a conta de destino</option>
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-icon"></i>
                </div>
            </div>
        </div>

        <div class="lk-page-quick-actions">
            <button type="button" class="lk-btn-skip lk-page-more-options-btn" id="globalQuickMoreOptionsBtn"
                onclick="lancamentoGlobalManager.toggleQuickOptions()" aria-expanded="false"
                aria-controls="globalQuickOptions">
                <i data-lucide="sliders-horizontal"></i>
                Mais opções
            </button>

            <button type="submit" class="lk-btn lk-btn-primary lk-page-submit-btn" id="globalBtnSalvar">
                <i data-lucide="check"></i>
                <span class="lk-page-submit-label">Salvar transação</span>
            </button>
        </div>

        <div class="lk-page-quick-options" id="globalQuickOptions" hidden>
            <div class="lk-page-quick-options-head">
                <span>Ajustes</span>
                <small>Forma, categoria, meta e repetição quando precisar.</small>
            </div>

            <div class="lk-page-quick-options-grid">
                <div class="lk-form-group" id="globalPagoGroup" style="display: none;">
                    <div class="lk-checkbox-wrapper">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="globalLancamentoPago" name="pago" value="1" class="lk-checkbox"
                                checked>
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i data-lucide="circle-check"></i>
                                <span id="globalPagoLabel">Já foi pago</span>
                            </span>
                        </label>
                    </div>
                    <small class="lk-helper-text" id="globalPagoHelperText">Pendentes não alteram o saldo até serem
                        confirmados.</small>
                </div>

                <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaPagamentoGroup"
                    style="display: none;">
                    <label for="globalFormaPagamento" class="lk-forma-pagamento-label">
                        <i data-lucide="wallet"></i>
                        Forma de pagamento
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalFormaPagamento" name="forma_pagamento" class="lk-select"
                            data-lk-custom-select="modal" onchange="lancamentoGlobalManager.selecionarFormaPagamento(this.value)">
                            <option value="">Não informar</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_credito">Cartão de crédito</option>
                            <option value="cartao_debito">Cartão de débito</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="boleto">Boleto</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                </div>

                <div class="lk-form-group lk-forma-pagamento-section" id="globalFormaRecebimentoGroup"
                    style="display: none;">
                    <label for="globalFormaRecebimento" class="lk-forma-pagamento-label">
                        <i data-lucide="hand-coins"></i>
                        Forma de recebimento
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalFormaRecebimento" name="forma_recebimento" class="lk-select"
                            data-lk-custom-select="modal" onchange="lancamentoGlobalManager.selecionarFormaRecebimento(this.value)">
                            <option value="">Não informar</option>
                            <option value="pix">PIX</option>
                            <option value="deposito">Depósito</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="transferencia">Transferência</option>
                            <option value="estorno_cartao">Estorno no cartão</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                </div>

                <div class="lk-form-group lk-forma-cartao-info" id="globalCartaoCreditoGroup" style="display: none;">
                    <label for="globalLancamentoCartaoCredito" class="lk-label">
                        <i data-lucide="credit-card"></i>
                        Cartão
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalLancamentoCartaoCredito" name="cartao_credito_id" class="lk-select"
                            data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                            data-lk-select-search-placeholder="Buscar cartão..."
                            onchange="typeof lancamentoGlobalManager !== 'undefined' && lancamentoGlobalManager.onCartaoEstornoChange && lancamentoGlobalManager.onCartaoEstornoChange()">
                            <option value="">Selecione o cartão</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                </div>

                <div class="lk-form-group" id="globalFaturaEstornoGroup" style="display: none;">
                    <label for="globalLancamentoFaturaEstorno" class="lk-label">
                        <i data-lucide="receipt"></i>
                        Fatura do estorno
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalLancamentoFaturaEstorno" name="fatura_mes_ano" class="lk-select"
                            data-lk-custom-select="modal" data-lk-select-search="true"
                            data-lk-select-search-placeholder="Buscar fatura...">
                            <option value="">Carregando faturas...</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                </div>

                <div class="lk-form-group" id="globalParcelamentoGroup" style="display: none;">
                    <div class="lk-checkbox-wrapper">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="globalLancamentoParcelado" name="eh_parcelado"
                                class="lk-checkbox">
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i data-lucide="calendar-days"></i>
                                <span class="lk-parcel-texto" id="globalParcelamentoTexto">Parcelar lançamento</span>
                            </span>
                        </label>
                    </div>
                    <small class="lk-helper-text" id="globalParcelamentoHelperText">O valor total será dividido em
                        parcelas futuras.</small>
                </div>

                <div class="lk-form-group" id="globalAssinaturaCartaoGroup" style="display: none;">
                    <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="globalLancamentoAssinaturaCartao" name="recorrente_cartao"
                                value="1" class="lk-checkbox"
                                onchange="lancamentoGlobalManager.toggleAssinaturaCartao()">
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i data-lucide="refresh-cw"></i>
                                Assinatura / Recorrente
                            </span>
                        </label>
                    </div>

                    <div id="globalAssinaturaCartaoDetalhes" style="display: none;">
                        <label class="lk-label">
                            <i data-lucide="refresh-cw"></i>
                            Frequência
                        </label>
                        <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                            <select id="globalLancamentoAssinaturaFreq" name="recorrencia_freq_cartao" class="lk-select"
                                data-lk-custom-select="modal" data-lk-select-search="true"
                                data-lk-select-search-placeholder="Buscar frequência...">
                                <option value="mensal" selected>Mensal</option>
                                <option value="bimestral">Bimestral</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>

                        <div class="lk-radio-group">
                            <label class="lk-radio-label">
                                <input type="radio" name="global_assinatura_modo" value="infinito" class="lk-radio"
                                    checked onchange="lancamentoGlobalManager.toggleAssinaturaCartaoFim()">
                                <span class="lk-radio-custom"></span>
                                <span class="lk-radio-text">Sem data de fim</span>
                            </label>
                            <label class="lk-radio-label">
                                <input type="radio" name="global_assinatura_modo" value="data" class="lk-radio"
                                    onchange="lancamentoGlobalManager.toggleAssinaturaCartaoFim()">
                                <span class="lk-radio-custom"></span>
                                <span class="lk-radio-text">Até uma data</span>
                            </label>
                        </div>

                        <div id="globalAssinaturaCartaoFimGroup" style="display: none;">
                            <input type="date" id="globalLancamentoAssinaturaFim" name="recorrencia_fim_cartao"
                                class="lk-input">
                        </div>
                    </div>
                </div>

                <div class="lk-form-group" id="globalNumeroParcelasGroup" style="display: none;">
                    <label for="globalLancamentoTotalParcelas" class="lk-label required">
                        <i data-lucide="list-ordered"></i>
                        <span id="globalNumeroParcelasLabelTexto">Número de parcelas</span>
                    </label>
                    <div class="lk-input-group">
                        <input type="number" id="globalLancamentoTotalParcelas" name="total_parcelas" class="lk-input"
                            min="2" max="48" value="2" placeholder="12">
                        <span class="lk-input-suffix" id="globalNumeroParcelasSuffixTexto">parcelas</span>
                    </div>
                    <div id="globalParcelamentoPreview" class="lk-parcelamento-preview" style="display: none;"></div>
                </div>

                <div class="lk-form-group" id="globalCategoriaGroup">
                    <label for="globalLancamentoCategoria" class="lk-label">
                        <i data-lucide="tag"></i>
                        Categoria
                    </label>
                    <div class="lk-ai-category-row">
                        <div class="lk-select-wrapper" style="flex:1">
                            <select id="globalLancamentoCategoria" name="categoria_id" class="lk-select"
                                data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                                data-lk-select-search-placeholder="Buscar categoria...">
                                <option value="">Sem categoria</option>
                            </select>
                            <i data-lucide="chevron-down" class="lk-select-icon"></i>
                        </div>
                        <button type="button" class="lk-btn-ai-suggest" id="btnGlobalAiSuggestCategoria"
                            onclick="lancamentoGlobalManager.sugerirCategoriaIA()" title="Sugerir categoria com IA">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="lk-planning-alerts" id="globalCategoriaPlanningAlerts" hidden></div>

                <div class="lk-form-group subcategoria-select-group" id="globalSubcategoriaGroup">
                    <label for="globalLancamentoSubcategoria" class="lk-label">
                        <i data-lucide="tags"></i>
                        Subcategoria
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalLancamentoSubcategoria" name="subcategoria_id" class="lk-select"
                            data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                            data-lk-select-search-placeholder="Buscar subcategoria...">
                            <option value="">Sem subcategoria</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                </div>

                <div class="lk-form-group" id="globalMetaGroup" style="display: none;">
                    <label for="globalLancamentoMeta" class="lk-label">
                        <i data-lucide="target"></i>
                        Meta
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalLancamentoMeta" name="meta_id" class="lk-select" data-lk-custom-select="modal"
                            data-lk-select-search="true" data-lk-select-sort="alpha"
                            data-lk-select-search-placeholder="Buscar meta..."
                            onchange="lancamentoGlobalManager.onMetaChange()">
                            <option value="">Nenhuma meta</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-icon"></i>
                    </div>
                    <small class="lk-helper-text" id="globalMetaHelperText">
                        Vincule para registrar aporte ou uso da meta com este lançamento.
                    </small>

                    <div class="lk-form-group" id="globalMetaValorGroup" style="display: none; margin-top: 0.75rem;">
                        <label for="globalLancamentoMetaValor" class="lk-label">
                            <i data-lucide="coins"></i>
                            Valor da meta
                        </label>
                        <div class="lk-input-money">
                            <span class="lk-currency-symbol">R$</span>
                            <input type="text" id="globalLancamentoMetaValor" name="meta_valor"
                                class="lk-input lk-input-with-prefix" value="" placeholder="0,00" autocomplete="off"
                                inputmode="decimal">
                        </div>
                    </div>

                    <div class="lk-form-group" id="globalMetaRealizacaoGroup"
                        style="display: none; margin-top: 0.5rem;">
                        <div class="lk-checkbox-wrapper">
                            <label class="lk-checkbox-label">
                                <input type="checkbox" id="globalLancamentoMetaRealizacao" class="lk-checkbox">
                                <span class="lk-checkbox-custom"></span>
                                <span class="lk-checkbox-text">
                                    <i data-lucide="flag"></i>
                                    Este gasto realiza a meta
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="lk-form-group" id="globalRecorrenciaGroup" style="display: none;">
                    <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
                        <label class="lk-checkbox-label">
                            <input type="checkbox" id="globalLancamentoRecorrente" name="recorrente" value="1"
                                class="lk-checkbox" onchange="lancamentoGlobalManager.toggleRecorrencia()">
                            <span class="lk-checkbox-custom"></span>
                            <span class="lk-checkbox-text">
                                <i data-lucide="refresh-cw"></i>
                                Repetir lançamento
                            </span>
                        </label>
                    </div>

                    <div id="globalRecorrenciaDetalhes" style="display: none;">
                        <label class="lk-label">
                            <i data-lucide="refresh-cw"></i>
                            Frequência
                        </label>
                        <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                            <select id="globalLancamentoRecorrenciaFreq" name="recorrencia_freq" class="lk-select"
                                data-lk-custom-select="modal" data-lk-select-search="true"
                                data-lk-select-search-placeholder="Buscar frequência...">
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

                        <div class="lk-radio-group" style="margin-bottom: 0.5rem;">
                            <label class="lk-radio-label" id="globalRecorrenciaRadioInfinito">
                                <input type="radio" name="global_recorrencia_modo" value="infinito" class="lk-radio"
                                    checked onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                <span class="lk-radio-custom"></span>
                                <span class="lk-radio-text">Sem fim</span>
                            </label>
                            <label class="lk-radio-label">
                                <input type="radio" name="global_recorrencia_modo" value="quantidade" class="lk-radio"
                                    onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                <span class="lk-radio-custom"></span>
                                <span class="lk-radio-text">Por quantidade</span>
                            </label>
                            <label class="lk-radio-label">
                                <input type="radio" name="global_recorrencia_modo" value="data" class="lk-radio"
                                    onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                                <span class="lk-radio-custom"></span>
                                <span class="lk-radio-text">Até uma data</span>
                            </label>
                        </div>

                        <div id="globalRecorrenciaTotalGroup" style="display: none;">
                            <div class="lk-input-group">
                                <input type="number" id="globalLancamentoRecorrenciaTotal" name="recorrencia_total"
                                    class="lk-input" min="2" max="120" value="12" placeholder="12">
                                <span class="lk-input-suffix">vezes</span>
                            </div>
                        </div>

                        <div id="globalRecorrenciaFimGroup" style="display: none;">
                            <input type="date" id="globalLancamentoRecorrenciaFim" name="recorrencia_fim"
                                class="lk-input">
                        </div>
                    </div>
                </div>

                <div class="lk-form-group" id="globalLembreteGroup" style="display: none;">
                    <label for="globalLancamentoTempoAviso" class="lk-label">
                        <i data-lucide="bell"></i>
                        Lembrete
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="globalLancamentoTempoAviso" name="lembrar_antes_segundos" class="lk-select"
                            data-lk-custom-select="modal" data-lk-select-search="true"
                            data-lk-select-search-placeholder="Buscar lembrete...">
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
            </div>

            <div class="lk-page-quick-options-actions">
                <button type="submit" class="lk-btn lk-btn-primary lk-page-submit-btn lk-page-quick-submit-bottom">
                    <i data-lucide="check"></i>
                    <span class="lk-page-submit-label">Salvar transação</span>
                </button>
            </div>
        </div>
    </div>

    <div class="lk-page-quick-history">
        <button type="button" class="lk-page-history-toggle" id="globalQuickHistoryBtn"
            onclick="lancamentoGlobalManager.toggleQuickHistory()" aria-expanded="false"
            aria-controls="globalQuickHistoryPanel">
            <span>
                <i data-lucide="history"></i>
                Últimas movimentações
            </span>
            <i data-lucide="chevron-down" class="lk-page-history-toggle__chevron"></i>
        </button>

        <div class="lk-page-history-panel" id="globalQuickHistoryPanel" hidden>
            <div class="lk-historico-list surface-card" id="globalLancamentoHistorico">
                <div class="lk-historico-empty">
                    <i data-lucide="history"></i>
                    <p>Escolha uma conta para ver o histórico.</p>
                </div>
            </div>
        </div>
    </div>
</div>