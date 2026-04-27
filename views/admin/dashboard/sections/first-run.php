<section class="dash-first-run-stack" id="dashboardFirstRunStack" hidden>
    <section class="dash-quick-start surface-card surface-card--interactive" id="dashboardQuickStart" hidden>
        <div class="dash-quick-start__main">
            <span class="dash-first-run__eyebrow" id="dashboardQuickStartEyebrow">Configuração inicial</span>
            <h2 id="dashboardQuickStartTitle">Cadastre sua primeira conta</h2>
            <p class="dash-quick-start__summary" id="dashboardQuickStartSummary">
                Comece pela base do seu fluxo financeiro. Assim que a conta for criada, o painel passa a refletir a sua operação.
            </p>

            <div class="dash-quick-start__meta" id="dashboardPreviewNotice" hidden>
                <span class="dash-quick-start__meta-badge">
                    <i data-lucide="sparkles"></i>
                    Modo de demonstração
                </span>
                <span class="dash-quick-start__meta-text">Você está usando dados de exemplo enquanto finaliza a configuração inicial.</span>
                <button type="button" class="dash-first-run__inline-link" id="dashboardPreviewLearnMore">
                    Sobre a prévia
                </button>
            </div>
        </div>

        <div class="dash-quick-start__cta-panel">
            <button class="dash-btn dash-btn--primary dash-btn--lg" id="dashboardFirstTransactionCta" type="button">
                <i data-lucide="plus"></i> Criar primeira conta
            </button>
            <button class="dash-btn dash-btn--ghost dash-quick-start__tour-btn" id="dashboardOpenTourPrompt"
                type="button">
                Ver tour
            </button>
        </div>
    </section>

    <section class="dash-first-run-bar surface-card" id="dashboardDisplayNamePrompt" hidden>
        <form class="dash-first-run-bar__name" id="dashboardDisplayNameForm" hidden>
            <label class="dash-first-run-bar__label" for="dashboardDisplayNameInput">
                Como prefere ser chamado?
            </label>
            <input type="text" id="dashboardDisplayNameInput" class="lk-input" maxlength="80" placeholder="Ex.: Mauro"
                autocomplete="nickname">
            <button type="submit" class="dash-btn dash-btn--ghost dash-first-run-bar__save-btn" id="dashboardDisplayNameSubmit">
                Salvar
            </button>
            <button type="button" class="dash-btn dash-btn--ghost" id="dashboardDisplayNameDismiss">
                Agora não
            </button>
        </form>

        <p class="dash-first-run-bar__feedback" id="dashboardDisplayNameFeedback" hidden aria-live="polite"></p>
    </section>
</section>