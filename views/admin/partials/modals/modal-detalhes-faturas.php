<!-- ==================== MODAL: DETALHES DA FATURA ==================== -->
<div class="modal fade" id="modalDetalhesParcelamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list"></i>
                    <span>Detalhes da Fatura</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesParcelamentoContent">
                <!-- Conteúdo carregado dinamicamente -->
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL: EDITAR ITEM DA FATURA ==================== -->
<div class="modal fade" id="modalEditarItemFatura" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-warning"></i>
                    <span>Editar Item</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarItemFatura">
                    <input type="hidden" id="editItemFaturaId" value="">
                    <input type="hidden" id="editItemId" value="">

                    <div class="mb-3">
                        <label for="editItemDescricao" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="editItemDescricao"
                            placeholder="Digite a descrição do item" required>
                    </div>

                    <div class="mb-3">
                        <label for="editItemValor" class="form-label">Valor (R$)</label>
                        <input type="text" class="form-control" id="editItemValor"
                            placeholder="0,00" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnSalvarItemFatura">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>