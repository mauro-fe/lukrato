<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/categorias-modern.css">

<section class="cat-page">
    <!-- ==================== CARD DE NOVA CATEGORIA ==================== -->
    <div class="modern-card create-card" data-aos="fade-up">
        <div class="card-header-icon">
            <div class="icon-wrapper create">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="card-title-group">
                <h3 class="card-title">Criar Nova Categoria</h3>
                <p class="card-subtitle">Organize suas receitas e despesas com categorias personalizadas</p>
            </div>
        </div>

        <form id="formNova" class="modern-form">
            <?= csrf_input('default') ?>
            <div class="form-grid">
                <div class="input-group">
                    <label for="catNome" class="input-label">
                        <i class="fas fa-tag"></i>
                        <span>Nome da Categoria</span>
                    </label>
                    <input id="catNome" class="modern-input" name="nome" 
                           placeholder="Ex: Alimentação, Salário..." 
                           required minlength="2" maxlength="100" 
                           aria-label="Nome da categoria" />
                </div>

                <div class="input-group">
                    <label for="catTipo" class="input-label">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Tipo</span>
                    </label>
                    <select id="catTipo" class="modern-select" name="tipo" 
                            required aria-label="Tipo de categoria">
                        <option value="receita">💰 Receita</option>
                        <option value="despesa">💸 Despesa</option>
                    </select>
                </div>
            </div>

            <button class="modern-btn primary submit-btn" type="submit">
                <i class="fas fa-plus-circle"></i>
                <span>Adicionar Categoria</span>
            </button>
        </form>
    </div>

    <!-- ==================== CATEGORIAS SEPARADAS POR TIPO ==================== -->
    <div class="categories-grid" data-aos="fade-up" data-aos-delay="150">
        <!-- CATEGORIAS DE RECEITAS -->
        <div class="category-card receitas-card">
            <div class="category-header receitas">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Receitas</h3>
                        <p class="category-count">
                            <span id="receitasCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar receitas" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <div class="category-list" id="receitasList">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma categoria de receita cadastrada</p>
                </div>
            </div>
        </div>

        <!-- CATEGORIAS DE DESPESAS -->
        <div class="category-card despesas-card">
            <div class="category-header despesas">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Despesas</h3>
                        <p class="category-count">
                            <span id="despesasCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar despesas" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <div class="category-list" id="despesasList">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma categoria de despesa cadastrada</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/editar-categorias.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>