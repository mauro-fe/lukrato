<!-- ============================================================
         FILTROS — Painel colapsável
         ============================================================ -->
<div class="filters-modern collapsed surface-card surface-card--clip" id="faturasFilters">
    <div class="filters-header">
        <div class="filters-title">
            <div class="filters-icon">
                <i data-lucide="sliders-horizontal"></i>
            </div>
            <div class="filters-text">
                <h3>Filtros</h3>
                <span class="filters-subtitle">Refine sua busca</span>
            </div>
        </div>
        <button type="button" class="filters-toggle" id="toggleFilters" aria-label="Expandir filtros">
            <i data-lucide="chevron-down"></i>
        </button>
    </div>

    <div class="filters-body" id="filtersBody">
        <div class="filters-grid">
            <div class="filter-item">
                <label class="filter-label-modern" for="filtroStatus">
                    <i data-lucide="circle-check"></i>
                    Status
                </label>
                <div class="select-wrapper">
                    <select id="filtroStatus" class="filter-select">
                        <option value="">Todos os status</option>
                        <option value="pendente">&#x25F7; Pendentes</option>
                        <option value="parcial">&#x21BB; Parcialmente Pagas</option>
                        <option value="paga">&#x2714; Pagas</option>
                        <option value="cancelado">&#x2718; Canceladas</option>
                    </select>
                    <i data-lucide="chevron-down" class="select-arrow"></i>
                </div>
            </div>

            <div class="filter-item">
                <label class="filter-label-modern" for="filtroCartao">
                    <i data-lucide="credit-card"></i>
                    Cartão
                </label>
                <div class="select-wrapper">
                    <select id="filtroCartao" class="filter-select">
                        <option value="">Todos os cartões</option>
                    </select>
                    <i data-lucide="chevron-down" class="select-arrow"></i>
                </div>
            </div>

            <div class="filter-item">
                <label class="filter-label-modern" for="filtroAno">
                    <i data-lucide="calendar"></i>
                    Ano
                </label>
                <div class="select-wrapper">
                    <select id="filtroAno" class="filter-select">
                        <option value="">Todos os anos</option>
                    </select>
                    <i data-lucide="chevron-down" class="select-arrow"></i>
                </div>
            </div>

            <div class="filter-item">
                <label class="filter-label-modern" for="filtroMes">
                    <i data-lucide="calendar"></i>
                    Mês
                </label>
                <div class="select-wrapper">
                    <select id="filtroMes" class="filter-select">
                        <option value="">Todos os meses</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                    <i data-lucide="chevron-down" class="select-arrow"></i>
                </div>
            </div>
        </div>

        <div class="filters-actions">
            <button type="button" id="btnLimparFiltros" class="btn-filter-clear">
                <i data-lucide="x"></i>
                <span>Limpar</span>
            </button>
            <button type="button" id="btnFiltrar" class="btn-filter-apply">
                <i data-lucide="search"></i>
                <span>Aplicar Filtros</span>
            </button>
        </div>
    </div>

    <div class="active-filters" id="activeFilters" style="display: none;"></div>
</div>