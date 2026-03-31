<section class="parc-page">

    <!-- ============================================================
         HERO — Visão geral (estilo dashboard-hero)
         ============================================================ -->
    <section class="fat-hero surface-card surface-card--interactive" id="faturasHero">
        <span class="fat-hero__eyebrow">Faturas de cartão</span>
        <h1 class="fat-hero__title">Suas faturas</h1>
        <p class="fat-hero__subtitle">
            Acompanhe o valor, vencimento e progresso de pagamento de cada fatura dos seus cartões.
        </p>
    </section>

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

    <!-- ============================================================
         LISTA DE FATURAS — Seção principal
         ============================================================ -->
    <section class="fat-list-section surface-card">
        <div class="fat-list-header">
            <div class="fat-list-heading">
                <span class="fat-section__eyebrow">Faturas</span>
                <h2 class="fat-section__title">Suas Faturas</h2>
                <p class="fat-section__desc">
                    Faturas pendentes e com valor elevado aparecem em destaque.
                </p>
            </div>

            <div class="fat-list-controls">
                <div class="view-toggle" id="faturasViewToggle">
                    <button class="view-btn active" data-view="grid" title="Visualização em Cards">
                        <i data-lucide="layout-grid"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="Visualização em Lista">
                        <i data-lucide="list"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loadingParcelamentos" class="lk-loading-state" style="display: none;">
            <i data-lucide="loader-2"></i>
            <p>Carregando faturas...</p>
        </div>

        <!-- Headers da lista (visível apenas em modo lista) -->
        <div id="faturasListHeader" class="faturas-list-header">
            <span></span>
            <span>Cartão</span>
            <span>Valor</span>
            <span>Progresso</span>
            <span>Status</span>
            <span>Ações</span>
        </div>

        <!-- Grid de faturas (JS-rendered) -->
        <div id="parcelamentosContainer" class="parcelamentos-grid"></div>

        <!-- Empty state -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="credit-card"></i>
            </div>
            <h3>Nenhuma fatura encontrada</h3>
            <p>Suas faturas de cartão aparecerão aqui automaticamente quando você cadastrar compras parceladas.</p>
            <a href="<?= BASE_URL ?>lancamentos" class="btn-cta">
                <i data-lucide="plus"></i>
                Criar Lançamento Parcelado
            </a>
        </div>
    </section>

    <div class="fat-customize-trigger">
        <button class="fat-customize-open" id="btnCustomizeFaturas" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>

    <div class="fat-customize-overlay" id="faturasCustomizeModalOverlay" style="display:none;">
        <div class="fat-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="faturasCustomizeModalTitle">
            <div class="fat-customize-header">
                <h3 class="fat-customize-title" id="faturasCustomizeModalTitle">Personalizar faturas</h3>
                <button class="fat-customize-close" id="btnCloseCustomizeFaturas" type="button"
                    aria-label="Fechar personalizacao">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="fat-customize-body">
                <p class="fat-customize-desc">Comece no modo essencial e habilite blocos quando quiser.</p>

                <div class="fat-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="fat-customize-preset" id="btnPresetEssencialFaturas" type="button">Modo essencial</button>
                    <button class="fat-customize-preset" id="btnPresetCompletoFaturas" type="button">Modo completo</button>
                </div>

                <div class="fat-customize-group">
                    <p class="fat-customize-group-title">Blocos da tela</p>
                    <label class="fat-customize-toggle">
                        <span>Hero de contexto</span>
                        <input type="checkbox" id="toggleFaturasHero" checked>
                    </label>
                    <label class="fat-customize-toggle">
                        <span>Painel de filtros</span>
                        <input type="checkbox" id="toggleFaturasFiltros" checked>
                    </label>
                    <label class="fat-customize-toggle">
                        <span>Toggle de visualizacao</span>
                        <input type="checkbox" id="toggleFaturasViewToggle" checked>
                    </label>
                </div>
            </div>

            <div class="fat-customize-footer">
                <button class="fat-customize-save" id="btnSaveCustomizeFaturas" type="button">Salvar</button>
            </div>
        </div>
    </div>

</section>

<?php include __DIR__ . '/../partials/modals/modal-detalhes-faturas.php'; ?>

<!-- Styles -->
