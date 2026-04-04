<div class="orc-customize-trigger">
    <button class="orc-customize-open surface-card" id="btnCustomizeOrcamento" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="orc-customize-overlay" id="orcamentoCustomizeModalOverlay" style="display:none;">
    <div class="orc-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="orcamentoCustomizeModalTitle">
        <div class="orc-customize-header">
            <h3 class="orc-customize-title" id="orcamentoCustomizeModalTitle">Personalizar orcamento</h3>
            <button class="orc-customize-close" id="btnCloseCustomizeOrcamento" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="orc-customize-body">
            <p class="orc-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="orc-customize-presets" role="group" aria-label="Preset de visualizacao">
                <button class="orc-customize-preset" id="btnPresetEssencialOrcamento" type="button">Modo
                    essencial</button>
                <button class="orc-customize-preset" id="btnPresetCompletoOrcamento" type="button">Modo
                    completo</button>
            </div>

            <div class="orc-customize-group">
                <p class="orc-customize-group-title">Blocos da tela</p>
                <label class="orc-customize-toggle">
                    <span>Cards de resumo</span>
                    <input type="checkbox" id="toggleOrcSummary" checked>
                </label>
                <label class="orc-customize-toggle">
                    <span>Foco do periodo</span>
                    <input type="checkbox" id="toggleOrcFocus" checked>
                </label>
                <label class="orc-customize-toggle">
                    <span>Barra de filtros</span>
                    <input type="checkbox" id="toggleOrcToolbar" checked>
                </label>
            </div>
        </div>

        <div class="orc-customize-footer">
            <button class="orc-customize-save" id="btnSaveCustomizeOrcamento" type="button">Salvar</button>
        </div>
    </div>
</div>