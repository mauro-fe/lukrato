<section class="cont-page">

    <!-- ============================================================
         HERO — Saldo consolidado (igual dashboard-hero-section)
         ============================================================ -->
    <section class="cont-hero surface-card surface-card--interactive" id="contasHero" aria-live="polite">
        <span class="cont-hero__eyebrow">Visão consolidada</span>
        <h1 class="cont-hero__title">Seu dinheiro total</h1>
        <div class="cont-hero__balance" id="saldoTotal">R$ 0,00</div>

        <div class="cont-hero__meta">
            <span class="cont-hero__meta-item">
                Distribuído em <strong id="totalContas">0 contas</strong>
            </span>
            <span class="cont-hero__meta-divider">·</span>
            <span class="cont-hero__meta-item">
                <strong id="saldoReservas">R$ 0,00</strong> guardados
            </span>
        </div>

        <div class="cont-hero__insight">
            <i data-lucide="sparkles"></i>
            <div>
                <p class="cont-hero__insight-title" id="contasContextTitle">Sua maior parte do dinheiro aparece aqui.</p>
                <p class="cont-hero__insight-desc" id="contasContextDescription">
                    Assim que suas contas carregarem, você vê onde o dinheiro está concentrado e se vale distribuir melhor.
                </p>
            </div>
        </div>
    </section>

    <!-- ============================================================
         KPIs — Conta principal · Reserva (estilo dash-kpis)
         ============================================================ -->
    <section class="cont-kpis" id="contasKpis">
        <article class="cont-kpi surface-card surface-card--interactive">
            <div class="cont-kpi__icon cont-kpi__icon--primary">
                <i data-lucide="crown"></i>
            </div>
            <div class="cont-kpi__body">
                <span class="cont-kpi__label">Conta principal</span>
                <strong class="cont-kpi__value" id="contasMainAccountName">Nenhuma conta</strong>
                <span class="cont-kpi__sub" id="contasMainAccountValue">R$ 0,00</span>
                <span class="cont-kpi__detail" id="contasMainAccountShare">0% do seu dinheiro está aqui</span>
            </div>
        </article>

        <article class="cont-kpi surface-card surface-card--interactive">
            <div class="cont-kpi__icon cont-kpi__icon--reserve">
                <i data-lucide="shield-check"></i>
            </div>
            <div class="cont-kpi__body">
                <span class="cont-kpi__label">Reserva acumulada</span>
                <strong class="cont-kpi__value" id="contasReserveLabel">R$ 0,00 guardados</strong>
                <span class="cont-kpi__detail" id="contasReserveShare">0% do total está em reserva</span>
            </div>
        </article>
    </section>

    <!-- ============================================================
         DISTRIBUIÇÃO — Onde seu dinheiro está
         ============================================================ -->
    <section class="cont-distribution surface-card surface-card--interactive" id="contasDistributionCard" aria-live="polite">
        <div class="cont-section-header">
            <span class="cont-section__eyebrow">Distribuição</span>
            <h2 class="cont-section__title">Onde seu dinheiro está</h2>
            <p class="cont-section__desc" id="contasDistributionSummary">
                Veja como seus saldos positivos estão distribuídos por tipo de conta.
            </p>
        </div>

        <div class="contas-distribution-list" id="contasDistributionList">
            <div class="contas-distribution-empty">
                <i data-lucide="wallet"></i>
                <span>Assim que houver saldo nas contas, a distribuição aparece aqui.</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
         CONTAS — Lista principal
         ============================================================ -->
    <section class="cont-list-section surface-card">
        <div class="cont-list-header">
            <div class="cont-list-heading">
                <span class="cont-section__eyebrow">Contas</span>
                <h2 class="cont-section__title" id="contasListTitle">Suas contas ativas</h2>
                <p class="cont-section__desc" id="contasListDescription">
                    A conta com maior saldo aparece primeiro para facilitar sua leitura.
                </p>
            </div>

            <div class="cont-list-controls">
                <div class="cont-list-actions">
                    <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                        <i data-lucide="plus"></i> Nova conta
                    </button>
                    <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                        <i data-lucide="refresh-cw"></i>
                    </button>
                </div>

                <div class="cont-list-right">
                    <div class="view-toggle" id="viewToggle">
                        <button class="view-btn active" data-view="grid" title="Visualização em cards">
                            <i data-lucide="layout-grid"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="Visualização compacta">
                            <i data-lucide="list"></i>
                        </button>
                    </div>
                    <a class="btn btn-ghost" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                        <i data-lucide="archive"></i> Arquivadas
                    </a>
                </div>
            </div>
        </div>

        <!-- Toolbar — Busca + Filtro -->
        <div class="contas-toolbar">
            <div class="contas-search-wrapper">
                <i data-lucide="search" class="contas-search-icon"></i>
                <input type="text" id="contasSearchInput" class="contas-search-input"
                    placeholder="Buscar conta, instituição ou tipo..." autocomplete="off" />
                <button type="button" id="contasSearchClear" class="contas-search-clear d-none" title="Limpar busca">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="contas-filter-wrapper">
                <label class="visually-hidden" for="contasTypeFilter">Filtrar por tipo</label>
                <select id="contasTypeFilter" class="contas-type-filter" aria-label="Filtrar contas por tipo">
                    <option value="all">Todos os tipos</option>
                    <option value="conta_corrente">Conta corrente</option>
                    <option value="conta_poupanca">Poupança</option>
                    <option value="conta_investimento">Reserva</option>
                    <option value="carteira_digital">Carteira digital</option>
                    <option value="dinheiro">Dinheiro</option>
                </select>
            </div>
        </div>

        <div class="contas-filter-summary" id="contasFilterSummary" aria-live="polite"></div>

        <!-- List-view header -->
        <div id="contasListHeader" class="contas-list-header">
            <span></span>
            <span>Conta</span>
            <span>% do total</span>
            <span>Valor</span>
            <span>Ações</span>
        </div>

        <!-- Grid de contas -->
        <div class="acc-grid" id="accountsGrid" aria-live="polite" aria-busy="true">
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
        </div>

        <noscript>
            <div class="empty-state" style="text-align:center;padding:3rem 1rem;">
                <div class="empty-icon" style="font-size:3rem;margin-bottom:1rem;">
                    <i data-lucide="wallet" style="color:var(--color-primary);"></i>
                </div>
                <h3 style="color:var(--color-text);margin-bottom:0.5rem;">JavaScript necessário</h3>
                <p style="color:var(--color-text-muted);">Ative o JavaScript no navegador para visualizar suas contas.</p>
            </div>
        </noscript>
    </section>

    <div class="cont-customize-trigger">
        <button class="btn btn-ghost" id="btnCustomizeContas" type="button">
            <i data-lucide="sliders-horizontal"></i> Personalizar tela
        </button>
    </div>

    <div class="cont-customize-overlay" id="contasCustomizeModalOverlay" style="display:none;">
        <div class="cont-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="contasCustomizeModalTitle">
            <div class="cont-customize-header">
                <h3 class="cont-customize-title" id="contasCustomizeModalTitle">Personalizar contas</h3>
                <button class="cont-customize-close" id="btnCloseCustomizeContas" type="button"
                    aria-label="Fechar personalizacao">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="cont-customize-body">
                <p class="cont-customize-desc">Comece no modo essencial e habilite blocos extras quando quiser.</p>

                <div class="cont-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="btn btn-ghost" id="btnPresetEssencialContas" type="button">Modo essencial</button>
                    <button class="btn btn-ghost" id="btnPresetCompletoContas" type="button">Modo completo</button>
                </div>

                <div class="cont-customize-group">
                    <p class="cont-customize-group-title">Blocos da tela</p>
                    <label class="cont-customize-toggle">
                        <span>Hero consolidado</span>
                        <input type="checkbox" id="toggleContasHero" checked>
                    </label>
                    <label class="cont-customize-toggle">
                        <span>Cards de KPI</span>
                        <input type="checkbox" id="toggleContasKpis" checked>
                    </label>
                    <label class="cont-customize-toggle">
                        <span>Distribuicao de saldo</span>
                        <input type="checkbox" id="toggleContasDistribution" checked>
                    </label>
                </div>
            </div>

            <div class="cont-customize-footer">
                <button class="btn btn-primary" id="btnSaveCustomizeContas" type="button">Salvar</button>
            </div>
        </div>
    </div>

</section>

<?php include __DIR__ . '/../partials/modals/modal-contas.php'; ?>
