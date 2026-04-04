<div class="lk-edit-lanc-grid">
    <section class="lk-edit-lanc-card surface-card">
        <div class="lk-edit-lanc-card__header">
            <div>
                <h6>Quando e quanto</h6>
                <p>Esses dados impactam saldo, gráficos e relatórios.</p>
            </div>
        </div>

        <div class="row g-4 lk-edit-lanc-card__grid">
            <div class="col-12">
                <label class="form-label" for="editLancData">Data e horário</label>
                <div class="lk-datetime-inline">
                    <div class="lk-datetime-date">
                        <i data-lucide="calendar" class="lk-datetime-icon"></i>
                        <input type="date" class="form-control" id="editLancData" required aria-required="true">
                    </div>
                    <div class="lk-datetime-sep"><span>às</span></div>
                    <div class="lk-datetime-time">
                        <i data-lucide="clock" class="lk-datetime-icon"></i>
                        <input type="time" class="form-control" id="editLancHora" aria-label="Horário do lançamento">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label for="editLancTipo" class="form-label">Tipo</label>
                <select class="form-select" id="editLancTipo" required aria-required="true" data-lk-custom-select="form"
                    data-lk-select-search="true" data-lk-select-search-placeholder="Buscar tipo...">
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="editLancValor" class="form-label">Valor</label>
                <input type="text" class="form-control money-mask" id="editLancValor" inputmode="decimal"
                    placeholder="R$ 0,00" required aria-required="true">
            </div>
        </div>
    </section>

    <section class="lk-edit-lanc-card surface-card">
        <div class="lk-edit-lanc-card__header">
            <div>
                <h6>Classificação</h6>
                <p>Organize a movimentação para facilitar os filtros depois.</p>
            </div>
        </div>

        <div class="row g-4 lk-edit-lanc-card__grid">
            <div class="col-md-6">
                <label for="editLancConta" class="form-label">Conta</label>
                <select class="form-select" id="editLancConta" required data-lk-custom-select="form"
                    data-lk-select-search="true" data-lk-select-sort="alpha"
                    data-lk-select-search-placeholder="Buscar conta..." aria-required="true"></select>
            </div>
            <div class="col-md-6">
                <label for="editLancCategoria" class="form-label">Categoria</label>
                <div class="lk-edit-lanc-category-row">
                    <select class="form-select" id="editLancCategoria" data-lk-custom-select="form"
                        data-lk-select-search="true" data-lk-select-sort="alpha"
                        data-lk-select-search-placeholder="Buscar categoria..."></select>
                    <button type="button" class="lk-btn-ai-suggest" id="btnEditAiSuggestCategoria"
                        onclick="window._editLancSugerirCategoriaIA()" title="Sugerir categoria com IA"
                        aria-label="Sugerir categoria com IA">
                        <i data-lucide="sparkles"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 subcategoria-select-group" id="editSubcategoriaGroup">
                <label for="editLancSubcategoria" class="form-label">Subcategoria</label>
                <select class="form-select" id="editLancSubcategoria" data-lk-custom-select="form"
                    data-lk-select-search="true" data-lk-select-sort="alpha"
                    data-lk-select-search-placeholder="Buscar subcategoria...">
                    <option value="">Sem subcategoria</option>
                </select>
            </div>
            <div class="col-12" id="editLancMetaGroup" hidden>
                <label for="editLancMeta" class="form-label">Meta vinculada</label>
                <select class="form-select" id="editLancMeta" data-lk-custom-select="form" data-lk-select-search="true"
                    data-lk-select-sort="alpha" data-lk-select-search-placeholder="Buscar meta...">
                    <option value="">Nenhuma meta</option>
                </select>
                <small class="lk-edit-lanc-field-help">
                    O valor desta receita sera somado ao valor alocado da meta sem mexer no saldo da
                    conta.
                </small>
            </div>
            <div class="col-md-6" id="editLancMetaValorGroup" hidden>
                <label for="editLancMetaValor" class="form-label">Quanto veio da meta?</label>
                <input type="text" class="form-control money-mask" id="editLancMetaValor" inputmode="decimal"
                    placeholder="R$ 0,00">
                <small class="lk-edit-lanc-field-help">
                    O restante continua como gasto normal do mês.
                </small>
            </div>
            <div class="col-md-6" id="editLancMetaRealizacaoGroup" hidden>
                <label class="form-label d-block">Realizacao da meta</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editLancMetaRealizacao">
                    <label class="form-check-label" for="editLancMetaRealizacao">
                        Este gasto realiza o objetivo da meta
                    </label>
                </div>
                <small class="lk-edit-lanc-field-help">
                    Marcado: a meta vai para realizada sem reduzir o reservado.
                </small>
            </div>
        </div>

        <div id="editLancPlanningAlerts" class="lk-planning-alerts" hidden></div>
    </section>

    <section class="lk-edit-lanc-card lk-edit-lanc-card--wide surface-card">
        <div class="lk-edit-lanc-card__header">
            <div>
                <h6>Detalhes finais</h6>
                <p>Deixe o lançamento fácil de identificar quando você bater o olho.</p>
            </div>
        </div>

        <div class="row g-4 lk-edit-lanc-card__grid">
            <div class="col-md-7">
                <label for="editLancDescricao" class="form-label">Descrição</label>
                <input type="text" class="form-control" id="editLancDescricao" placeholder="Ex.: Mercado, Uber, salário"
                    aria-label="Descrição do lançamento">
            </div>
            <div class="col-md-5">
                <label for="editLancFormaPagamento" class="form-label">Forma de pagamento</label>
                <select class="form-select" id="editLancFormaPagamento" data-lk-custom-select="form"
                    data-lk-select-search="true" data-lk-select-search-placeholder="Buscar forma de pagamento...">
                    <option value="">Não informada</option>
                    <option value="pix">PIX</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="boleto">Boleto</option>
                    <option value="transferencia">Transferência</option>
                    <option value="deposito">Depósito</option>
                    <option value="estorno_cartao">Estorno Cartão</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
        </div>
    </section>
</div>