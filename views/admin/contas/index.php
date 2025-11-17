<div class="cont-page">
    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid pt-5" id="statsContainer" role="region" aria-label="Estatísticas das contas">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-value" id="totalContas" aria-live="polite">0</div>
            <div class="stat-label">Total de Contas</div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-value" id="saldoTotal" aria-live="polite">R$ 0,00</div>
            <div class="stat-label">Saldo Total</div>
        </div>
    </div>

    <!-- ==================== LISTA DE CONTAS ==================== -->
    <div class="lk-accounts-wrap" data-aos="fade-up">
        <!-- Header com ações -->
        <div class="lk-acc-header">
            <div class="lk-acc-actions">
                <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                    <i class="fas fa-plus"></i> Nova Conta
                </button>
                <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <a class="btn btn-light" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                <i class="fas fa-archive"></i> Arquivadas
            </a>
        </div>

        <!-- Cards das contas -->
        <div class="lk-card">
            <div class="acc-grid" id="accountsGrid" aria-live="polite" aria-busy="false">
                <!-- Skeleton loader inicial -->
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal_contas.php'; ?>