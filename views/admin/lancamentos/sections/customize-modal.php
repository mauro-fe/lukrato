<div class="lan-customize-trigger">
    <button class="modern-btn surface-card" id="btnCustomizeLancamentos" type="button">
        <i data-lucide="sliders-horizontal"></i><span>Personalizar tela</span>
    </button>
</div>

<div class="lan-customize-overlay" id="lanCustomizeModalOverlay" style="display:none;">
    <div class="lan-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="lanCustomizeModalTitle">
        <div class="lan-customize-header">
            <h3 class="lan-customize-title" id="lanCustomizeModalTitle">Personalizar lancamentos</h3>
            <button class="lan-customize-close" id="btnCloseCustomizeLancamentos" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="lan-customize-body">
            <p class="lan-customize-desc">Comece no modo essencial e ative blocos quando fizer sentido.</p>

            <div class="lan-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="modern-btn" id="btnPresetEssencialLancamentos" type="button">Modo essencial</button>
                <button class="modern-btn" id="btnPresetCompletoLancamentos" type="button">Modo completo</button>
            </div>

            <div class="lan-customize-group">
                <p class="lan-customize-group-title">Blocos da pagina</p>
                <label class="lan-customize-toggle">
                    <span>Fluxo financeiro</span>
                    <input type="checkbox" id="toggleLanHero" checked>
                </label>
                <label class="lan-customize-toggle">
                    <span>Resumo do periodo</span>
                    <input type="checkbox" id="toggleLanSummary" checked>
                </label>
                <label class="lan-customize-toggle">
                    <span>Exportação</span>
                    <input type="checkbox" id="toggleLanExport" checked>
                </label>
                <label class="lan-customize-toggle">
                    <span>Filtros</span>
                    <input type="checkbox" id="toggleLanFilters" checked>
                </label>
            </div>
        </div>
        <div class="lan-customize-footer">
            <button class="modern-btn primary" id="btnSaveCustomizeLancamentos" type="button">Salvar</button>
        </div>
    </div>
</div>