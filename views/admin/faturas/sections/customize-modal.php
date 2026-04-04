<div class="fat-customize-trigger">
    <button class="fat-customize-open surface-card" id="btnCustomizeFaturas" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="fat-customize-overlay" id="faturasCustomizeModalOverlay" style="display:none;">
    <div class="fat-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="faturasCustomizeModalTitle">
        <div class="fat-customize-header">
            <h3 class="fat-customize-title" id="faturasCustomizeModalTitle">Personalizar faturas</h3>
            <button class="fat-customize-close" id="btnCloseCustomizeFaturas" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="fat-customize-body">
            <p class="fat-customize-desc">Comece no modo essencial e habilite blocos quando quiser.</p>

            <div class="fat-customize-presets" role="group" aria-label="Preset de visualizacao">
                <button class="fat-customize-preset" id="btnPresetEssencialFaturas" type="button">Modo
                    essencial</button>
                <button class="fat-customize-preset" id="btnPresetCompletoFaturas" type="button">Modo completo</button>
            </div>

            <div class="fat-customize-group">
                <p class="fat-customize-group-title">Blocos da tela</p>
                <label class="fat-customize-toggle">
                    <span>Hero de contexto</span>
                    <input type="checkbox" id="toggleFaturasHero" checked>
                </label>
                <label class="fat-customize-toggle">
                    <span>Painel de filtros</span>
                    <input type="checkbox" id="toggleFaturasFiltros" checked>
                </label>
                <label class="fat-customize-toggle">
                    <span>Toggle de visualizacao</span>
                    <input type="checkbox" id="toggleFaturasViewToggle" checked>
                </label>
            </div>
        </div>

        <div class="fat-customize-footer">
            <button class="fat-customize-save" id="btnSaveCustomizeFaturas" type="button">Salvar</button>
        </div>
    </div>
</div>