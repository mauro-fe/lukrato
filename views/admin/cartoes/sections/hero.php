<!-- ============================================================
         HERO - Limite consolidado (estilo dashboard-hero)
         ============================================================ -->
<section class="cart-hero surface-card surface-card--interactive" id="cartoesHero" aria-live="polite">
    <div class="cart-hero__content">
        <span class="cart-hero__eyebrow">Visão consolidada</span>
        <h1 class="cart-hero__title">Seus cartões de crédito</h1>
        <p class="cart-hero__subtitle">
            Acompanhe limite, faturas pendentes e os cartões que merecem atenção primeiro.
        </p>
    </div>
    <div class="cart-hero__actions">
        <a
            class="btn btn-secondary"
            href="<?= BASE_URL ?>importacoes?import_target=cartao"
            data-cartoes-import-ofx-link>
            <i data-lucide="upload"></i>
            Importar fatura
        </a>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico?import_target=cartao">
            <i data-lucide="history"></i>
            Histórico de importações
        </a>
    </div>
</section>