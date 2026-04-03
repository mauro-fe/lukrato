<div class="cont-customize-trigger">
    <button class="btn btn-ghost" id="btnCustomizeContas" type="button">
        <i data-lucide="sliders-horizontal"></i> Personalizar tela
    </button>
</div>

<div class="cont-customize-overlay" id="contasCustomizeModalOverlay" style="display:none;">
    <div class="cont-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="contasCustomizeModalTitle">
        <div class="cont-customize-header">
            <h3 class="cont-customize-title" id="contasCustomizeModalTitle">Personalizar contas</h3>
            <button class="cont-customize-close" id="btnCloseCustomizeContas" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="cont-customize-body">
            <p class="cont-customize-desc">Comece no modo essencial e habilite blocos extras quando quiser.</p>

            <div class="cont-customize-presets" role="group" aria-label="Preset de visualizacao">
                <button class="btn btn-ghost" id="btnPresetEssencialContas" type="button">Modo essencial</button>
                <button class="btn btn-ghost" id="btnPresetCompletoContas" type="button">Modo completo</button>
            </div>

            <div class="cont-customize-group">
                <p class="cont-customize-group-title">Blocos da tela</p>
                <label class="cont-customize-toggle">
                    <span>Hero consolidado</span>
                    <input type="checkbox" id="toggleContasHero" checked>
                </label>
                <label class="cont-customize-toggle">
                    <span>Cards de KPI</span>
                    <input type="checkbox" id="toggleContasKpis" checked>
                </label>
                <label class="cont-customize-toggle">
                    <span>Distribuicao de saldo</span>
                    <input type="checkbox" id="toggleContasDistribution" checked>
                </label>
            </div>
        </div>

        <div class="cont-customize-footer">
            <button class="btn btn-primary" id="btnSaveCustomizeContas" type="button">Salvar</button>
        </div>
    </div>
</div>