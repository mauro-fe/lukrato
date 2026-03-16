<section class="cat-page">
    <!-- ==================== CARD DE NOVA CATEGORIA ==================== -->
    <div class="create-card-wrapper">
        <div class="create-card-glow"></div>
        <div class="modern-card create-card">
            <div class="create-card-content">
                <!-- Lado esquerdo: ícone preview -->
                <div class="create-icon-area">
                    <div class="create-icon-ring" id="iconPreviewRing">
                        <div class="create-icon-inner">
                            <i data-lucide="tag" class="create-main-icon" id="iconPreview"></i>
                        </div>
                    </div>
                    <p class="create-hint">Nova categoria</p>
                    <button type="button" class="icon-picker-trigger" id="btnIconPicker" title="Escolher ícone">
                        <i data-lucide="palette"></i>
                        <span>Escolher ícone</span>
                    </button>
                </div>

                <!-- Lado direito: formulário inline -->
                <div class="create-form-area">
                    <div class="create-form-header">
                        <h3 class="create-form-title">Criar Categoria</h3>
                        <p class="create-form-subtitle">Organize suas finanças com categorias personalizadas</p>
                    </div>

                    <form id="formNova" class="create-form">
                        <?= csrf_input('default') ?>
                        <input type="hidden" name="icone" id="catIcone" value="">

                        <div class="create-form-fields">
                            <div class="create-field">
                                <div class="modern-input-wrapper">
                                    <i data-lucide="tag" class="field-icon"></i>
                                    <input id="catNome" class="modern-input create-input" name="nome"
                                        placeholder="Nome da categoria..." required minlength="2" maxlength="100"
                                        aria-label="Nome da categoria" autocomplete="off" />
                                </div>
                            </div>

                            <div class="create-field">
                                <div class="type-toggle-group" role="radiogroup" aria-label="Tipo de categoria">
                                    <input type="radio" name="tipo" value="receita" id="tipoReceita" checked
                                        class="type-toggle-input">
                                    <label for="tipoReceita" class="type-toggle-pill receita">
                                        <i data-lucide="trending-up"></i>
                                        <span>Receita</span>
                                    </label>

                                    <input type="radio" name="tipo" value="despesa" id="tipoDespesa"
                                        class="type-toggle-input">
                                    <label for="tipoDespesa" class="type-toggle-pill despesa">
                                        <i data-lucide="trending-down"></i>
                                        <span>Despesa</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Sugestões rápidas -->
                        <div class="suggestions-section" id="suggestionsSection">
                            <p class="suggestions-label">
                                <i data-lucide="sparkles"></i>
                                Sugestões rápidas
                            </p>
                            <div class="suggestions-chips" id="suggestionsChips">
                                <!-- Populado via JS baseado no tipo selecionado -->
                            </div>
                        </div>

                        <button class="create-submit-btn" type="submit">
                            <span class="create-btn-text">Adicionar</span>
                            <i data-lucide="arrow-right" class="create-btn-icon"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Icon Picker Drawer -->
            <div class="icon-picker-drawer" id="iconPickerDrawer">
                <div class="icon-picker-header">
                    <h4 class="icon-picker-title">
                        <i data-lucide="palette"></i>
                        Escolher Ícone
                    </h4>
                    <button type="button" class="icon-picker-close" id="btnCloseIconPicker">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <div class="icon-picker-search">
                    <i data-lucide="search" class="icon-search-icon"></i>
                    <input type="text" class="icon-search-input" id="iconSearchInput" placeholder="Buscar ícone..."
                        autocomplete="off" />
                </div>
                <div class="icon-picker-grid" id="iconPickerGrid">
                    <!-- Populado via JS -->
                </div>
            </div>
        </div>
    </div>

    <section class="cat-context-card" id="catContextCard" aria-live="polite">
        <div class="cat-context-copy">
            <p class="cat-context-kicker" id="catContextKicker">Categorias e subcategorias</p>
            <h3 class="cat-context-title" id="catContextTitle">Organize sua estrutura financeira com clareza</h3>
            <p class="cat-context-description" id="catContextDescription">
                Os limites mensais exibidos abaixo seguem o mês selecionado no topo da página.
            </p>
        </div>

        <div class="cat-context-stats">
            <article class="cat-context-stat">
                <span class="cat-context-stat-value" id="catTotalCount">0</span>
                <span class="cat-context-stat-label">Categorias</span>
            </article>
            <article class="cat-context-stat">
                <span class="cat-context-stat-value" id="catOwnCount">0</span>
                <span class="cat-context-stat-label">Personalizadas</span>
            </article>
            <article class="cat-context-stat">
                <span class="cat-context-stat-value" id="catSubCount">0</span>
                <span class="cat-context-stat-label">Subcategorias</span>
            </article>
            <article class="cat-context-stat">
                <span class="cat-context-stat-value" id="catBudgetCount">0</span>
                <span class="cat-context-stat-label">Com limite no mês</span>
            </article>
        </div>

        <div class="cat-context-chips" id="catContextChips"></div>

        <div class="cat-context-actions">
            <button type="button" class="cat-context-btn" data-action="refresh-categorias" id="catRefreshButton">
                <i data-lucide="refresh-cw"></i>
                <span>Atualizar dados</span>
            </button>
            <button type="button" class="cat-context-btn ghost d-none" data-action="clear-categoria-search"
                id="catClearSearchButton">
                <i data-lucide="x"></i>
                <span>Limpar busca</span>
            </button>
        </div>
    </section>

    <!-- ==================== BUSCA ==================== -->
    <div class="cat-toolbar">
        <div class="cat-search-wrapper">
            <i data-lucide="search" class="cat-search-icon"></i>
            <input type="text" id="catSearchInput" class="cat-search-input"
                placeholder="Buscar categoria ou subcategoria..." autocomplete="off" />
            <button type="button" id="catSearchClear" class="cat-search-clear d-none" title="Limpar busca">
                <i data-lucide="x"></i>
            </button>
        </div>
    </div>

    <div class="cat-filter-summary" id="catFilterSummary" aria-live="polite"></div>

    <!-- ==================== CATEGORIAS SEPARADAS POR TIPO ==================== -->
    <div class="categories-grid">
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
                            <span id="receitasCount">0</span>
                            <span class="category-count-divider">de</span>
                            <span id="receitasTotalCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar categorias"
                    data-action="refresh-categorias">
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
                            <span id="despesasCount">0</span>
                            <span class="category-count-divider">de</span>
                            <span id="despesasTotalCount">0</span> categorias
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn refresh-btn" title="Atualizar categorias"
                    data-action="refresh-categorias">
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

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
