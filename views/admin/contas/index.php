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
                <div class="stat-label">Saldo Contas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i data-lucide="trending-up" style="color: var(--color-success)"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoInvestimentos" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Investimentos</div>
            </div>
        </div>

    </div>

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
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
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
<?php include __DIR__ . '/../partials/modals/modal-lancamento-v2.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->