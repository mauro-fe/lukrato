<div class="bill-customize-trigger">
    <button class="bill-customize-open" id="btnCustomizeBilling" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span>Personalizar tela</span>
    </button>
</div>

<div class="bill-customize-overlay" id="billingCustomizeModalOverlay" style="display:none;">
    <div class="bill-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="billingCustomizeModalTitle">
        <div class="bill-customize-header">
            <h3 class="bill-customize-title" id="billingCustomizeModalTitle">Personalizar billing</h3>
            <button class="bill-customize-close" id="btnCloseCustomizeBilling" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="bill-customize-body">
            <p class="bill-customize-desc">Escolha os blocos que deseja manter visiveis nesta tela.</p>

            <div class="bill-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="bill-customize-preset" id="btnPresetEssencialBilling" type="button">Modo
                    essencial</button>
                <button class="bill-customize-preset" id="btnPresetCompletoBilling" type="button">Modo completo</button>
            </div>

            <div class="bill-customize-group">
                <p class="bill-customize-group-title">Blocos da tela</p>
                <label class="bill-customize-toggle">
                    <span>Cabeçalho da página</span>
                    <input type="checkbox" id="toggleBillingHeader" checked>
                </label>
                <label class="bill-customize-toggle">
                    <span>Grid de planos</span>
                    <input type="checkbox" id="toggleBillingPlans" checked>
                </label>
            </div>
        </div>

        <div class="bill-customize-footer">
            <button class="bill-customize-save" id="btnSaveCustomizeBilling" type="button">Salvar</button>
        </div>
    </div>
</div>
</div>