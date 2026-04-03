<!-- ============================================================
         PERSONALIZAR DASHBOARD — Botão + Modal
         ============================================================ -->
<div class="dash-customize-trigger">
    <button class="dash-btn dash-btn--ghost" id="btnCustomizeDashboard" type="button">
        <i data-lucide="sliders-horizontal"></i> Personalizar dashboard
    </button>
</div>

<!-- Modal de personalização -->
<div class="dash-modal-overlay" id="customizeModalOverlay" style="display:none;">
    <div class="dash-modal" role="dialog" aria-modal="true" aria-labelledby="customizeModalTitle">
        <div class="dash-modal__header">
            <h3 class="dash-modal__title" id="customizeModalTitle">Personalizar dashboard</h3>
            <button class="dash-modal__close" id="btnCloseCustomize" type="button" title="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="dash-modal__body">
            <p class="dash-modal__desc">Comece no modo essencial e ative extras quando fizer sentido para você.</p>

            <div class="dash-preset-switch" role="group" aria-label="Preset de visualização">
                <button class="dash-btn dash-btn--ghost" id="btnPresetEssencial" type="button">
                    Modo essencial
                </button>
                <button class="dash-btn dash-btn--ghost" id="btnPresetCompleto" type="button">
                    Modo completo
                </button>
            </div>

            <div class="dash-toggle-group">
                <span class="dash-toggle-group__title">Principais</span>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleAlertas" checked>
                    <span class="dash-toggle__label">Alertas</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleHealthScore" checked>
                    <span class="dash-toggle__label">Saúde financeira</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleAiTip" checked>
                    <span class="dash-toggle__label">Dicas do Lukrato</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleEvolucao" checked>
                    <span class="dash-toggle__label">Evolução financeira</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="togglePrevisao" checked>
                    <span class="dash-toggle__label">Previsão financeira</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleGrafico" checked>
                    <span class="dash-toggle__label">Gráfico de categorias</span>
                </label>
            </div>

            <div class="dash-toggle-group">
                <span class="dash-toggle-group__title">Extras</span>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleMetas">
                    <span class="dash-toggle__label">Metas</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleCartoes">
                    <span class="dash-toggle__label">Cartões</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleContas">
                    <span class="dash-toggle__label">Contas</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleOrcamentos">
                    <span class="dash-toggle__label">Orçamentos</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleFaturas">
                    <span class="dash-toggle__label">Faturas de cartão</span>
                </label>
                <label class="dash-toggle">
                    <input type="checkbox" id="toggleGamificacao">
                    <span class="dash-toggle__label">Gamificação</span>
                </label>
            </div>
        </div>
        <div class="dash-modal__footer">
            <button class="dash-btn dash-btn--primary" id="btnSaveCustomize" type="button">Salvar</button>
        </div>
    </div>