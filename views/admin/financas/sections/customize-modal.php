<div class="fin-customize-trigger">
    <button class="fin-customize-open" id="btnCustomizeFinancas" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="fin-customize-overlay" id="financasCustomizeModalOverlay" style="display:none;">
    <div class="fin-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="financasCustomizeModalTitle">
        <div class="fin-customize-header">
            <h3 class="fin-customize-title" id="financasCustomizeModalTitle">Personalizar financas</h3>
            <button class="fin-customize-close" id="btnCloseCustomizeFinancas" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="fin-customize-body">
            <p class="fin-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="fin-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="fin-customize-preset" id="btnPresetEssencialFinancas" type="button">Modo
                    essencial</button>
                <button class="fin-customize-preset" id="btnPresetCompletoFinancas" type="button">Modo completo</button>
            </div>

            <div class="fin-customize-group">
                <p class="fin-customize-group-title">Blocos da tela</p>
                <label class="fin-customize-toggle">
                    <span>Cards de resumo</span>
                    <input type="checkbox" id="toggleFinSummary" checked>
                </label>
                <label class="fin-customize-toggle">
                    <span>Acoes da aba orcamentos</span>
                    <input type="checkbox" id="toggleFinOrcActions" checked>
                </label>
                <label class="fin-customize-toggle">
                    <span>Acoes da aba metas</span>
                    <input type="checkbox" id="toggleFinMetasActions" checked>
                </label>
                <label class="fin-customize-toggle">
                    <span>Insights de orcamentos</span>
                    <input type="checkbox" id="toggleFinInsights" checked>
                </label>
            </div>
        </div>

        <div class="fin-customize-footer">
            <button class="fin-customize-save" id="btnSaveCustomizeFinancas" type="button">Salvar</button>
        </div>
    </div>
</div>