<div class="cat-customize-trigger">
    <button class="cat-customize-open surface-card" id="btnCustomizeCategorias" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="cat-customize-overlay" id="categoriasCustomizeModalOverlay" style="display:none;">
    <div class="cat-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="categoriasCustomizeModalTitle">
        <div class="cat-customize-header">
            <h3 class="cat-customize-title" id="categoriasCustomizeModalTitle">Personalizar categorias</h3>
            <button class="cat-customize-close" id="btnCloseCustomizeCategorias" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="cat-customize-body">
            <p class="cat-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="cat-customize-presets" role="group" aria-label="Preset de visualizacao">
                <button class="cat-customize-preset" id="btnPresetEssencialCategorias" type="button">Modo
                    essencial</button>
                <button class="cat-customize-preset" id="btnPresetCompletoCategorias" type="button">Modo
                    completo</button>
            </div>

            <div class="cat-customize-group">
                <p class="cat-customize-group-title">Blocos da tela</p>
                <label class="cat-customize-toggle">
                    <span>Cards de KPI</span>
                    <input type="checkbox" id="toggleCategoriasKpis" checked>
                </label>
                <label class="cat-customize-toggle">
                    <span>Card de criacao</span>
                    <input type="checkbox" id="toggleCategoriasCreateCard" checked>
                </label>
                <label class="cat-customize-toggle">
                    <span>Contexto e busca</span>
                    <input type="checkbox" id="toggleCategoriasContextCard" checked>
                </label>
            </div>
        </div>

        <div class="cat-customize-footer">
            <button class="cat-customize-save" id="btnSaveCustomizeCategorias" type="button">Salvar</button>
        </div>
    </div>
</div>