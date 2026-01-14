<!-- Incluir o CSS dos cartões modernos -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cartoes-modern.css?v=<?= time() ?>">

<div class="cont-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lk-accounts-wrap" style="margin-bottom: 2rem;" data-aos="fade-down">
        <div class="lk-acc-header"
            style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.75rem; margin: 0; color: var(--color-text); font-weight: 700;">
                <i class="fas fa-archive" style="color: var(--color-primary);"></i>
                Cartões Arquivados
            </h1>
            <a class="btn btn-light" href="<?= BASE_URL ?>cartoes" aria-label="Voltar para cartões">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid" id="statsContainer" style="margin-bottom: 2rem;">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-archive"></i>
            </div>
            <div>
                <div class="stat-value" id="totalArquivados" aria-live="polite">0</div>
                <div class="stat-label">Cartões Arquivados</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div>
                <div class="stat-value" id="limiteTotal" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Limite Total (Arquivados)</div>
            </div>
        </div>
    </div>

    <!-- ==================== LISTA DE CARTÕES ARQUIVADOS ==================== -->
    <div class="cartoes-container" data-aos="fade-up">
        <div class="cartoes-grid" id="archivedGrid" aria-live="polite" aria-busy="false">
            <!-- Skeleton loader inicial -->
            <div class="card-skeleton" aria-hidden="true"></div>
            <div class="card-skeleton" aria-hidden="true"></div>
            <div class="card-skeleton" aria-hidden="true"></div>
        </div>
    </div>
</div>