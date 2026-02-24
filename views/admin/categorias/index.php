<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/categorias-modern.css?v=<?= time() ?>">

<section class="cat-page">
    <!-- ==================== CARD DE NOVA CATEGORIA ==================== -->
    <div class="modern-card create-card" data-aos="fade-up">
        <div class="card-header-icon">
            <div class="icon-wrapper create">
                <i data-lucide="circle-plus" style="color: var(--color-primary)"></i>
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
                        <i data-lucide="tag" style="color: var(--color-primary)"></i>
                        <span>Nome da Categoria</span>
                    </label>
                    <input id="catNome" class="modern-input" name="nome" placeholder="Ex: Alimentação, Salário..."
                        required minlength="2" maxlength="100" aria-label="Nome da categoria" />
                </div>

                <div class="input-group">
                    <label for="catTipo" class="input-label">
                        <i data-lucide="arrow-left-right" style="color: var(--color-primary)"></i>
                        <span>Tipo</span>
                    </label>
                    <select id="catTipo" class="modern-select" name="tipo" required aria-label="Tipo de categoria">
                        <option value="receita">↑ Receita</option>
                        <option value="despesa">↓ Despesa</option>
                    </select>
                </div>
            </div>

            <button class="modern-btn primary submit-btn" type="submit">
                <i data-lucide="plus"></i>
                <span>Adicionar Categoria</span>
            </button>
        </form>
    </div>

    <!-- ==================== SELETOR DE MÊS (padrão Lukrato) ==================== -->
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- ==================== CATEGORIAS SEPARADAS POR TIPO ==================== -->
    <div class="categories-grid" data-aos="fade-up" data-aos-delay="150">
        <!-- CATEGORIAS DE RECEITAS -->
        <div class="category-card receitas-card">
            <div class="category-header receitas">
                <div class="header-content">
                    <div class="header-icon">
                        <i data-lucide="arrow-up"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Receitas</h3>
                        <p class="category-count">
                            <span id="receitasCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar receitas"
                    onclick="location.reload()">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>

            <div class="category-list" id="receitasList">
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de receita cadastrada</p>
                </div>
            </div>
        </div>

        <!-- CATEGORIAS DE DESPESAS -->
        <div class="category-card despesas-card">
            <div class="category-header despesas">
                <div class="header-content">
                    <div class="header-icon">
                        <i data-lucide="arrow-down"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Despesas</h3>
                        <p class="category-count">
                            <span id="despesasCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar despesas"
                    onclick="location.reload()">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>

            <div class="category-list" id="despesasList">
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de despesa cadastrada</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/editar-categorias.php'; ?>

<!-- Modal de Orçamento / Limite Mensal -->
<div class="modal fade" id="modalOrcamento" tabindex="-1" aria-labelledby="modalOrcamentoLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalOrcamentoLabel">
                    <i data-lucide="wallet"></i> Limite Mensal
                </h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="orc-modal-description">
                    <i data-lucide="info"></i>
                    <span>Defina o valor máximo que deseja gastar por mês nesta categoria. Você será alertado quando
                        estiver próximo ou ultrapassar o limite.</span>
                </div>

                <p class="orc-modal-cat-name">Categoria: <strong id="orcCategoriaNome">—</strong></p>
                <p class="orc-modal-gasto d-none" id="orcGastoAtual">Gasto atual: <strong id="orcGastoValor">R$
                        0,00</strong></p>

                <div id="orcAlertError" class="alert alert-danger d-none py-2 px-3" style="font-size:0.85rem"
                    role="alert"></div>

                <form id="formOrcamento" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="orcValorLimite">Orçamento mensal (R$)</label>
                        <div class="orc-input-wrapper">
                            <span class="orc-input-prefix">R$</span>
                            <input type="text" class="form-control orc-input-currency" id="orcValorLimite"
                                name="valor_limite" placeholder="0,00" required inputmode="decimal" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btnRemoverOrcamento">
                    <i data-lucide="trash-2"></i> Remover limite
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" form="formOrcamento" id="btnSalvarOrcamento">
                    <i data-lucide="check"></i> <span id="btnOrcText">Definir</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/categorias-manager.js"></script>