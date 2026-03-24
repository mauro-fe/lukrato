<div class="cont-page">
    <section class="contas-hero" id="contasHero" aria-live="polite">
        <div class="contas-hero-main">
            <span class="contas-section-eyebrow">Visao consolidada</span>
            <h1 class="contas-hero-title">Seu dinheiro total</h1>
            <div class="contas-hero-value" id="saldoTotal">R$ 0,00</div>

            <div class="contas-hero-meta">
                <article class="contas-hero-meta-item">
                    <span class="contas-hero-meta-label">Distribuido em</span>
                    <strong class="contas-hero-meta-value" id="totalContas">0 contas</strong>
                </article>
                <article class="contas-hero-meta-item">
                    <span class="contas-hero-meta-label" id="saldoReservas">R$ 0,00</span>
                    <strong class="contas-hero-meta-value">estao guardados</strong>
                </article>
            </div>

            <div class="contas-hero-insight">
                <div class="contas-hero-insight-icon">
                    <i data-lucide="sparkles"></i>
                </div>
                <div class="contas-hero-insight-copy">
                    <p class="contas-hero-insight-title" id="contasContextTitle">Sua maior parte do dinheiro aparece aqui.</p>
                    <p class="contas-hero-insight-text" id="contasContextDescription">
                        Assim que suas contas carregarem, voce ve onde o dinheiro esta concentrado e se vale distribuir melhor.
                    </p>
                </div>
            </div>
        </div>

        <aside class="contas-hero-side">
            <article class="contas-side-card contas-side-card--featured">
                <span class="contas-side-card-label">Conta principal</span>
                <strong class="contas-side-card-title" id="contasMainAccountName">Nenhuma conta</strong>
                <span class="contas-side-card-value" id="contasMainAccountValue">R$ 0,00</span>
                <span class="contas-side-card-copy" id="contasMainAccountShare">0% do seu dinheiro esta aqui</span>
            </article>

            <article class="contas-side-card">
                <span class="contas-side-card-label">Reserva acumulada</span>
                <strong class="contas-side-card-title" id="contasReserveLabel">R$ 0,00 guardados</strong>
                <span class="contas-side-card-copy" id="contasReserveShare">0% do total esta em reserva</span>
            </article>
        </aside>
    </section>

    <section class="contas-distribution-card" id="contasDistributionCard" aria-live="polite">
        <div class="contas-section-heading">
            <div>
                <span class="contas-section-eyebrow">Distribuicao</span>
                <h2 class="contas-section-title">Onde seu dinheiro esta</h2>
                <p class="contas-section-copy" id="contasDistributionSummary">
                    Veja como seus saldos positivos estao distribuidos por tipo de conta.
                </p>
            </div>
        </div>

        <div class="contas-distribution-list" id="contasDistributionList">
            <div class="contas-distribution-empty">
                <i data-lucide="wallet"></i>
                <span>Assim que houver saldo nas contas, a distribuicao aparece aqui.</span>
            </div>
        </div>
    </section>

    <div class="lk-accounts-wrap" data-aos="fade-up">
        <div class="lk-acc-header">
            <div class="lk-acc-heading">
                <span class="contas-section-eyebrow">Contas</span>
                <h2 class="contas-section-title" id="contasListTitle">Suas contas ativas</h2>
                <p class="contas-section-copy" id="contasListDescription">
                    A conta com maior saldo aparece primeiro para facilitar sua leitura.
                </p>
            </div>

            <div class="lk-acc-header-controls">
                <div class="lk-acc-actions">
                    <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                        <i data-lucide="plus"></i> Nova conta
                    </button>

                    <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                        <i data-lucide="refresh-cw"></i>
                    </button>
                </div>

                <div class="lk-acc-right">
                    <div class="view-toggle" id="viewToggle">
                        <button class="view-btn active" data-view="grid" title="Visualizacao em cards">
                            <i data-lucide="layout-grid"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="Visualizacao compacta">
                            <i data-lucide="list"></i>
                        </button>
                    </div>

                    <a class="btn btn-light" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                        <i data-lucide="archive"></i> Arquivadas
                    </a>
                </div>
            </div>
        </div>

        <div class="contas-toolbar">
            <div class="contas-search-wrapper">
                <i data-lucide="search" class="contas-search-icon"></i>
                <input type="text" id="contasSearchInput" class="contas-search-input"
                    placeholder="Buscar conta, instituicao ou tipo..." autocomplete="off" />
                <button type="button" id="contasSearchClear" class="contas-search-clear d-none" title="Limpar busca">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="contas-filter-wrapper">
                <label class="visually-hidden" for="contasTypeFilter">Filtrar por tipo</label>
                <select id="contasTypeFilter" class="contas-type-filter" aria-label="Filtrar contas por tipo">
                    <option value="all">Todos os tipos</option>
                    <option value="conta_corrente">Conta corrente</option>
                    <option value="conta_poupanca">Poupanca</option>
                    <option value="conta_investimento">Reserva</option>
                    <option value="carteira_digital">Carteira digital</option>
                    <option value="dinheiro">Dinheiro</option>
                </select>
            </div>
        </div>

        <div class="contas-filter-summary" id="contasFilterSummary" aria-live="polite"></div>

        <div class="lk-card">
            <div id="contasListHeader" class="contas-list-header">
                <span></span>
                <span>Conta</span>
                <span>% do total</span>
                <span>Valor</span>
                <span>Acoes</span>
            </div>

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
                    <h3 style="color:var(--color-text);margin-bottom:0.5rem;">JavaScript necessario</h3>
                    <p style="color:var(--color-text-muted);">
                        Ative o JavaScript no seu navegador para visualizar suas contas.
                    </p>
                </div>
            </noscript>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/modals/modal-contas.php'; ?>
