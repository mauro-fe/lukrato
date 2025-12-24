<div class="cont-page">
    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid pt-5" id="statsContainer" role="region" aria-label="Estatísticas das contas">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <div class="stat-value" id="totalContas" aria-live="polite">0</div>
                <div class="stat-label">Total de Contas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoTotal" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Saldo Total</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div>
                <div class="stat-value" id="totalCartoes" aria-live="polite">0</div>
                <div class="stat-label">Cartões de Crédito</div>
            </div>
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
                <button class="btn btn-ghost" id="btnNovoCartao" aria-label="Adicionar cartão de crédito">
                    <i class="fas fa-credit-card"></i> Novo Cartão
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
<?php include __DIR__ . '/../partials/modals/modal_contas_v2.php'; ?>
<?php include __DIR__ . '/../partials/modals/modal_cartoes_v2.php'; ?>
<?php include __DIR__ . '/../partials/modals/modal_lancamento_v2.php'; ?>

<!-- ==================== SCRIPTS ==================== -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/contas-modern.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modal-contas-modern.css?v=<?= time() ?>">
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    
    // Limpar qualquer código JavaScript antigo em cache
    if ('caches' in window) {
        caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
    }
</script>
<script src="<?= BASE_URL ?>assets/js/contas-manager.js?v=<?= md5_file(__DIR__ . '/../../../public/assets/js/contas-manager.js') ?>"></script>