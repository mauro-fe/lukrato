<!-- Modal de Edição de Lançamento (usado pela tabela) -->
<div class="modal fade lk-edit-lanc-modal" id="modalEditarLancamento" tabindex="-1"
    aria-labelledby="modalEditarLancamentoLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable lk-edit-lanc-modal__dialog">
        <div class="modal-content lk-edit-lanc-modal__content">
            <div class="modal-header lk-edit-lanc-modal__header">
                <div class="lk-edit-lanc-modal__header-main">
                    <div class="modal-icon lk-edit-lanc-modal__icon">
                        <i data-lucide="pen-square"></i>
                    </div>
                    <div class="lk-edit-lanc-modal__header-copy">
                        <span class="lk-edit-lanc-modal__eyebrow">Ajuste rápido</span>
                        <h5 class="modal-title" id="modalEditarLancamentoLabel">Editar lançamento</h5>
                        <p class="modal-subtitle">Revise os dados principais sem perder o contexto da sua lista.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar modal"></button>
            </div>
            <div class="modal-body lk-edit-lanc-modal__body">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert"></div>
                <form id="formLancamento" class="lk-edit-lanc-form" novalidate>
                    <section class="lk-edit-lanc-summary surface-card surface-card--glass">
                        <div class="lk-edit-lanc-summary__copy">
                            <span class="lk-edit-lanc-summary__eyebrow">Resumo do lançamento</span>
                            <strong id="editLancSummaryTitle" class="lk-edit-lanc-summary__title">Sem descrição
                                informada</strong>
                            <span id="editLancSummaryMeta" class="lk-edit-lanc-summary__meta">Conta, categoria e data
                                aparecem aqui.</span>
                        </div>
                        <div class="lk-edit-lanc-summary__aside">
                            <div class="lk-edit-lanc-summary__chips">
                                <span id="editLancSummaryTipo"
                                    class="lk-edit-lanc-summary__chip lk-edit-lanc-summary__chip--tipo is-despesa">Despesa</span>
                                <span id="editLancSummaryStatus"
                                    class="lk-edit-lanc-summary__chip lk-edit-lanc-summary__chip--status is-warning">Pendente</span>
                            </div>
                            <strong id="editLancSummaryValor" class="lk-edit-lanc-summary__value">R$ 0,00</strong>
                        </div>
                    </section>

                    <div class="lk-edit-lanc-grid">
                        <section class="lk-edit-lanc-card">
                            <div class="lk-edit-lanc-card__header">
                                <div>
                                    <h6>Quando e quanto</h6>
                                    <p>Esses dados impactam saldo, gráficos e relatórios.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="editLancData">Data e horário</label>
                                    <div class="lk-datetime-inline">
                                        <div class="lk-datetime-date">
                                            <i data-lucide="calendar" class="lk-datetime-icon"></i>
                                            <input type="date" class="form-control" id="editLancData" required
                                                aria-required="true">
                                        </div>
                                        <div class="lk-datetime-sep"><span>às</span></div>
                                        <div class="lk-datetime-time">
                                            <i data-lucide="clock" class="lk-datetime-icon"></i>
                                            <input type="time" class="form-control" id="editLancHora"
                                                aria-label="Horário do lançamento">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="editLancTipo" class="form-label">Tipo</label>
                                    <select class="form-select" id="editLancTipo" required aria-required="true">
                                        <option value="receita">Receita</option>
                                        <option value="despesa">Despesa</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editLancValor" class="form-label">Valor</label>
                                    <input type="text" class="form-control money-mask" id="editLancValor"
                                        inputmode="decimal" placeholder="R$ 0,00" required aria-required="true">
                                </div>
                            </div>
                        </section>

                        <section class="lk-edit-lanc-card">
                            <div class="lk-edit-lanc-card__header">
                                <div>
                                    <h6>Classificação</h6>
                                    <p>Organize a movimentação para facilitar os filtros depois.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="editLancConta" class="form-label">Conta</label>
                                    <select class="form-select" id="editLancConta" required
                                        aria-required="true"></select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editLancCategoria" class="form-label">Categoria</label>
                                    <div class="lk-edit-lanc-category-row">
                                        <select class="form-select" id="editLancCategoria"></select>
                                        <button type="button" class="lk-btn-ai-suggest" id="btnEditAiSuggestCategoria"
                                            onclick="window._editLancSugerirCategoriaIA()"
                                            title="Sugerir categoria com IA" aria-label="Sugerir categoria com IA">
                                            <i data-lucide="sparkles"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 subcategoria-select-group" id="editSubcategoriaGroup">
                                    <label for="editLancSubcategoria" class="form-label">Subcategoria</label>
                                    <select class="form-select" id="editLancSubcategoria">
                                        <option value="">Sem subcategoria</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="lk-edit-lanc-card lk-edit-lanc-card--wide">
                            <div class="lk-edit-lanc-card__header">
                                <div>
                                    <h6>Detalhes finais</h6>
                                    <p>Deixe o lançamento fácil de identificar quando você bater o olho.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label for="editLancDescricao" class="form-label">Descrição</label>
                                    <input type="text" class="form-control" id="editLancDescricao"
                                        placeholder="Ex.: Mercado, Uber, salário" aria-label="Descrição do lançamento">
                                </div>
                                <div class="col-md-5">
                                    <label for="editLancFormaPagamento" class="form-label">Forma de pagamento</label>
                                    <select class="form-select" id="editLancFormaPagamento">
                                        <option value="">Não informada</option>
                                        <option value="pix">📱 PIX</option>
                                        <option value="cartao_credito">💳 Cartão de Crédito</option>
                                        <option value="cartao_debito">💳 Cartão de Débito</option>
                                        <option value="dinheiro">💵 Dinheiro</option>
                                        <option value="boleto">📄 Boleto</option>
                                        <option value="transferencia">🏦 Transferência</option>
                                        <option value="deposito">🏦 Depósito</option>
                                        <option value="estorno_cartao">↩️ Estorno Cartão</option>
                                        <option value="cheque">📝 Cheque</option>
                                    </select>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="lk-edit-lanc-modal__footer">
                        <p class="lk-edit-lanc-modal__hint">As mudanças atualizam saldo, dashboard e relatórios
                            automaticamente.</p>
                        <div class="lk-edit-lanc-modal__actions">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="save" class="me-1"></i>
                                <span>Salvar alterações</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>