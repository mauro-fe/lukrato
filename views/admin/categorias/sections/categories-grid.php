<!-- ==================== CATEGORIAS SEPARADAS POR TIPO ==================== -->
<div class="categories-grid">
    <!-- CATEGORIAS DE RECEITAS -->
    <div class="category-card receitas-card surface-card surface-card--interactive surface-card--clip">
        <div class="category-header receitas">
            <div class="header-content">
                <div class="header-icon">
                    <i data-lucide="trending-up"></i>
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
        </div>

        <div class="category-list" id="receitasList">
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <p>Nenhuma categoria de receita cadastrada</p>
            </div>
        </div>
    </div>

    <!-- CATEGORIAS DE DESPESAS -->
    <div class="category-card despesas-card surface-card surface-card--interactive surface-card--clip">
        <div class="category-header despesas">
            <div class="header-content">
                <div class="header-icon">
                    <i data-lucide="trending-down"></i>
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
        </div>

        <div class="category-list" id="despesasList">
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <p>Nenhuma categoria de despesa cadastrada</p>
            </div>
        </div>
    </div>
</div>