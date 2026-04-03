<section class="lk-filters-section collapsed surface-card" id="lanFiltersSection" data-aos="fade-up"
    data-aos-delay="200" aria-label="Filtros de lancamentos">
    <div class="lk-filters-header">
        <div class="lk-filters-title-group">
            <div class="lk-filters-icon">
                <i data-lucide="sliders-horizontal"></i>
            </div>
            <div class="lk-filters-text">
                <h3 class="lk-filters-title">Filtrar transações</h3>
                <span class="lk-filters-subtitle">Busca unificada, chips compactos e periodo rapido.</span>
            </div>
        </div>
        <button type="button" class="lk-filters-toggle" id="btnToggleLanFilters" aria-label="Expandir filtros">
            <i data-lucide="chevron-down"></i>
        </button>
    </div>

    <div class="lk-filters-body" id="lanFiltersBody">
        <div class="lk-filter-search">
            <i data-lucide="search" class="lk-filter-search-icon"></i>
            <input type="text" id="filtroTexto" class="lk-filter-search-input"
                placeholder="Buscar descricao, categoria ou conta..." aria-label="Buscar lancamentos">
        </div>

        <div class="lk-filters-grid">
            <div class="lk-filter-item">
                <label class="lk-filter-label" for="filtroTipo">
                    <i data-lucide="tag"></i>
                    Tipo
                </label>
                <div class="lk-select-wrapper">
                    <select id="filtroTipo" class="lk-filter-select" aria-label="Filtrar por tipo">
                        <option value="">Tipo</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                </div>
            </div>
            <div class="lk-filter-item">
                <label class="lk-filter-label" for="filtroCategoria">
                    <i data-lucide="folder"></i>
                    Categoria
                </label>
                <div class="lk-select-wrapper">
                    <select id="filtroCategoria" class="lk-filter-select" aria-label="Filtrar por categoria">
                        <option value="">Categoria</option>
                        <option value="none">Sem categoria</option>
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                </div>
            </div>
            <div class="lk-filter-item">
                <label class="lk-filter-label" for="filtroConta">
                    <i data-lucide="wallet"></i>
                    Conta
                </label>
                <div class="lk-select-wrapper">
                    <select id="filtroConta" class="lk-filter-select" aria-label="Filtrar por conta">
                        <option value="">Conta</option>
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                </div>
            </div>
            <div class="lk-filter-item">
                <label class="lk-filter-label" for="filtroStatus">
                    <i data-lucide="circle-check"></i>
                    Status
                </label>
                <div class="lk-select-wrapper">
                    <select id="filtroStatus" class="lk-filter-select" aria-label="Filtrar por status">
                        <option value="">Status</option>
                        <option value="pago">Pagos</option>
                        <option value="pendente">Pendentes</option>
                    </select>
                    <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                </div>
            </div>
        </div>

        <div class="lk-filters-period-row">
            <div class="lk-period-presets" aria-label="Atalhos de periodo">
                <button type="button" class="lk-period-preset-btn" data-period-preset="today">Hoje</button>
                <button type="button" class="lk-period-preset-btn" data-period-preset="7">7 dias</button>
                <button type="button" class="lk-period-preset-btn" data-period-preset="30">30 dias</button>
                <button type="button" class="lk-period-month-btn" id="btnUsarMesDoTopo">
                    <i data-lucide="calendar-sync"></i><span>Usar mes do topo</span>
                </button>
                <button type="button" class="lk-period-month-btn is-secondary" id="btnToggleAdvancedPeriod"
                    aria-expanded="false" aria-controls="advancedPeriodPanel">
                    <i data-lucide="sliders-horizontal"></i><span>Personalizar</span>
                </button>
            </div>
        </div>

        <div class="lk-period-advanced" id="advancedPeriodPanel" hidden>
            <div class="lk-period-filter" aria-label="Periodo personalizado da listagem">
                <div class="lk-period-filter-label">
                    <i data-lucide="calendar-range"></i><span>Periodo personalizado</span>
                </div>
                <div class="lk-period-inputs">
                    <div class="lk-period-field">
                        <label for="filtroDataInicio" class="lk-period-field-label">Data inicial</label>
                        <input type="date" id="filtroDataInicio" class="lk-filter-date-input"
                            aria-label="Data inicial da listagem">
                    </div>
                    <div class="lk-period-field">
                        <label for="filtroDataFim" class="lk-period-field-label">Data final</label>
                        <input type="date" id="filtroDataFim" class="lk-filter-date-input"
                            aria-label="Data final da listagem">
                    </div>
                </div>
            </div>
        </div>

        <div class="lk-active-filters" id="activeFilterBadges" style="display:none;" aria-live="polite"></div>

        <div class="lk-filters-actions">
            <button id="btnLimparFiltros" type="button" class="lk-filter-clear-btn" aria-label="Limpar filtros"
                title="Limpar filtros">
                <i data-lucide="x"></i><span>Limpar</span>
            </button>
        </div>
    </div>
</section>