<div class="cart-customize-trigger">
    <button class="btn btn-ghost surface-card" id="btnCustomizeCartoes" type="button">
        <i data-lucide="sliders-horizontal"></i> Personalizar tela
    </button>
</div>

<div class="cart-customize-overlay" id="cartoesCustomizeModalOverlay" style="display:none;">
    <div class="cart-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="cartoesCustomizeModalTitle">
        <div class="cart-customize-header">
            <h3 class="cart-customize-title" id="cartoesCustomizeModalTitle">Personalizar cartões</h3>
            <button class="cart-customize-close" id="btnCloseCustomizeCartoes" type="button"
                aria-label="Fechar personalizacao">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="cart-customize-body">
            <p class="cart-customize-desc">Comece no modo essencial e ative os blocos quando fizer sentido.</p>

            <div class="cart-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="btn btn-ghost" id="btnPresetEssencialCartoes" type="button">Modo essencial</button>
                <button class="btn btn-ghost" id="btnPresetCompletoCartoes" type="button">Modo completo</button>
            </div>

            <div class="cart-customize-group">
                <p class="cart-customize-group-title">Blocos da tela</p>
                <label class="cart-customize-toggle">
                    <span>Resumo consolidado</span>
                    <input type="checkbox" id="toggleCartoesKpis" checked>
                </label>
                <label class="cart-customize-toggle">
                    <span>Barra de filtros</span>
                    <input type="checkbox" id="toggleCartoesToolbar" checked>
                </label>
            </div>
        </div>

        <div class="cart-customize-footer">
            <button class="btn btn-primary" id="btnSaveCustomizeCartoes" type="button">Salvar</button>
        </div>
    </div>
</div>