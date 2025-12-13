<!-- Modal de Edição -->
<div class="modal fade" id="modalEditCategoria" tabindex="-1" aria-labelledby="modalEditCategoriaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditCategoriaLabel">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-0">
                <div id="editCategoriaAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formEditCategoria" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="editCategoriaNome">📝 Nome</label>
                        <input type="text" class="form-control form-control-sm" id="editCategoriaNome" name="nome"
                            placeholder="Nome da categoria" required minlength="2" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="editCategoriaTipo">🏷️ Tipo</label>
                        <select class="form-select form-select-sm" id="editCategoriaTipo" name="tipo" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary btn-sm" form="formEditCategoria">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>