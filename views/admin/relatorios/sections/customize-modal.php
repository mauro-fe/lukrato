    <div class="rel-customize-trigger">
        <button class="rel-customize-open surface-card" id="btnCustomizeRelatorios" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>

    <div class="rel-customize-overlay" id="relatoriosCustomizeModalOverlay" style="display:none;">
        <div class="rel-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="relatoriosCustomizeModalTitle">
            <div class="rel-customize-header">
                <h3 class="rel-customize-title" id="relatoriosCustomizeModalTitle">Personalizar relatorios</h3>
                <button class="rel-customize-close" id="btnCloseCustomizeRelatorios" type="button"
                    aria-label="Fechar personalizacao">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="rel-customize-body">
                <p class="rel-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

                <div class="rel-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="rel-customize-preset" id="btnPresetEssencialRelatorios" type="button">Modo
                        essencial</button>
                    <button class="rel-customize-preset" id="btnPresetCompletoRelatorios" type="button">Modo
                        completo</button>
                </div>

                <div class="rel-customize-group">
                    <p class="rel-customize-group-title">Blocos da tela</p>
                    <label class="rel-customize-toggle">
                        <span>Cards de resumo rápido</span>
                        <input type="checkbox" id="toggleRelQuickStats" checked>
                    </label>
                    <label class="rel-customize-toggle">
                        <span>Mini graficos da visão geral</span>
                        <input type="checkbox" id="toggleRelOverviewCharts" checked>
                    </label>
                    <label class="rel-customize-toggle">
                        <span>Barra de controles</span>
                        <input type="checkbox" id="toggleRelControls" checked>
                    </label>
                </div>
            </div>

            <div class="rel-customize-footer">
                <button class="rel-customize-save" id="btnSaveCustomizeRelatorios" type="button">Salvar</button>
            </div>
        </div>
    </div>