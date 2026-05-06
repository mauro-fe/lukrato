<!-- ==================== FOCO DO PERIODO ==================== -->
<section class="orc-focus-panel surface-card surface-card--interactive" id="orcFocusPanel" data-aos="fade-up"
    <?= !$showOrcFocus ? 'style="display:none;"' : '' ?>
    data-aos-delay="80">
    <div class="orc-focus-panel__main">
        <div class="orc-focus-panel__eyebrow">
            <i data-lucide="sparkles"></i>
            <span>Onde agir agora</span>
        </div>
        <div class="orc-focus-panel__content" id="orcFocusContent">
            <div class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Mapeando seus pontos de atencao...</p>
            </div>
        </div>
    </div>
    <div class="orc-focus-panel__stats" id="orcFocusStats">
        <div class="orc-focus-stat">
            <span class="orc-focus-stat__label">Em alerta</span>
            <strong class="orc-focus-stat__value">--</strong>
        </div>
        <div class="orc-focus-stat">
            <span class="orc-focus-stat__label">Estourados</span>
            <strong class="orc-focus-stat__value">--</strong>
        </div>
        <div class="orc-focus-stat">
            <span class="orc-focus-stat__label">Uso geral</span>
            <strong class="orc-focus-stat__value">--</strong>
        </div>
    </div>
</section>