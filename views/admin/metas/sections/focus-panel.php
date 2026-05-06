<!-- ==================== FOCO DO MOMENTO ==================== -->
<section class="met-focus-panel surface-card surface-card--interactive" id="metFocusPanel" data-aos="fade-up"
    <?= !$showMetasFocus ? 'style="display:none;"' : '' ?>
    data-aos-delay="80">
    <div class="met-focus-panel__main">
        <div class="met-focus-panel__eyebrow">
            <i data-lucide="sparkles"></i>
            <span>Seu próximo passo</span>
        </div>
        <div class="met-focus-panel__content" id="metFocusContent">
            <div class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Analisando suas metas...</p>
            </div>
        </div>
    </div>
    <div class="met-focus-panel__stats" id="metFocusStats">
        <div class="met-focus-stat">
            <span class="met-focus-stat__label">Em risco</span>
            <strong class="met-focus-stat__value">--</strong>
        </div>
        <div class="met-focus-stat">
            <span class="met-focus-stat__label">Guardar sugerido</span>
            <strong class="met-focus-stat__value">--</strong>
        </div>
        <div class="met-focus-stat">
            <span class="met-focus-stat__label">Concluídas</span>
            <strong class="met-focus-stat__value">--</strong>
        </div>
    </div>
</section>