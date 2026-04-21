<div class="met-customize-overlay" id="metasCustomizeModalOverlay" style="display:none;">
    <div class="met-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="metasCustomizeModalTitle">
        <div class="met-customize-header">
            <h3 class="met-customize-title" id="metasCustomizeModalTitle">Personalizar metas</h3>
            <button class="met-customize-close" id="btnCloseCustomizeMetas" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="met-customize-body">
            <p class="met-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="met-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="met-customize-preset" id="btnPresetEssencialMetas" type="button">Modo essencial</button>
                <button class="met-customize-preset" id="btnPresetCompletoMetas" type="button">Modo completo</button>
            </div>

            <div class="met-customize-group">
                <p class="met-customize-group-title">Blocos da tela</p>
                <label class="met-customize-toggle">
                    <span>Resumo de metas</span>
                    <input type="checkbox" id="toggleMetasSummary" checked>
                </label>
                <label class="met-customize-toggle">
                    <span>Foco do momento</span>
                    <input type="checkbox" id="toggleMetasFocus" checked>
                </label>
                <label class="met-customize-toggle">
                    <span>Barra de filtros</span>
                    <input type="checkbox" id="toggleMetasToolbar" checked>
                </label>
            </div>
        </div>

        <div class="met-customize-footer">
            <button class="met-customize-save" id="btnSaveCustomizeMetas" type="button">Salvar</button>
        </div>
    </div>
</div>