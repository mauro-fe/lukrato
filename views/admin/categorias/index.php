<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">


<section class="c-page">
    <!-- Formulário de Nova Categoria -->
    <div class="c-card mt-4" data-aos="fade-up">
        <p class="c-subtitle"><i class="fa-solid fa-layer-group"></i> Criar Nova Categoria</p>

        <form id="formNova" class="c-form">
            <?= csrf_input('default') ?>
            <input class="c-input" name="nome" placeholder="Nome da categoria" required minlength="2" maxlength="100"
                aria-label="Nome da categoria" />
            <select class="lk-select" name="tipo" required aria-label="Tipo de categoria">
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
            </select>
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-plus"></i> Adicionar
            </button>
        </form>
    </div>

    <!-- Tabela de Categorias -->
    <div class="container-table" data-aos="fade-up" data-aos-delay="250">
        <!-- Cabeçalho fixo no mobile -->
        <div class="c-mobile-cat-header">
            <span>Nome</span>
            <span>Tipo</span>
            <span>Ações</span>
        </div>

        <!-- Tabela / Cards -->
        <section class="table-container">
            <div id="tabCategorias"></div>
        </section>

        <!-- Cards mobile (renderizados via JS) -->
        <section class="c-mobile-card-wrapper" id="catCards"></section>
    </div>

</section>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



