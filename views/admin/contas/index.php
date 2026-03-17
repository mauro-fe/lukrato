<div class="cont-page">
    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid pt-5" id="statsContainer" role="region" aria-label="Estatísticas das contas">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i data-lucide="wallet" style="color: var(--color-primary)"></i>
            </div>
            <div>
                <div class="stat-value" id="totalContas" aria-live="polite">0</div>
                <div class="stat-label">Total de Contas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i data-lucide="coins"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoTotal" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Saldo Atual Total</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i data-lucide="piggy-bank" style="color: var(--color-success)"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoReservas" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Reservas Atuais</div>
            </div>
        </div>

    </div>

    <section class="contas-context-card" id="contasContextCard" aria-live="polite">
        <div class="contas-context-copy">
            <p class="contas-context-kicker">Saldo atual consolidado</p>
            <h3 class="contas-context-title" id="contasContextTitle">Suas contas ativas em tempo real</h3>
            <p class="contas-context-description" id="contasContextDescription">
                Esta página mostra a posição atual das contas ativas. Para análises por período, use os relatórios.
            </p>
        </div>

        <div class="contas-context-metrics">
            <article class="contas-context-metric">
                <span class="contas-context-metric-value" id="contasNegativasCount">0</span>
                <span class="contas-context-metric-label">Com saldo negativo</span>
            </article>
            <article class="contas-context-metric">
                <span class="contas-context-metric-value" id="contasPositivasCount">0</span>
                <span class="contas-context-metric-label">Com saldo positivo</span>
            </article>
        </div>

        <div class="contas-context-chips" id="contasContextChips"></div>
    </section>

    <!-- ==================== LISTA DE CONTAS ==================== -->
    <div class="lk-accounts-wrap" data-aos="fade-up">
        <!-- Header com ações -->
        <div class="lk-acc-header">
            <div class="lk-acc-actions">
                <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                    <i data-lucide="plus"></i> Nova Conta
                </button>

                <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>

            <div class="lk-acc-right">
                <!-- View Toggle -->
                <div class="view-toggle" id="viewToggle">
                    <button class="view-btn active" data-view="grid" title="Visualização em cards">
                        <i data-lucide="layout-grid"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="Visualização em lista">
                        <i data-lucide="list"></i>
                    </button>
                </div>

                <a class="btn btn-light" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                    <i data-lucide="archive"></i> Arquivadas
                </a>
            </div>
        </div>

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
                    <option value="carteira_digital">Carteira digital</option>
                    <option value="dinheiro">Dinheiro</option>
                </select>
            </div>
        </div>

        <div class="contas-filter-summary" id="contasFilterSummary" aria-live="polite"></div>

        <!-- Cards das contas -->
        <div class="lk-card">
            <!-- Headers da lista (visível apenas em modo lista) -->
            <div id="contasListHeader" class="contas-list-header">
                <span></span>
                <span>Conta</span>
                <span>Instituição</span>
                <span>Tipo</span>
                <span>Saldo</span>
                <span>Ações</span>
            </div>
            <div class="acc-grid" id="accountsGrid" aria-live="polite" aria-busy="true">
                <!-- Skeleton loader inicial -->
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            </div>
            <!-- Fallback caso JS não carregue -->
            <noscript>
                <div class="empty-state" style="text-align:center;padding:3rem 1rem;">
                    <div class="empty-icon" style="font-size:3rem;margin-bottom:1rem;"><i data-lucide="wallet"
                            style="color:var(--color-primary);"></i></div>
                    <h3 style="color:var(--color-text);margin-bottom:0.5rem;">JavaScript necessário</h3>
                    <p style="color:var(--color-text-muted);">Ative o JavaScript no seu navegador para visualizar suas
                        contas.</p>
                </div>
            </noscript>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal-contas.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
