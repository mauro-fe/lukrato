<div class="imp-customize-trigger">
    <button class="imp-customize-open surface-card" id="btnCustomizeImportacoes" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="imp-customize-overlay" id="importacoesCustomizeModalOverlay" style="display:none;">
    <div class="imp-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="importacoesCustomizeModalTitle">
        <div class="imp-customize-header">
            <h3 class="imp-customize-title" id="importacoesCustomizeModalTitle">Personalizar importacoes</h3>
            <button class="imp-customize-close" id="btnCloseCustomizeImportacoes" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="imp-customize-body">
            <p class="imp-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="imp-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="imp-customize-preset" id="btnPresetEssencialImportacoes" type="button">Modo
                    essencial</button>
                <button class="imp-customize-preset" id="btnPresetCompletoImportacoes" type="button">Modo
                    completo</button>
            </div>

            <div class="imp-customize-group">
                <p class="imp-customize-group-title">Blocos da tela</p>
                <label class="imp-customize-toggle">
                    <span>Cabeçalho de contexto</span>
                    <input type="checkbox" id="toggleImpHero" checked>
                </label>
                <label class="imp-customize-toggle">
                    <span>Painel lateral de apoio</span>
                    <input type="checkbox" id="toggleImpSidebar" checked>
                </label>
            </div>
        </div>

        <div class="imp-customize-footer">
            <button class="imp-customize-save" id="btnSaveCustomizeImportacoes" type="button">Salvar</button>
        </div>
    </div>
</div>