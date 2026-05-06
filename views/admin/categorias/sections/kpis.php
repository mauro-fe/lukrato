<!-- ==================== KPI CARDS (estilo dashboard) ==================== -->
<div class="cat-kpis" id="categoriasKpis" <?= !$showCategoriasKpis ? ' style="display:none;"' : '' ?>>
    <article class="cat-kpi surface-card surface-card--interactive">
        <div class="cat-kpi__icon cat-kpi__icon--total">
            <i data-lucide="layers"></i>
        </div>
        <div class="cat-kpi__body">
            <span class="cat-kpi__label">Categorias</span>
            <span class="cat-kpi__value" id="catTotalCount">0</span>
        </div>
    </article>
    <article class="cat-kpi surface-card surface-card--interactive">
        <div class="cat-kpi__icon cat-kpi__icon--sub">
            <i data-lucide="git-branch"></i>
        </div>
        <div class="cat-kpi__body">
            <span class="cat-kpi__label">Subcategorias</span>
            <span class="cat-kpi__value" id="catSubCount">0</span>
        </div>
    </article>
    <article class="cat-kpi surface-card surface-card--interactive">
        <div class="cat-kpi__icon cat-kpi__icon--budget">
            <i data-lucide="pie-chart"></i>
        </div>
        <div class="cat-kpi__body">
            <span class="cat-kpi__label">Com orçamento</span>
            <span class="cat-kpi__value" id="catBudgetCount">0</span>
        </div>
    </article>
    <article class="cat-kpi surface-card surface-card--interactive">
        <div class="cat-kpi__icon cat-kpi__icon--own">
            <i data-lucide="user"></i>
        </div>
        <div class="cat-kpi__body">
            <span class="cat-kpi__label">Personalizadas</span>
            <span class="cat-kpi__value" id="catOwnCount">0</span>
        </div>
    </article>
</div>