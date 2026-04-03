    <!-- ==================== SEÇÃO: RELATÓRIOS ==================== -->
    <div class="rel-section-panel" id="section-relatorios" role="tabpanel">

        <!-- Tabs de Visualização de Gráficos -->
        <div class="modern-card tabs-card surface-card surface-card--interactive surface-card--clip">
            <div class="tabs-container" role="tablist">
                <div class="tab-group" data-group-label="Mensal">
                    <button class="tab-btn active" data-view="category" role="tab">
                        <i data-lucide="pie-chart"></i>
                        <span>Por Categoria</span>
                    </button>

                    <button class="tab-btn" data-view="balance" role="tab">
                        <i data-lucide="line-chart"></i>
                        <span>Saldo Diário</span>
                    </button>

                    <button class="tab-btn" data-view="comparison" role="tab">
                        <i data-lucide="bar-chart-2"></i>
                        <span>Receitas x Despesas</span>
                    </button>

                    <button class="tab-btn" data-view="accounts" role="tab">
                        <i data-lucide="wallet"></i>
                        <span>Por Conta</span>
                    </button>

                    <button class="tab-btn" data-view="cards" role="tab">
                        <i data-lucide="credit-card"></i>
                        <span>Cartões de Crédito</span>
                    </button>

                    <button class="tab-btn" data-view="evolution" role="tab">
                        <i data-lucide="git-branch"></i>
                        <span>Evolução 12m</span>
                    </button>
                </div>

                <div class="tab-separator"></div>

                <div class="tab-group" data-group-label="Anual">
                    <button class="tab-btn" data-view="annual_summary" role="tab">
                        <i data-lucide="calendar-days"></i>
                        <span>Resumo Anual</span>
                    </button>

                    <button class="tab-btn" data-view="annual_category" role="tab">
                        <i data-lucide="pie-chart"></i>
                        <span>Categoria Anual</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Controles Adicionais -->
        <div class="controls-row" id="relControlsRow">
            <div class="control-group surface-control-box surface-control-box--interactive hidden"
                id="typeSelectWrapper">
                <label for="reportType">
                    <i data-lucide="filter" style="color: var(--color-primary)"></i>
                    Filtrar por
                </label>
                <select id="reportType" class="form-select">
                    <option value="despesas_por_categoria">Despesas por Categoria</option>
                    <option value="receitas_por_categoria">Receitas por Categoria</option>
                </select>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive hidden"
                id="accountSelectWrapper">
                <label for="accountFilter">
                    <i data-lucide="landmark" style="color: var(--color-primary)"></i>
                    Conta
                </label>
                <select id="accountFilter" class="form-select">
                    <option value="">Todas as Contas</option>
                </select>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive" id="clearFiltersWrapper"
                style="display:none; align-items: flex-end;">
                <button type="button" id="btnLimparFiltrosRel" class="btn btn-secondary"
                    title="Resetar filtros para padrão" style="white-space: nowrap;">
                    <i data-lucide="eraser"></i>
                    Limpar Filtros
                </button>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive" id="exportControl"
                style="margin-left: auto; align-items: flex-end;">
                <button type="button" id="exportBtn" class="btn btn-secondary btn-compact-export"
                    <?= !$isPro ? 'disabled title="Recurso PRO"' : '' ?>>
                    <i data-lucide="download"></i>
                    <span>Exportar</span>
                    <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"
                            style="margin-left:4px"><i data-lucide="crown"></i> PRO</span><?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Área de Relatório / Gráfico -->
        <div class="report-filter-summary" id="reportFilterSummary" aria-live="polite"></div>
        <div class="report-scope-note hidden" id="reportScopeNote" aria-live="polite"></div>

        <div class="modern-card report-card surface-card surface-card--interactive surface-card--clip">
            <div class="report-area" id="reportArea">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            </div>
        </div>
    </div>
