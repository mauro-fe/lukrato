<div class="cont-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lk-accounts-wrap" style="margin-bottom: 2rem;" data-aos="fade-down">
        <div class="lk-acc-header"
            style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.75rem; margin: 0; color: var(--color-text); font-weight: 700;">
                <i data-lucide="archive" style="color: var(--color-primary);"></i>
                Contas Arquivadas
            </h1>
            <a class="btn btn-light" href="<?= BASE_URL ?>contas" aria-label="Voltar para contas">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid" id="statsContainer" style="margin-bottom: 2rem;">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i data-lucide="archive"></i>
            </div>
            <div>
                <div class="stat-value" id="totalArquivadas" aria-live="polite">0</div>
                <div class="stat-label">Contas Arquivadas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i data-lucide="coins"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoArquivado" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Saldo Total (Arquivadas)</div>
            </div>
        </div>
    </div>

    <!-- ==================== LISTA DE CONTAS ARQUIVADAS ==================== -->
    <div class="lk-accounts-wrap" data-aos="fade-up">
        <div class="lk-card">
            <div class="acc-grid" id="archivedGrid" aria-live="polite" aria-busy="false">
                <!-- Skeleton loader inicial -->
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
                <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</div>

<!-- CSS carregado via loadPageCss() no header -->
<!-- JS carregado via loadPageJs() + Vite -->