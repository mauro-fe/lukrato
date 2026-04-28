<!-- ============================================================
         KPIs — 4 indicadores (estilo dash-kpis)
         ============================================================ -->
<section class="cart-kpis" id="cartoesKpis">
    <article class="stat-card surface-card surface-card--interactive" data-stat="limite">
        <div class="stat-card__summary">
            <span class="stat-summary__eyebrow">Visão consolidada</span>
            <h2 class="stat-summary__title">Seu limite total</h2>
            <p class="stat-summary__subtitle">Disponível, uso e cartões que pedem atenção.</p>
        </div>

        <div class="stat-card__metric">
            <div class="stat-icon stat-icon--primary">
                <i data-lucide="hand-coins"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Limite total</span>
                <strong class="stat-value" id="statLimiteTotal">R$ 0,00</strong>
            </div>
        </div>
    </article>

    <article class="stat-card surface-card surface-card--interactive" data-stat="total">
        <div class="stat-icon">
            <i data-lucide="credit-card"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total de cartões</span>
            <strong class="stat-value" id="totalCartoes">0</strong>
        </div>
    </article>

    <article class="stat-card surface-card surface-card--interactive" data-stat="disponivel">
        <div class="stat-icon stat-icon--success">
            <i data-lucide="circle-check"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Limite disponível</span>
            <strong class="stat-value success" id="limiteDisponivel">R$ 0,00</strong>
        </div>
    </article>

    <article class="stat-card surface-card surface-card--interactive" data-stat="utilizado">
        <div class="stat-icon stat-icon--warning">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Limite utilizado</span>
            <strong class="stat-value warning" id="limiteUtilizado">R$ 0,00</strong>
        </div>
    </article>
</section>