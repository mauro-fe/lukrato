<!-- Modal de exclusao de item da fatura -->
<div class="modal fade lan-delete-modal" id="modalDeleteFaturaItemScope" tabindex="-1"
    aria-labelledby="modalDeleteFaturaItemScopeLabel" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon" aria-hidden="true">
                        <i data-lucide="trash-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-1" id="modalDeleteFaturaItemScopeLabel">Excluir item da fatura</h5>
                        <p class="modal-subtitle mb-0" id="deleteFaturaItemScopeModalSubtitle">
                            Revise a exclusao antes de confirmar.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form id="deleteFaturaItemScopeForm">
                <div class="modal-body">
                    <p class="lan-delete-scope-lead mb-0" id="deleteFaturaItemScopeModalLead">
                        Esta acao nao pode ser desfeita.
                    </p>

                    <div class="lan-delete-scope-options" id="deleteFaturaItemScopeOptions" role="radiogroup"
                        aria-labelledby="modalDeleteFaturaItemScopeLabel">
                        <label class="lan-delete-scope-option" data-delete-fatura-scope-option="item">
                            <input class="lan-delete-scope-input" type="radio" name="deleteFaturaItemScopeOption" value="item"
                                checked>
                            <span class="lan-delete-scope-card surface-control-box surface-control-box--interactive">
                                <span class="lan-delete-scope-card-head">
                                    <span class="lan-delete-scope-indicator" aria-hidden="true"></span>
                                    <span class="lan-delete-scope-option-title" data-delete-fatura-scope-title="item">
                                        Apenas esta parcela
                                    </span>
                                </span>
                                <span class="lan-delete-scope-option-text" data-delete-fatura-scope-text="item">
                                    Remove somente o item atual da fatura.
                                </span>
                            </span>
                        </label>

                        <label class="lan-delete-scope-option" data-delete-fatura-scope-option="parcelamento">
                            <input class="lan-delete-scope-input" type="radio" name="deleteFaturaItemScopeOption"
                                value="parcelamento">
                            <span class="lan-delete-scope-card surface-control-box surface-control-box--interactive">
                                <span class="lan-delete-scope-card-head">
                                    <span class="lan-delete-scope-indicator" aria-hidden="true"></span>
                                    <span class="lan-delete-scope-option-title" data-delete-fatura-scope-title="parcelamento">
                                        Todo o parcelamento
                                    </span>
                                </span>
                                <span class="lan-delete-scope-option-text" data-delete-fatura-scope-text="parcelamento">
                                    Remove todas as parcelas vinculadas a esta compra.
                                </span>
                            </span>
                        </label>
                    </div>

                    <p class="lan-delete-scope-hint mb-0" id="deleteFaturaItemScopeModalHint">
                        O item sera removido permanentemente da fatura.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmDeleteFaturaItemScope">Continuar</button>
                </div>
            </form>
        </div>
    </div>
</div>