<!-- Modal de Edição de Transferência -->
<div class="modal fade" id="modalEditarTransferencia" tabindex="-1" aria-labelledby="modalEditarTransferenciaLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:650px">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarTransferenciaLabel">Editar Transferência</h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal"
                    aria-label="Fechar modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="editTransAlert" class="alert alert-danger d-none" role="alert"></div>
                <form id="formTransLancamento" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editTransData" class="form-label">Data</label>
                            <input type="date" class="form-control form-control-sm" id="editTransData" required
                                aria-required="true">
                        </div>
                        <div class="col-md-6">
                            <label for="editTransValor" class="form-label">Valor</label>
                            <input type="text" class="form-control form-control-sm money-mask" id="editTransValor"
                                inputmode="decimal" placeholder="R$ 0,00" required aria-required="true">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="editTransConta" class="form-label">Conta Origem</label>
                            <select class="form-select form-select-sm" id="editTransConta" required
                                aria-required="true" data-lk-custom-select="compact"
                                data-lk-select-search="true" data-lk-select-sort="alpha"
                                data-lk-select-search-placeholder="Buscar conta de origem..."></select>
                        </div>
                        <div class="col-md-6">
                            <label for="editTransContaDestino" class="form-label">Conta Destino</label>
                            <select class="form-select form-select-sm" id="editTransContaDestino" required
                                aria-required="true" data-lk-custom-select="compact"
                                data-lk-select-search="true" data-lk-select-sort="alpha"
                                data-lk-select-search-placeholder="Buscar conta de destino..."></select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label for="editTransDescricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control form-control-sm" id="editTransDescricao"
                                aria-label="Descrição da transferência">
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">Salvar alteração</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
