<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">


<section class="c-page">
    <!-- Formulário de Nova Categoria -->
    <div class="c-card mt-4" data-aos="fade-up">
        <p class="c-subtitle"><i class="fa-solid fa-layer-group"></i> Criar Nova Categoria</p>

        <form id="formNova" class="c-form">
            <?= csrf_input('default') ?>
            <div class="c-form-inputs">
                <input class="c-input" name="nome" placeholder="Nome da categoria" required minlength="2"
                    maxlength="100" aria-label="Nome da categoria" />
                <select class="lk-select" name="tipo" required aria-label="Tipo de categoria">
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>
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

<?php include __DIR__ . '/../partials/modals/editar-categorias.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>