<!-- Modal de Exclusao de Lancamento -->
<div class="modal fade lan-delete-modal" id="modalDeleteLancamentoScope" tabindex="-1"
    aria-labelledby="modalDeleteLancamentoScopeLabel" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon" aria-hidden="true">
                        <i data-lucide="trash-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-1" id="modalDeleteLancamentoScopeLabel">Excluir lancamento</h5>
                        <p class="modal-subtitle mb-0" id="deleteScopeModalSubtitle">
                            Revise a exclusao antes de confirmar.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form id="deleteScopeForm">
                <div class="modal-body">
                    <p class="lan-delete-scope-lead mb-0" id="deleteScopeModalLead">
                        Esta acao nao pode ser desfeita.
                    </p>

                    <div class="lan-delete-scope-options" id="deleteScopeOptions" role="radiogroup"
                        aria-labelledby="modalDeleteLancamentoScopeLabel">
                        <label class="lan-delete-scope-option" data-delete-scope-option="single">
                            <input class="lan-delete-scope-input" type="radio" name="deleteScopeOption" value="single"
                                checked>
                            <span class="lan-delete-scope-card surface-control-box surface-control-box--interactive">
                                <span class="lan-delete-scope-card-head">
                                    <span class="lan-delete-scope-indicator" aria-hidden="true"></span>
                                    <span class="lan-delete-scope-option-title" data-delete-scope-title="single">
                                        Apenas este lancamento
                                    </span>
                                </span>
                                <span class="lan-delete-scope-option-text" data-delete-scope-text="single">
                                    Remove somente o registro atual.
                                </span>
                            </span>
                        </label>

                        <label class="lan-delete-scope-option" data-delete-scope-option="future">
                            <input class="lan-delete-scope-input" type="radio" name="deleteScopeOption" value="future">
                            <span class="lan-delete-scope-card surface-control-box surface-control-box--interactive">
                                <span class="lan-delete-scope-card-head">
                                    <span class="lan-delete-scope-indicator" aria-hidden="true"></span>
                                    <span class="lan-delete-scope-option-title" data-delete-scope-title="future">
                                        Este e os proximos nao pagos
                                    </span>
                                </span>
                                <span class="lan-delete-scope-option-text" data-delete-scope-text="future">
                                    Mantem os itens ja quitados da serie.
                                </span>
                            </span>
                        </label>

                        <label class="lan-delete-scope-option" data-delete-scope-option="all">
                            <input class="lan-delete-scope-input" type="radio" name="deleteScopeOption" value="all">
                            <span class="lan-delete-scope-card surface-control-box surface-control-box--interactive">
                                <span class="lan-delete-scope-card-head">
                                    <span class="lan-delete-scope-indicator" aria-hidden="true"></span>
                                    <span class="lan-delete-scope-option-title" data-delete-scope-title="all">
                                        Toda a serie
                                    </span>
                                </span>
                                <span class="lan-delete-scope-option-text" data-delete-scope-text="all">
                                    Remove o vinculo completo dessa recorrencia ou parcelamento.
                                </span>
                            </span>
                        </label>
                    </div>

                    <p class="lan-delete-scope-hint mb-0" id="deleteScopeModalHint">
                        O lancamento sera removido permanentemente.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnConfirmDeleteScope">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>
