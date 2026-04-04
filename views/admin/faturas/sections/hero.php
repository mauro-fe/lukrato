<!-- ============================================================
         HERO - Visão geral (estilo dashboard-hero)
         ============================================================ -->
<section class="fat-hero surface-card surface-card--interactive" id="faturasHero">
    <div class="fat-hero__content">
        <span class="fat-hero__eyebrow">Faturas de cartão</span>
        <h1 class="fat-hero__title">Suas faturas</h1>
        <p class="fat-hero__subtitle">
            Acompanhe o valor, vencimento e progresso de pagamento de cada fatura dos seus cartões.
        </p>
    </div>
    <div class="fat-hero__actions">
        <a
            class="btn btn-secondary"
            href="<?= BASE_URL ?>importacoes?import_target=cartao&source_type=ofx"
            data-faturas-import-ofx-link
        >
            <i data-lucide="upload"></i>
            Importar OFX de fatura
        </a>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico?import_target=cartao">
            <i data-lucide="history"></i>
            Histórico de importações
        </a>
    </div>
</section>
